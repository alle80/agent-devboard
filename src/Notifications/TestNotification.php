<?php

namespace Alle80\Devboard\Notifications;

use Alle80\Devboard\Settings\AppSettings;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/** «Send me a test notification» from /settings: goes through the enabled channels. */
class TestNotification extends Notification
{
    public function via(object $notifiable): array
    {
        $app = app(AppSettings::class);
        $via = $app->notify_in_app ? ['database'] : [];
        if ($app->notify_webpush && method_exists($notifiable, 'routeNotificationForWebPush') && config('webpush.vapid.public_key')) {
            $via[] = WebPushChannel::class;
        }
        if ($app->notify_mail && ! empty($notifiable->email) && config('mail.default') && config('mail.default') !== 'array') {
            $via[] = 'mail';
        }

        return $via;
    }

    public function toArray(object $notifiable): array
    {
        return ['kind' => 'test', 'icon' => '🔔', 'title' => __('devboard::t.notif.test_title'), 'body' => __('devboard::t.notif.test_body'), 'todo_id' => 0, 'checklist_id' => 0, 'url' => url('/')];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('🔔 '.__('devboard::t.notif.test_title'))->line(__('devboard::t.notif.test_body'));
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        return (new WebPushMessage)->title('🔔 '.__('devboard::t.notif.test_title'))->body(__('devboard::t.notif.test_body'))->tag('devboard-test')->data(['url' => url('/')]);
    }
}
