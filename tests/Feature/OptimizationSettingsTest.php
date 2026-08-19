<?php

namespace Alle80\Devboard\Tests\Feature;

use Alle80\Devboard\Livewire\SettingsPage;
use Alle80\Devboard\Models\Checklist;
use Alle80\Devboard\Models\Todo;
use Alle80\Devboard\Settings\OptimizationSettings;
use Alle80\Devboard\Tests\TestCase;
use Livewire\Livewire;

/** «Optimization» settings group: token-saving switches read by devboard:check and shown in /settings. */
class OptimizationSettingsTest extends TestCase
{
    protected Todo $todo;

    protected function setUp(): void
    {
        parent::setUp();
        $user = $this->actingAsUser();
        $list = Checklist::create(['name' => 'dev', 'user_id' => $user->id]);
        $parent = Todo::create(['title' => 'Old', 'order' => 1, 'checklist_id' => $list->id, 'completed' => true, 'claude_comment' => str_repeat('x', 300)]);
        $this->todo = Todo::create(['title' => 'Add dark mode', 'order' => 2, 'checklist_id' => $list->id, 'open_to_work' => true, 'parent_id' => $parent->id]);
    }

    public function test_defaults_and_compact_output_on_actions(): void
    {
        $opt = app(OptimizationSettings::class);
        $this->assertTrue($opt->compact_check);
        $this->assertFalse($opt->terse_agent);
        $this->assertSame(0, $opt->context_max_chars);
        $this->assertTrue($opt->progress_piggyback);
        $this->assertTrue($opt->token_report);

        // Plain check: settings + optimization line + listing
        $this->artisan('devboard:check')
            ->expectsOutputToContain('FOLLOW THEM')
            ->expectsOutputToContain('⚡ optimization: ')
            ->expectsOutputToContain('🟢 #2 Add dark mode')
            ->doesntExpectOutputToContain('TERSE MODE')
            ->assertSuccessful();

        // Action with compact on: only the result line
        $this->artisan('devboard:check', ['--take' => $this->todo->id])
            ->expectsOutputToContain('taken in charge')
            ->doesntExpectOutputToContain('FOLLOW THEM')
            ->doesntExpectOutputToContain('#2 Add dark mode')
            ->assertSuccessful();

        // Compact off: the action prints the listing again
        $opt->compact_check = false;
        $opt->save();
        $this->artisan('devboard:check', ['--take' => $this->todo->id, '--progress' => 40])
            ->expectsOutputToContain('taken in charge')
            ->expectsOutputToContain('FOLLOW THEM')
            ->expectsOutputToContain('🔧 #2 Add dark mode [40%]')
            ->assertSuccessful();
    }

    public function test_terse_rules_and_context_trim(): void
    {
        $opt = app(OptimizationSettings::class);
        $opt->terse_agent = true;
        $opt->context_max_chars = 100;
        $opt->save();

        $this->artisan('devboard:check')
            ->expectsOutputToContain('TERSE MODE ON')
            ->expectsOutputToContain(str_repeat('x', 100).' […]')
            ->doesntExpectOutputToContain(str_repeat('x', 101))
            ->assertSuccessful();

        $this->assertSame('abc', $opt->trim('abc'));
        $this->assertNull($opt->trim(null));
    }

    public function test_settings_page_shows_and_toggles_the_group(): void
    {
        $page = Livewire::test(SettingsPage::class);
        $page->assertSee('Optimization')->assertSee('Terse mode');

        $page->call('toggle', 'optimization', 'terse_agent');
        $this->assertTrue(app(OptimizationSettings::class)->refresh()->terse_agent);

        $page->set('values.optimization.context_max_chars', 99999);
        $this->assertSame(5000, app(OptimizationSettings::class)->refresh()->context_max_chars);
    }
}
