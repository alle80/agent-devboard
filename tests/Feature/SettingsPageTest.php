<?php

namespace Alle80\Devboard\Tests\Feature;

use Alle80\Devboard\Livewire\SettingsPage;
use Alle80\Devboard\Livewire\TodoList;
use Alle80\Devboard\Settings\AgentSettings;
use Alle80\Devboard\Settings\AppSettings;
use Alle80\Devboard\Tests\TestCase;
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

        $page->set('values.agent.autonomy', 'decide');
        $this->assertSame('decide', app(AgentSettings::class)->refresh()->autonomy);

        $page->set('values.agent.autonomy', 'bogus');
        $this->assertSame('decide', app(AgentSettings::class)->refresh()->autonomy);

        $page->set('values.app.title_max_length', 999);
        $this->assertSame(200, app(AppSettings::class)->refresh()->title_max_length);
        $this->assertSame(200, TodoList::titleMax());

        $page->set('values.agent.daily_summary_time', '25:99')->assertDispatched('toast');
        $this->assertSame('21:00', app(AgentSettings::class)->refresh()->daily_summary_time);
    }

    public function test_default_style_redirects_home(): void
    {
        $this->get('/')->assertOk();
        $s = app(AppSettings::class);
        $s->default_style = 'slate';
        $s->save();
        $this->get('/')->assertRedirect('/slate');
        $this->get('/?stay=1')->assertOk();
    }
}
