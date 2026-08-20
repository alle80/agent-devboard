<?php

namespace Alle80\Griglia\Tests\Feature;

use Alle80\Griglia\Models\Checklist;
use Alle80\Griglia\Models\Todo;
use Alle80\Griglia\Tests\TestCase;

class GrigliaCheckCommandTest extends TestCase
{
    protected Todo $todo;

    protected function setUp(): void
    {
        parent::setUp();
        $user = $this->actingAsUser();
        $list = Checklist::create(['name' => 'dev', 'user_id' => $user->id]);
        $this->todo = Todo::create(['title' => 'Add dark mode', 'order' => 1, 'checklist_id' => $list->id, 'notes' => 'please', 'open_to_work' => true]);
        $this->todo->ingredients()->create(['name' => 'css', 'order' => 1]);
    }

    public function test_lists_open_to_work_items_with_settings_line(): void
    {
        $this->artisan('griglia:check')
            ->expectsOutputToContain('FOLLOW THEM')
            ->expectsOutputToContain('🟢 #1 Add dark mode')
            ->expectsOutputToContain('note: please')
            ->assertSuccessful();

        // Waiting items are hidden without --all
        Todo::create(['title' => 'Later', 'order' => 2, 'checklist_id' => $this->todo->checklist_id]);
        $this->artisan('griglia:check')->doesntExpectOutputToContain('Later')->assertSuccessful();
        $this->artisan('griglia:check', ['--all' => true])->expectsOutputToContain('Later')->assertSuccessful();
    }

    public function test_take_ask_and_done(): void
    {
        $this->artisan('griglia:check', ['--take' => $this->todo->id])->expectsOutputToContain('taken in charge')->assertSuccessful();
        $this->assertTrue($this->todo->fresh()->working);

        $this->artisan('griglia:check', ['--ask' => $this->todo->id, '--q' => ['Which shade?', 'Also for the login?']])->assertSuccessful();
        $this->todo->refresh();
        $this->assertTrue($this->todo->question);
        $this->assertFalse($this->todo->working);
        $this->assertSame(2, $this->todo->questions()->count());
        // items with open questions are not listed as workable
        $this->artisan('griglia:check')->doesntExpectOutputToContain('Add dark mode')->assertSuccessful();

        $this->todo->update(['question' => false, 'open_to_work' => true]);
        $this->artisan('griglia:check', ['--done' => $this->todo->id, '--comment' => 'Shipped'])->expectsOutputToContain('completed')->assertSuccessful();
        $this->todo->refresh();
        $this->assertTrue($this->todo->completed);
        $this->assertSame('Shipped', $this->todo->claude_comment);
        $this->assertTrue($this->todo->ingredients()->first()->checked, 'sub-tasks ticked on done');
    }

    public function test_progress_starts_at_zero_on_take_and_updates(): void
    {
        $this->artisan('griglia:check', ['--take' => $this->todo->id])->expectsOutputToContain('— 0%')->assertSuccessful();
        $this->assertSame(0, $this->todo->fresh()->progress, '--take alone shows 0% (percentage always visible while working)');

        $this->artisan('griglia:check', ['--take' => $this->todo->id, '--progress' => 140])->expectsOutputToContain('— 100%')->assertSuccessful();
        $this->assertSame(100, $this->todo->fresh()->progress, 'clamped to 100');

        $this->artisan('griglia:check', ['--take' => $this->todo->id, '--progress' => 45, '--phase' => 'writing code'])->expectsOutputToContain('— 45% · writing code')->assertSuccessful();
        $this->assertSame('writing code', $this->todo->fresh()->phase);
        $this->artisan('griglia:check')->expectsOutputToContain('🔧 #1 Add dark mode [45% · writing code]')->assertSuccessful();

        // Re-taking without --progress keeps the current value
        $this->artisan('griglia:check', ['--take' => $this->todo->id])->expectsOutputToContain('— 45%')->assertSuccessful();
        $this->assertSame(45, $this->todo->fresh()->progress);

        $this->artisan('griglia:check', ['--done' => $this->todo->id])->assertSuccessful();
        $this->assertNull($this->todo->fresh()->progress, 'done clears the progress');
        $this->assertNull($this->todo->fresh()->phase, 'done clears the phase');
    }

    public function test_alias_and_missing_list(): void
    {
        $this->artisan('sviluppo:check')->assertSuccessful();
        config(['griglia.agent_list' => 'nope']);
        $this->artisan('griglia:check')->expectsOutputToContain('No list named "nope"')->assertSuccessful();
    }

    public function test_json_output_stays_machine_readable_with_a_stuck_plan(): void
    {
        // The dead-end warning must never end up in --json: scripts parse that output (task 347).
        $plan = Checklist::create(['name' => 'Stuck plan', 'user_id' => auth()->id(), 'plan_prompt' => 'Something']);
        $plan->todos()->create(['title' => 'Waiting for ever', 'order' => 1]);

        \Illuminate\Support\Facades\Artisan::call('griglia:check', ['--json' => true]);

        $out = trim(\Illuminate\Support\Facades\Artisan::output());
        $this->assertJson($out);
    }
}
