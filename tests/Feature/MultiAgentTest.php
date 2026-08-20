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
}
