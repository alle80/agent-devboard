<?php

namespace Alle80\Devboard\Tests\Feature;

use Alle80\Devboard\Livewire\TodoList;
use Alle80\Devboard\Models\Checklist;
use Alle80\Devboard\Models\Todo;
use Alle80\Devboard\Tests\TestCase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

/** Per-todo statistics: agent working time (🔧 intervals) and tokens reported via devboard:check. */
class TodoStatsTest extends TestCase
{
    protected Todo $todo;

    protected function setUp(): void
    {
        parent::setUp();
        $user = $this->actingAsUser();
        $list = Checklist::create(['name' => 'dev', 'user_id' => $user->id]);
        session(['checklist_id' => $list->id]);
        $this->todo = Todo::create(['title' => 'Stats', 'order' => 1, 'checklist_id' => $list->id, 'open_to_work' => true]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_working_time_accumulates_over_intervals_and_tokens_add_up(): void
    {
        Carbon::setTestNow('2026-08-19 10:00:00');
        $this->artisan('devboard:check', ['--take' => $this->todo->id])->assertSuccessful();
        $this->todo->refresh();
        $this->assertNotNull($this->todo->working_since);
        $this->assertSame(0, $this->todo->work_seconds);

        Carbon::setTestNow('2026-08-19 10:05:00');
        $this->assertSame(300, $this->todo->workSeconds(), 'open interval counts live');
        $this->artisan('devboard:check')->expectsOutputToContain('⏱ working since 2026-08-19T10:00:00+00:00 (5m 00s this interval)')->assertSuccessful();

        // A question closes the interval (the user's thinking time is not agent time)
        $this->artisan('devboard:check', ['--ask' => $this->todo->id, '--q' => ['Which?'], '--tokens-in' => 1200, '--tokens-out' => 300])->assertSuccessful();
        $this->todo->refresh();
        $this->assertSame(300, $this->todo->work_seconds);
        $this->assertNull($this->todo->working_since);
        $this->assertSame(1200, $this->todo->tokens_in);
        $this->assertSame(300, $this->todo->tokens_out);

        Carbon::setTestNow('2026-08-19 11:00:00');
        $this->todo->update(['question' => false, 'open_to_work' => true]);
        $this->artisan('devboard:check', ['--take' => $this->todo->id])->assertSuccessful();
        Carbon::setTestNow('2026-08-19 11:02:30');
        $this->artisan('devboard:check', ['--done' => $this->todo->id, '--comment' => 'ok', '--tokens-in' => 800, '--tokens-out' => 200])
            ->expectsOutputToContain('📊 ⏱ 7m 30s · 🪙 2k in / 500 out')
            ->assertSuccessful();
        $this->todo->refresh();
        $this->assertSame(450, $this->todo->work_seconds);
        $this->assertSame(2000, $this->todo->tokens_in);
        $this->assertSame(500, $this->todo->tokens_out);
        $this->assertTrue($this->todo->hasStats());
        $this->assertSame('⏱ 7m 30s · 🪙 2k in / 500 out', $this->todo->statsLine());
        $this->artisan('devboard:check', ['--all' => true])->expectsOutputToContain('📊 ⏱ 7m 30s')->assertSuccessful();
    }

    public function test_user_stop_from_the_web_closes_the_interval(): void
    {
        Carbon::setTestNow('2026-08-19 10:00:00');
        $this->artisan('devboard:check', ['--take' => $this->todo->id])->assertSuccessful();
        Carbon::setTestNow('2026-08-19 10:00:42');
        Livewire::test(TodoList::class)->call('toggleOpenToWork', $this->todo->id);
        $this->todo->refresh();
        $this->assertFalse($this->todo->working);
        $this->assertNotNull($this->todo->stopped_at);
        $this->assertSame(42, $this->todo->work_seconds);
        $this->assertNull($this->todo->working_since);
    }

    public function test_formatters(): void
    {
        $this->assertSame('12s', Todo::formatDuration(12));
        $this->assertSame('4m 05s', Todo::formatDuration(245));
        $this->assertSame('1h 12m', Todo::formatDuration(4320));
        $this->assertSame('812', Todo::formatTokens(812));
        $this->assertSame('45k', Todo::formatTokens(45000));
        $this->assertSame('1.2M', Todo::formatTokens(1234567));
        $this->assertFalse($this->todo->hasStats());
    }
}
