<?php

namespace Alle80\Devboard\Tests\Feature;

use Alle80\Devboard\Admin;
use Alle80\Devboard\Livewire\ContextPage;
use Alle80\Devboard\Livewire\SettingsPage;
use Alle80\Devboard\Tests\Support\User;
use Alle80\Devboard\Tests\TestCase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

/** Admin boundary: settings/context/themes only for administrators; local mode not switchable from the UI. */
class AdminTest extends TestCase
{
    public function test_first_user_is_admin_by_default_others_are_not(): void
    {
        $first = $this->actingAsUser('first@example.com');
        $this->assertTrue(Admin::allows($first));
        $this->get('/settings')->assertOk();
        $this->get('/context')->assertOk();
        $this->get('/')->assertOk()->assertSee('/settings');

        $second = User::create(['name' => 'Two', 'email' => 'two@example.com', 'password' => bcrypt('x')]);
        $this->actingAs($second);
        $this->assertFalse(Admin::allows($second));
        $this->get('/settings')->assertForbidden();
        $this->get('/context')->assertForbidden();
        $this->get('/stats')->assertOk();
        $this->get('/')->assertOk()->assertDontSee('href="http://localhost/settings"', false);

        // Livewire components refuse too (defence in depth)
        Livewire::test(SettingsPage::class)->assertForbidden();
    }

    public function test_admin_sources_list_gate_and_model_method(): void
    {
        $this->actingAsUser('first@example.com');
        $second = User::create(['name' => 'Two', 'email' => 'two@example.com', 'password' => bcrypt('x')]);

        config(['devboard.admins' => 'two@example.com']);
        $this->assertTrue(Admin::allows($second));
        $this->assertFalse(Admin::allows(User::first()), 'explicit list replaces the first-user default');
        config(['devboard.admins' => (string) $second->id]);
        $this->assertTrue(Admin::allows($second));

        config(['devboard.admins' => null, 'devboard.admin_gate' => 'manage-devboard']);
        Gate::define('manage-devboard', fn ($u) => $u->email === 'two@example.com');
        $this->assertTrue(Admin::allows($second));
        $this->assertFalse(Admin::allows(User::first()));

        $custom = new class extends User { public function canManageDevboard(): bool { return true; } };
        $this->assertTrue(Admin::allows($custom), 'model method wins');
        $this->assertFalse(Admin::allows(null));
    }

    public function test_local_mode_cannot_be_enabled_from_the_ui_unless_allowed(): void
    {
        $this->actingAsUser();
        $page = Livewire::test(SettingsPage::class);
        $page->set('values.app.mode', 'local');
        $this->assertSame('', app(\Alle80\Devboard\Settings\AppSettings::class)->refresh()->mode);
        $this->assertArrayNotHasKey('local', \Alle80\Devboard\Settings\AppSettings::fields()['mode'][3]);

        config(['devboard.allow_local_from_ui' => true]);
        $this->assertArrayHasKey('local', \Alle80\Devboard\Settings\AppSettings::fields()['mode'][3]);
        Livewire::test(SettingsPage::class)->set('values.app.mode', 'local');
        $this->assertSame('local', app(\Alle80\Devboard\Settings\AppSettings::class)->refresh()->mode);
    }

    public function test_context_page_is_admin_only(): void
    {
        $this->actingAsUser('first@example.com');
        $second = User::create(['name' => 'Two', 'email' => 'two@example.com', 'password' => bcrypt('x')]);
        $this->actingAs($second);
        Livewire::test(ContextPage::class)->assertForbidden();
    }
}
