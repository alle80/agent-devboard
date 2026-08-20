<?php

namespace Alle80\Griglia\Tests\Feature;

use Alle80\Griglia\Livewire\NotificationBell;
use Alle80\Griglia\Models\Checklist;
use Alle80\Griglia\Models\Todo;
use Alle80\Griglia\Notifications\QuestionAsked;
use Alle80\Griglia\Notifications\TodoCompleted;
use Alle80\Griglia\Settings\AgentSettings;
use Alle80\Griglia\Settings\AppSettings;
use Alle80\Griglia\Tests\Support\User;
use Alle80\Griglia\Tests\TestCase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use NotificationChannels\WebPush\WebPushChannel;

/** The board notifies the list owner itself: database (bell), web push, mail — on --done and --ask. */
class NotificationsTest extends TestCase
{
    protected User $user;

    protected Todo $todo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->actingAsUser();
        $list = Checklist::create(['name' => 'dev', 'user_id' => $this->user->id]);
        session(['checklist_id' => $list->id]);
        $this->todo = Todo::create(['title' => 'Dark mode', 'order' => 1, 'checklist_id' => $list->id, 'open_to_work' => true, 'working' => true]);
    }

    public function test_done_and_ask_notify_the_owner_on_the_enabled_channels(): void
    {
        Notification::fake();
        $this->artisan('griglia:check', ['--done' => $this->todo->id, '--comment' => 'Shipped it'])->assertSuccessful();
        Notification::assertSentTo($this->user, TodoCompleted::class, function (TodoCompleted $n, array $channels) {
            $this->assertSame(['database'], $channels, 'defaults: bell yes, mail no (no mailer), webpush needs a subscription trait + key');
            $this->assertSame('Shipped it', $n->body());
            $this->assertStringContainsString('open='.$this->todo->id, $n->url());

            return true;
        });

        $t2 = Todo::create(['title' => 'Q', 'order' => 2, 'checklist_id' => $this->todo->checklist_id, 'open_to_work' => true]);
        $this->artisan('griglia:check', ['--ask' => $t2->id, '--q' => ['Which color?']])->assertSuccessful();
        Notification::assertSentTo($this->user, QuestionAsked::class, fn (QuestionAsked $n) => $n->body() === 'Which color?' && str_contains($n->title(), '1'));
    }

    public function test_channels_follow_the_settings(): void
    {
        Notification::fake();
        $agent = app(AgentSettings::class);
        $agent->notify_on_done = false;
        $agent->save();
        $this->artisan('griglia:check', ['--done' => $this->todo->id])->assertSuccessful();
        Notification::assertNothingSent();

        $agent->notify_on_done = true;
        $agent->save();
        $app = app(AppSettings::class);
        $app->notify_in_app = false;
        $app->notify_webpush = true;
        $app->notify_mail = true;
        $app->save();
        config(['webpush.vapid.public_key' => 'BKEY', 'mail.default' => 'log']);
        $this->todo->update(['completed' => false, 'working' => true]);
        $this->artisan('griglia:check', ['--done' => $this->todo->id])->assertSuccessful();
        Notification::assertSentTo($this->user, TodoCompleted::class, function ($n, array $channels) {
            sort($channels);
            $expected = ['mail', WebPushChannel::class];
            sort($expected);
            $this->assertSame($expected, $channels);

            return true;
        });
    }

    public function test_bell_lists_unread_and_opens_the_todo(): void
    {
        $this->artisan('griglia:check', ['--done' => $this->todo->id, '--comment' => 'Done'])->assertSuccessful();
        $this->assertSame(1, $this->user->unreadNotifications()->count(), 'stored in the notifications table');

        $bell = Livewire::test(NotificationBell::class)->assertSee('Dark mode')->assertSee('1');
        $id = $this->user->notifications()->first()->id;
        $bell->call('openNotification', $id)->assertDispatched('open-ingredients', todoId: $this->todo->id);
        $this->assertSame(0, $this->user->unreadNotifications()->count());

        $this->artisan('griglia:check', ['--ask' => $this->todo->id, '--q' => ['Really?']])->assertSuccessful();
        Livewire::test(NotificationBell::class)->call('markAllRead');
        $this->assertSame(0, $this->user->unreadNotifications()->count());
    }

    public function test_push_subscription_endpoints_and_service_worker(): void
    {
        // only https endpoints of known push services (SSRF)
        $this->postJson(route('griglia.push.store'), ['endpoint' => 'http://127.0.0.1:8080/apps/x', 'keys' => ['p256dh' => 'K', 'auth' => 'A']])->assertStatus(422);
        $this->postJson(route('griglia.push.store'), ['endpoint' => 'https://evil.example/abc', 'keys' => ['p256dh' => 'K', 'auth' => 'A']])->assertStatus(422);
        $this->post(route('griglia.push.store'), ['endpoint' => 'https://fcm.googleapis.com/fcm/send/abc', 'keys' => ['p256dh' => 'K', 'auth' => 'A'], 'contentEncoding' => 'aesgcm'])->assertOk()->assertJson(['ok' => true]);
        $this->assertSame(1, $this->user->pushSubscriptions()->count());
        $this->assertSame('K', $this->user->pushSubscriptions()->first()->public_key);
        $this->assertTrue(\Alle80\Griglia\Http\Controllers\PushSubscriptionController::endpointAllowed('https://web.push.apple.com/QWxh'));
        $this->assertFalse(\Alle80\Griglia\Http\Controllers\PushSubscriptionController::endpointAllowed('https://attacker.push.apple.com.evil.test/x'));
        config(['griglia.push_allowed_hosts' => []]);
        $this->assertTrue(\Alle80\Griglia\Http\Controllers\PushSubscriptionController::endpointAllowed('https://any.host/x'), 'empty list = any https');
        $this->assertFalse(\Alle80\Griglia\Http\Controllers\PushSubscriptionController::endpointAllowed('http://any.host/x'));

        $this->delete(route('griglia.push.destroy'), ['endpoint' => 'https://fcm.googleapis.com/fcm/send/abc'])->assertOk();
        $this->assertSame(0, $this->user->pushSubscriptions()->count());

        $this->get('/griglia-sw.js')->assertOk()->assertHeader('Content-Type', 'application/javascript; charset=utf-8')->assertSee('notificationclick');

        $this->post(route('griglia.notifications.test'))->assertOk();
        $this->assertSame(1, $this->user->unreadNotifications()->count(), 'test notification lands in the bell');
        // throttled: 5 per minute on the test endpoint
        for ($i = 0; $i < 4; $i++) { $this->post(route('griglia.notifications.test'))->assertOk(); }
        $this->post(route('griglia.notifications.test'))->assertStatus(429);
    }

    public function test_deep_link_switches_list_and_opens_the_todo(): void
    {
        $other = Checklist::create(['name' => 'other', 'user_id' => $this->user->id]);
        $this->get('/settings?list='.$other->id.'&open=42')->assertOk();
        $this->assertSame($other->id, session('checklist_id'));
        $this->assertSame(42, session('griglia_open_todo'));

        $foreign = Checklist::create(['name' => 'x', 'user_id' => User::create(['name' => 'B', 'email' => 'b@x.it', 'password' => bcrypt('s')])->id]);
        $this->get('/settings?list='.$foreign->id)->assertOk();
        $this->assertSame($other->id, session('checklist_id'), 'lists of other users are ignored');
    }
}
