<?php

namespace Alle80\Devboard\Tests\Feature;

use Alle80\Devboard\Livewire\ChecklistSwitcher;
use Alle80\Devboard\Mode;
use Alle80\Devboard\Models\Checklist;
use Alle80\Devboard\Settings\AppSettings;
use Alle80\Devboard\Tests\Support\User;
use Alle80\Devboard\Tests\TestCase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

/** Local (no auth, global lists) vs server (auth + access check) modes. */
class ModeTest extends TestCase
{
    protected function tearDown(): void
    {
        Mode::reset();
        parent::tearDown();
    }

    public function test_server_mode_requires_login_and_honours_the_access_hooks(): void
    {
        Route::get('/login', fn () => 'login')->name('login');
        $this->assertSame('server', Mode::current());
        $this->get('/settings')->assertRedirect('/login');

        $u = $this->actingAsUser();
        $this->get('/settings')->assertOk();

        // Gate ability from config
        config(['devboard.access_gate' => 'access-devboard']);
        Gate::define('access-devboard', fn ($user) => $user->email === 'boss@example.com');
        $this->get('/settings')->assertForbidden();
        $this->actingAs(User::create(['name' => 'Boss', 'email' => 'boss@example.com', 'password' => bcrypt('x')]));
        $this->get('/settings')->assertOk();

        // canAccessDevboard() on the model wins over the gate
        $this->actingAs(new class extends User {
            public function canAccessDevboard(): bool { return false; }
        });
        $this->get('/settings')->assertForbidden();
    }

    public function test_local_mode_has_no_auth_and_global_lists(): void
    {
        config(['devboard.mode' => 'local']);
        Mode::reset();
        $this->assertTrue(Mode::isLocal());

        // guest can use the board
        $this->get('/settings')->assertOk();
        $this->get('/')->assertOk()->assertSee('local mode')->assertDontSee('notification-bell');

        // lists are global: no user_id, everybody sees all of them
        $owner = User::create(['name' => 'A', 'email' => 'a@x.it', 'password' => bcrypt('x')]);
        Checklist::create(['name' => 'of A', 'user_id' => $owner->id]);
        $id = Checklist::currentId();
        $this->assertNull(Checklist::find($id)->user_id, 'created without user');
        $this->assertSame(2, Checklist::mine()->count(), 'all lists are visible in local mode');
        Livewire::test(ChecklistSwitcher::class)->assertSee('of A');

        // public broadcast channel + listener
        $this->assertSame('devboard.local', Mode::broadcastChannel());
        $this->assertSame('echo:devboard.local,.TodoChanged', Mode::echoListener());
    }

    public function test_mode_setting_overrides_the_config_and_dashboard_tab_switch(): void
    {
        $this->actingAsUser();
        $app = app(AppSettings::class);
        $this->assertSame('', $app->mode);
        $app->mode = 'local';
        $app->save();
        Mode::reset();
        $this->assertTrue(Mode::isLocal());
        auth()->logout();
        $this->get('/settings')->assertOk();

        $app->mode = '';
        $app->show_dashboard_tab = false;
        $app->save();
        Mode::reset();
        $this->assertFalse(Mode::isLocal());
        $this->actingAsUser('c@x.it');
        $this->get('/')->assertOk()->assertDontSee('DASHBOARD');
    }
}
