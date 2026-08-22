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
        $this->artisan('griglia:check', ['--done' => $this->todo->id, '--comment' => 'Shipped', '--summary' => 'Dark mode delivered'])->expectsOutputToContain('completed')->assertSuccessful();
        $this->todo->refresh();
        $this->assertTrue($this->todo->completed);
        $this->assertSame('Shipped', $this->todo->claude_comment);
        $this->assertSame('Dark mode delivered', $this->todo->result_summary);
        $another = Todo::create(['title' => 'Fallback', 'order' => 2, 'checklist_id' => $this->todo->checklist_id]);
        $this->artisan('griglia:check', ['--done' => $another->id, '--comment' => 'Automatic concise result'])->assertSuccessful();
        $this->assertSame('Automatic concise result', $another->fresh()->result_summary);
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

    public function test_worker_json_includes_scheduling_mode_and_items(): void
    {
        $settings = app(\Alle80\Griglia\Settings\AgentSettings::class);
        $settings->task_mode = 'multitasking';
        $settings->save();
        \Illuminate\Support\Facades\Artisan::call('griglia:check', ['--worker-json' => true]);

        $payload = json_decode(trim(\Illuminate\Support\Facades\Artisan::output()), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('multitasking', $payload['task_mode']);
        $this->assertSame($this->todo->id, $payload['items'][0]['id']);
    }

    public function test_taking_a_completed_task_is_refused(): void
    {
        // A closed task stays closed: to carry on there is «resume», which makes a new linked task (task 348).
        $list = Checklist::where('name', config('griglia.agent_list', 'dev'))->first()
            ?? Checklist::create(['name' => config('griglia.agent_list', 'dev'), 'user_id' => auth()->id()]);
        $todo = $list->todos()->create(['title' => 'Closed too early', 'order' => 99, 'completed' => true, 'completed_at' => now()]);

        $this->artisan('griglia:check', ['--take' => $todo->id])->assertFailed();

        $todo->refresh();
        $this->assertTrue($todo->completed, 'still closed');
        $this->assertFalse($todo->working, 'and the agent did not take it');
    }

    public function test_resume_chain_prints_the_whole_history(): void
    {
        // A resume can be resumed again: the agent must get every previous step, not just the last one (task 416).
        $list = $this->todo->checklist_id;
        $first = Todo::create(['title' => 'Dark mode', 'order' => 2, 'checklist_id' => $list, 'completed' => true, 'completed_at' => now(), 'notes' => 'first request', 'claude_comment' => 'first answer']);
        $first->ingredients()->create(['name' => 'first subtask', 'order' => 1, 'checked' => true]);
        $second = Todo::create(['title' => 'Dark mode', 'order' => 3, 'checklist_id' => $list, 'completed' => true, 'completed_at' => now(), 'parent_id' => $first->id, 'notes' => 'second request', 'claude_comment' => 'second answer']);
        $third = Todo::create(['title' => 'Dark mode', 'order' => 4, 'checklist_id' => $list, 'parent_id' => $second->id, 'open_to_work' => true, 'notes' => 'still not right']);

        $this->artisan('griglia:check')
            ->expectsOutputToContain('↩ resume chain: 2 previous tasks')
            ->expectsOutputToContain(sprintf('↩ resumes «Dark mode» (id:%d)', $second->id))
            ->expectsOutputToContain('previous note: second request')
            ->expectsOutputToContain('🤖 previous: second answer')
            ->expectsOutputToContain(sprintf('↩ 2 steps back «Dark mode» (id:%d)', $first->id))
            ->expectsOutputToContain('previous note: first request')
            ->expectsOutputToContain('🤖 previous: first answer')
            ->expectsOutputToContain('- [x] first subtask')
            ->assertSuccessful();

        // JSON output carries the same history for scripts
        \Illuminate\Support\Facades\Artisan::call('griglia:check', ['--json' => true]);
        $rows = json_decode(trim(\Illuminate\Support\Facades\Artisan::output()), true);
        $row = collect($rows)->firstWhere('id', $third->id);
        $this->assertSame([$second->id, $first->id], array_column($row['resume_chain'], 'id'));
        $this->assertSame('first answer', $row['resume_chain'][1]['claude_comment']);
        $this->assertSame([['name' => 'first subtask', 'checked' => true]], $row['resume_chain'][1]['ingredients']);
    }

    public function test_a_single_resume_step_keeps_the_short_wording(): void
    {
        $list = $this->todo->checklist_id;
        $old = Todo::create(['title' => 'Login', 'order' => 5, 'checklist_id' => $list, 'completed' => true, 'completed_at' => now(), 'notes' => 'old note']);
        Todo::create(['title' => 'Login', 'order' => 6, 'checklist_id' => $list, 'parent_id' => $old->id, 'open_to_work' => true]);

        $this->artisan('griglia:check')
            ->expectsOutputToContain(sprintf('↩ resumes «Login» (id:%d): the previous context still applies', $old->id))
            ->doesntExpectOutputToContain('resume chain:')
            ->assertSuccessful();
    }

    public function test_a_broken_chain_does_not_loop_for_ever(): void
    {
        $list = $this->todo->checklist_id;
        $a = Todo::create(['title' => 'A', 'order' => 7, 'checklist_id' => $list, 'open_to_work' => true]);
        $b = Todo::create(['title' => 'B', 'order' => 8, 'checklist_id' => $list, 'parent_id' => $a->id]);
        // Corrupt data (a cycle) must not hang the command
        $a->update(['parent_id' => $b->id]);

        $this->assertSame([$b->id], $a->fresh()->resumeChain()->pluck('id')->all(), 'the walk stops as soon as it meets a task it already saw');
        $this->artisan('griglia:check')->assertSuccessful();
    }

    public function test_taking_a_task_the_user_stopped_is_refused(): void
    {
        // The agent is working; the user clicks the 🔧 dot: the task goes back to ⚪ with stopped_at set.
        $this->artisan('griglia:check', ['--take' => $this->todo->id])->assertSuccessful();
        $this->todo->fresh()->update(['working' => false, 'open_to_work' => false, 'stopped_at' => now()]);

        // The agent, unaware of the stop, updates its progress (the documented «piggyback» pattern):
        // that must NOT silently put the task back to 🔧 and wipe the stop.
        $this->artisan('griglia:check', ['--take' => $this->todo->id, '--progress' => 50, '--phase' => 'writing code'])
            ->expectsOutputToContain('stopped')
            ->assertFailed();
        $this->todo->refresh();
        $this->assertFalse($this->todo->working, 'the stop still holds');
        $this->assertNotNull($this->todo->stopped_at, 'and its trace is kept');
        $this->assertNotSame(50, $this->todo->progress);

        // Once the user puts it back to 🟢 the agent may take it again (and the stop is cleared)
        $this->todo->update(['open_to_work' => true, 'stopped_at' => null]);
        $this->artisan('griglia:check', ['--take' => $this->todo->id, '--progress' => 50, '--phase' => 'writing code'])->assertSuccessful();
        $this->todo->refresh();
        $this->assertTrue($this->todo->working);
        $this->assertNull($this->todo->stopped_at);
        $this->assertSame(50, $this->todo->progress);

        // --force is the deliberate way past the stop (e.g. the user said «go on» in the chat)
        $this->todo->update(['working' => false, 'open_to_work' => false, 'stopped_at' => now()]);
        $this->artisan('griglia:check', ['--take' => $this->todo->id, '--force' => true])->assertSuccessful();
        $this->assertTrue($this->todo->fresh()->working);
    }

    public function test_done_leaves_no_question_or_open_to_work_flag_behind(): void
    {
        // Closed while the questions were still open: the row must read «done», not «question» for ever
        $this->artisan('griglia:check', ['--take' => $this->todo->id])->assertSuccessful();
        $this->artisan('griglia:check', ['--ask' => $this->todo->id, '--q' => ['Which shade?']])->assertSuccessful();
        $this->artisan('griglia:check', ['--done' => $this->todo->id, '--comment' => 'Picked the dark one'])->assertSuccessful();

        $this->todo->refresh();
        $this->assertTrue($this->todo->completed);
        $this->assertFalse($this->todo->question, 'done is done: no open question left on a closed task');
        $this->assertFalse($this->todo->open_to_work);
        $this->assertSame('ok', $this->todo->attention(), 'the row asks for a look at the result, not for answers');
    }
}
