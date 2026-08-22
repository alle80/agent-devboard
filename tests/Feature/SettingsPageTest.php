<?php

namespace Alle80\Griglia\Tests\Feature;

use Alle80\Griglia\Livewire\SettingsPage;
use Alle80\Griglia\Livewire\TodoList;
use Alle80\Griglia\Settings\AgentSettings;
use Alle80\Griglia\Settings\AppSettings;
use Alle80\Griglia\Themes;
use Alle80\Griglia\Tests\TestCase;
use Livewire\Livewire;

class SettingsPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsUser();
    }

    public function test_toggle_select_and_int_fields(): void
    {
        $page = Livewire::test(SettingsPage::class);
        $page->assertSee('How Agent works');

        $page->call('toggle', 'agent', 'commit_after_task');
        $this->assertFalse(app(AgentSettings::class)->refresh()->commit_after_task);

        $page->set('values.agent.autonomy', 'autonomous');
        $this->assertSame('autonomous', app(AgentSettings::class)->refresh()->autonomy);

        $page->set('values.agent.autonomy', 'bogus');
        $this->assertSame('autonomous', app(AgentSettings::class)->refresh()->autonomy);
        $page->set('values.agent.autonomy', 'decide'); // the pre-499 value is no longer an option
        $this->assertSame('autonomous', app(AgentSettings::class)->refresh()->autonomy);

        $page->set('values.agent.response_tone', 'conversational');
        $this->assertSame('conversational', app(AgentSettings::class)->refresh()->response_tone);

        $page->set('values.agent.response_length', 'detailed');
        $this->assertSame('detailed', app(AgentSettings::class)->refresh()->response_length);

        $page->set('values.app.title_max_length', 999);
        $this->assertSame(200, app(AppSettings::class)->refresh()->title_max_length);
        $this->assertSame(200, TodoList::titleMax());

        $page->set('values.agent.daily_summary_time', '25:99')->assertDispatched('toast');
        $this->assertSame('21:00', app(AgentSettings::class)->refresh()->daily_summary_time);
    }

    /**
     * Le select (e gli altri campi non-bool) devono salvare da sole al cambio: in Livewire 4
     * «wire:model.change» senza «.live» aggiorna il valore solo lato client e non invia la
     * richiesta, quindi updatedValues() non scattava e il settaggio restava quello vecchio (task 436).
     */
    public function test_non_bool_fields_are_bound_live_so_they_save_on_change(): void
    {
        $html = Livewire::test(SettingsPage::class)->html();

        $this->assertStringNotContainsString('wire:model.change="values.', $html);
        $this->assertStringContainsString('wire:model.live.change="values.agent.git_flow"', $html);
        $this->assertStringContainsString('wire:model.live.change="values.app.title_max_length"', $html);
    }

    public function test_notification_settings_are_grouped_in_the_notifications_tab(): void
    {
        $html = Livewire::test(SettingsPage::class)->html();
        $notificationPanel = substr($html, strpos($html, 'id="panel-notif"'));
        $notificationPanel = substr($notificationPanel, 0, strpos($notificationPanel, 'id="panel-themes"'));

        $this->assertStringContainsString("toggle('agent', 'notify_on_done')", $notificationPanel);
        $this->assertStringContainsString('values.agent.daily_summary_time', $notificationPanel);
        $this->assertStringContainsString("toggle('app', 'notify_in_app')", $notificationPanel);
        $this->assertStringNotContainsString("toggle('agent', 'commit_after_task')", $notificationPanel);
    }

    public function test_theme_setting_renders_home_without_a_slug_route(): void
    {
        config(['griglia.themes' => ['ocean' => array_replace(Themes::builtin()['slate'], ['label' => 'Ocean', 'icon' => '🌊', 'claim' => 'deep blue'])]]);
        Livewire::test(SettingsPage::class)
            ->set('values.app.default_style', 'ocean');
        $this->assertSame('ocean', app(AppSettings::class)->refresh()->default_style);

        $this->get('/')->assertOk()->assertSee('deep blue');
        $this->get('/ocean')->assertNotFound();
        $this->get('/slate')->assertNotFound();
        $this->get('/dashboard')->assertOk();
    }
}
