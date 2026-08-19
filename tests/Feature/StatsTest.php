<?php

namespace Alle80\Devboard\Tests\Feature;

use Alle80\Devboard\Models\Checklist;
use Alle80\Devboard\Models\Todo;
use Alle80\Devboard\Settings\AppSettings;
use Alle80\Devboard\Support\Stats;
use Alle80\Devboard\Tests\TestCase;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;

/** Statistics & history: completed_at, cost from the price list, history rows, aggregates, series. */
class StatsTest extends TestCase
{
    protected Checklist $list;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsUser();
        $this->list = Checklist::create(['name' => 'proj', 'user_id' => auth()->id()]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_edge_cases_archived_included_tokens_without_time_and_prices_with_comma(): void
    {
        $s = app(AppSettings::class);
        $s->cost_per_m_in = '2,5'; // decimal comma accepted
        $s->cost_per_m_out = '0';
        $s->save();
        $a = Todo::create(['title' => 'archived done', 'order' => 1, 'checklist_id' => $this->list->id, 'completed' => true, 'archived_at' => now(), 'tokens_in' => 1_000_000]);
        Todo::create(['title' => 'timed only', 'order' => 2, 'checklist_id' => $this->list->id, 'completed' => true, 'work_seconds' => 120]);
        $rows = Stats::history($this->list);
        $this->assertCount(2, $rows, 'archived completed tasks stay in the history');
        $agg = Stats::aggregate($rows);
        $this->assertSame(2.5, $agg['cost'], 'only input priced');
        $this->assertSame(1, $agg['costed_count']);
        $this->assertSame(1, $agg['timed_count']);
        $this->assertSame(120, $agg['avg_work_seconds']);
        $this->assertSame(1, $agg['tokens_count']);
        $row = $rows->firstWhere('todo.id', $a->id);
        $this->assertNull($row['work_seconds']);
        $this->assertSame(2.5, $row['cost']);
    }

    public function test_completed_at_follows_the_flag_and_history_computes_times_costs(): void
    {
        Carbon::setTestNow('2026-08-19 10:00:00');
        $a = Todo::create(['title' => 'A', 'order' => 1, 'checklist_id' => $this->list->id]);
        $this->assertNull($a->completed_at);
        Carbon::setTestNow('2026-08-19 12:00:00');
        $a->update(['completed' => true, 'work_seconds' => 600, 'tokens_in' => 2_000_000, 'tokens_out' => 100_000]);
        $this->assertSame('2026-08-19 12:00:00', $a->fresh()->completed_at->format('Y-m-d H:i:s'));
        $a->update(['completed' => false]);
        $this->assertNull($a->fresh()->completed_at, 'reopened → out of the history');
        $a->update(['completed' => true]);

        $b = Todo::create(['title' => 'B (untracked)', 'order' => 2, 'checklist_id' => $this->list->id, 'completed' => true]);
        Todo::create(['title' => 'C (open)', 'order' => 3, 'checklist_id' => $this->list->id]);

        // No price list → cost unknown
        $this->assertNull(Stats::cost(2_000_000, 100_000));
        $rows = Stats::history($this->list);
        $this->assertCount(2, $rows);
        $this->assertSame('B (untracked)', $rows[0]['todo']->title, 'newest first');
        $this->assertNull($rows[0]['work_seconds'], '0 seconds = not tracked');
        $this->assertSame(600, $rows[1]['work_seconds']);
        $this->assertSame(7200, $rows[1]['lead_seconds']);

        // Price list → cost
        $s = app(AppSettings::class);
        $s->cost_per_m_in = '3';
        $s->cost_per_m_out = '15';
        $s->cost_currency = '€';
        $s->save();
        $this->assertSame(7.5, Stats::cost(2_000_000, 100_000));
        $rows = Stats::history($this->list);
        $agg = Stats::aggregate($rows);
        $this->assertSame(2, $agg['count']);
        $this->assertSame(600, $agg['work_seconds']);
        $this->assertSame(1, $agg['timed_count']);
        $this->assertSame(600, $agg['avg_work_seconds'], 'average over tracked items only');
        $this->assertSame(7.5, $agg['cost']);
        $this->assertSame(2_000_000, $agg['tokens_in']);
        $this->assertSame('7,50 €', Stats::money($agg['cost']));
        $this->assertSame('—', Stats::money(null));

        $series = Stats::series($rows, 3);
        $this->assertCount(3, $series);
        $this->assertSame(2, $series['2026-08-19']['count']);
        $this->assertSame(7.5, $series['2026-08-19']['cost']);
        $this->assertSame(0, $series[CarbonImmutable::today()->subDay()->toDateString()]['count']);

        $overview = Stats::overview(Checklist::mine()->get());
        // page
        session(['checklist_id' => $this->list->id]);
        $this->get('/stats')->assertOk()->assertSee('Statistics')->assertSee('7,50 €')->assertSee('B (untracked)');
        \Livewire\Livewire::test(\Alle80\Devboard\Livewire\StatsPage::class)->assertSee('History — 2 completed tasks')->call('setDays', 7)->assertSet('days', 7)->call('setDays', 99)->assertSet('days', 30);
        // empty state
        $empty = Checklist::create(['name' => 'empty', 'user_id' => auth()->id()]);
        \Livewire\Livewire::test(\Alle80\Devboard\Livewire\StatsPage::class)->call('setList', $empty->id)->assertSee('No completed task in this period');
        $this->assertSame(2, $overview->firstWhere('list.id', $this->list->id)['agg']['count']);
    }
}
