<?php

namespace Alle80\Griglia\Tests\Feature;

use Alle80\Griglia\Livewire\ChecklistSwitcher;
use Alle80\Griglia\Mode;
use Alle80\Griglia\Models\Checklist;
use Alle80\Griglia\Settings\AppSettings;
use Alle80\Griglia\Tests\Support\User;
use Alle80\Griglia\Tests\TestCase;
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
        $this->get('/stats')->assertRedirect('/login');

        $u = $this->actingAsUser();
        $this->get('/stats')->assertOk();

        // Gate ability from config
        config(['griglia.access_gate' => 'access-griglia']);
        Gate::define('access-griglia', fn ($user) => $user->email === 'boss@example.com');
        $this->get('/stats')->assertForbidden();
        $this->actingAs(User::create(['name' => 'Boss', 'email' => 'boss@example.com', 'password' => bcrypt('x')]));
        $this->get('/stats')->assertOk();

        // canAccessGriglia() on the model wins over the gate
        $this->actingAs(new class extends User {
            public function canAccessGriglia(): bool { return false; }
        });
        $this->get('/stats')->assertForbidden();

    }

    public function test_access_middleware_is_persistent_on_livewire_updates(): void
    {
        $this->assertContains(\Alle80\Griglia\Http\Middleware\GrigliaAccess::class, \Livewire\Livewire::getPersistentMiddleware(), 'GrigliaAccess replaces auth, so Livewire must re-apply it on /livewire/update');
    }

    public function test_local_mode_has_no_auth_and_global_lists(): void
    {
        config(['griglia.mode' => 'local']);
        Mode::reset();
        $this->assertTrue(Mode::isLocal());

        // guest can use the board
        $this->get('/settings')->assertOk();
        $this->get('/')->assertOk()->assertSee('local mode')->assertSee('Local mode: no authentication')->assertDontSee('notification-bell');

        // lists are global: no user_id, everybody sees all of them
        $owner = User::create(['name' => 'A', 'email' => 'a@x.it', 'password' => bcrypt('x')]);
        Checklist::create(['name' => 'of A', 'user_id' => $owner->id]);
        $id = Checklist::currentId();
        $this->assertNull(Checklist::find($id)->user_id, 'created without user');
        $this->assertSame(2, Checklist::mine()->count(), 'all lists are visible in local mode');
        Livewire::test(ChecklistSwitcher::class)->assertSee('of A');

        // public broadcast channel + listener
        $this->assertSame('griglia.local', Mode::broadcastChannel());
        $this->assertSame('echo:griglia.local,.TodoChanged', Mode::echoListener());
    }

    public function test_mode_setting_overrides_the_config_and_dashboard_tab_switch(): void
    {
        $this->actingAsUser();
        $app = app(AppSettings::class);
        $this->assertSame('', $app->mode);
        $app->mode = 'local';
        $app->save();
        Mode::reset();
        $this->assertFalse(Mode::isLocal(), 'a local override from the UI is ignored unless allowed');
        config(['griglia.allow_local_from_ui' => true]);
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
