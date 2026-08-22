<?php

namespace Alle80\Griglia\Tests\Feature;

use Alle80\Griglia\Agent;
use Alle80\Griglia\Livewire\IngredientModal;
use Alle80\Griglia\Livewire\TodoList;
use Alle80\Griglia\Models\Checklist;
use Alle80\Griglia\Models\Todo;
use Alle80\Griglia\Tests\TestCase;
use Livewire\Livewire;

/** Multi-agent: per-list default, per-task override, `griglia:check --agent` filter. */
class MultiAgentTest extends TestCase
{
    public function test_single_agent_by_default(): void
    {
        $this->assertFalse(Agent::many());
        $this->assertSame(['agent' => 'Agent'], Agent::all());
        $this->assertSame('agent', Agent::defaultKey());
    }

    public function test_assignment_and_check_filter(): void
    {
        config(['griglia.agents' => 'claude:Claude Code,codex:Codex CLI']);
        $this->assertTrue(Agent::many());
        $this->assertSame('claude', Agent::defaultKey());
        $this->assertSame('Codex CLI', Agent::label('codex'));

        $user = $this->actingAsUser();
        $dev = Checklist::create(['name' => 'dev', 'user_id' => $user->id]);
        session(['checklist_id' => $dev->id]);
        $a = Todo::create(['title' => 'For claude', 'order' => 1, 'checklist_id' => $dev->id, 'open_to_work' => true]);
        $b = Todo::create(['title' => 'For codex', 'order' => 2, 'checklist_id' => $dev->id, 'open_to_work' => true]);

        // task override from the modal
        Livewire::test(IngredientModal::class)->call('openFor', $b->id)->call('setAgent', 'codex');
        $this->assertSame('codex', $b->fresh()->agent);
        Livewire::test(IngredientModal::class)->call('openFor', $b->id)->call('setAgent', 'nope');
        $this->assertSame('codex', $b->fresh()->agent, 'unknown agent ignored');

        // check: default agent (claude) sees only A; codex only B
        $this->artisan('griglia:check')->expectsOutputToContain('you are «claude»')->expectsOutputToContain('For claude')->doesntExpectOutputToContain('For codex')->assertSuccessful();
        $this->artisan('griglia:check', ['--agent' => 'codex'])->expectsOutputToContain('For codex')->doesntExpectOutputToContain('For claude')->assertSuccessful();

        // list default agent: every unassigned task of the list goes to codex
        Livewire::test(TodoList::class)->call('setListAgent', 'codex')->assertDispatched('toast');
        $this->assertSame('codex', $dev->fresh()->agent);
        $this->assertSame('codex', Agent::effective($a->fresh()));
        $this->artisan('griglia:check')->doesntExpectOutputToContain('For claude')->assertSuccessful();
        $this->artisan('griglia:check', ['--agent' => 'codex'])->expectsOutputToContain('For claude')->expectsOutputToContain('{agent: codex}')->assertSuccessful();
        // --take still works across agents by id
        $this->artisan('griglia:check', ['--take' => $a->id, '--agent' => 'codex'])->expectsOutputToContain('taken in charge')->assertSuccessful();
        // back to default
        Livewire::test(TodoList::class)->call('setListAgent', '');
        $this->assertNull($dev->fresh()->agent);
    }

    /** Task 422: the row itself assigns the agent, and its name shows even when the task inherits the list's. */
    public function test_agent_select_on_the_list_row(): void
    {
        config(['griglia.agents' => 'claude:Claude Code,codex:Codex CLI']);
        $user = $this->actingAsUser();
        $dev = Checklist::create(['name' => 'dev', 'user_id' => $user->id]);
        session(['checklist_id' => $dev->id]);
        $todo = Todo::create(['title' => 'Row assignment', 'order' => 1, 'checklist_id' => $dev->id, 'open_to_work' => true]);

        // inherited: no agent on the task, but the row shows the effective one (the badge is always there),
        // on a line of its own under the title (task 427) — not squeezed between the row commands
        Livewire::test(TodoList::class)
            ->assertSee('Claude Code')
            ->assertSeeHtml('setTodoAgent('.$todo->id.', $event.target.value)')
            ->assertSeeHtml('db-agent-row');

        $css = file_get_contents(__DIR__.'/../../resources/css/griglia.css');
        $this->assertStringContainsString('field-sizing: content;', $css, 'the native select must size itself from its selected label');
        $this->assertStringContainsString('text-overflow: clip;', $css, 'the selected agent label must never be replaced by an ellipsis');
        $this->assertStringNotContainsString('.db-agent-chip { appearance: none; -webkit-appearance: none; max-width:', $css, 'the chip must not keep a fixed width');
        $this->assertStringNotContainsString('.db-agent-chip { appearance: none; -webkit-appearance: none; text-overflow: ellipsis;', $css, 'the chip must not request an ellipsis');

        Livewire::test(TodoList::class)->call('setTodoAgent', $todo->id, 'codex')->assertDispatched('toast');
        $this->assertSame('codex', $todo->fresh()->agent);

        Livewire::test(TodoList::class)->call('setTodoAgent', $todo->id, 'nope');
        $this->assertSame('codex', $todo->fresh()->agent, 'unknown agent ignored');

        // empty value = back to the list's default
        Livewire::test(TodoList::class)->call('setTodoAgent', $todo->id, '');
        $this->assertNull($todo->fresh()->agent);
        $this->assertSame('claude', Agent::effective($todo->fresh()));
    }

    public function test_a_task_assigned_to_an_unknown_agent_is_not_invisible(): void
    {
        config(['griglia.agents' => 'claude:Claude Code,codex:Codex CLI']);
        $list = Checklist::create(['name' => config('griglia.agent_list', 'dev'), 'user_id' => auth()->id()]);
        $todo = $list->todos()->create(['title' => 'Left behind', 'order' => 1, 'open_to_work' => true, 'agent' => 'gone']);

        // «gone» is not configured any more: the task must fall back to the default agent instead of
        // belonging to nobody and waiting forever (task 347).
        $this->assertSame(Agent::defaultKey(), Agent::effective($todo->fresh()));

        $this->artisan('griglia:check', ['--agent' => Agent::defaultKey()])
            ->expectsOutputToContain('Left behind')
            ->assertSuccessful();
    }
}
