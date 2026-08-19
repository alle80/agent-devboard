<?php

namespace Alle80\Devboard\Tests\Feature;

use Alle80\Devboard\Livewire\ChecklistSwitcher;
use Alle80\Devboard\Models\Checklist;
use Alle80\Devboard\Models\Todo;
use Alle80\Devboard\Support\Plan;
use Alle80\Devboard\Tests\TestCase;
use Livewire\Livewire;

/** Plan mode: a list built from a prompt into chained tasks; the chain opens the next task on completion. */
class PlanTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsUser();
    }

    protected function tearDown(): void
    {
        Plan::$resolver = null;
        parent::tearDown();
    }

    public function test_build_creates_chained_tasks_and_the_chain_opens_the_next_one(): void
    {
        Plan::$resolver = fn (string $prompt) => [
            ['title' => 'Set up the repo', 'notes' => 'Init', 'subtasks' => ['git init', 'CI']],
            ['title' => 'Build the API', 'notes' => '', 'subtasks' => []],
            ['title' => 'Ship it', 'notes' => 'Deploy', 'subtasks' => ['tag']],
        ];
        $this->assertTrue(Plan::available());
        $list = Checklist::create(['name' => 'dev', 'user_id' => auth()->id()]); // the agent list, so devboard:check sees it
        $this->assertSame(3, Plan::build($list, 'Make a thing'));
        $this->assertSame('Make a thing', $list->fresh()->plan_prompt);

        [$a, $b, $c] = $list->todos()->orderBy('order')->get()->all();
        $this->assertNull($a->depends_on_id);
        $this->assertSame($a->id, $b->depends_on_id);
        $this->assertSame($b->id, $c->depends_on_id);
        $this->assertSame(2, $a->ingredients()->count());
        $this->assertNull($b->notes);
        $this->assertFalse($a->open_to_work, 'the user starts the first one');

        // Completing A opens B (🟢), not C
        $a->update(['completed' => true]);
        $this->assertTrue($b->fresh()->open_to_work);
        $this->assertFalse($c->fresh()->open_to_work);
        // B done via the CLI → C opens
        $b->update(['open_to_work' => false, 'working' => true]);
        $this->artisan('devboard:check', ['--done' => $b->id])->assertSuccessful();
        $this->assertTrue($c->fresh()->open_to_work);
        $this->artisan('devboard:check')->expectsOutputToContain('⛓ plan chain: after «Build the API»')->assertSuccessful();
    }

    public function test_start_plan_from_the_list(): void
    {
        Plan::$resolver = fn () => [['title' => 'A'], ['title' => 'B']];
        $list = Checklist::create(['name' => 'Plan', 'user_id' => auth()->id()]);
        Plan::build($list, 'Go');
        session(['checklist_id' => $list->id]);
        [$a, $b] = $list->todos()->orderBy('order')->get()->all();

        $c = Livewire::test(\Alle80\Devboard\Livewire\TodoList::class)->assertSee('Start the plan');
        $c->call('startPlan')->assertDispatched('toast');
        $this->assertTrue($a->fresh()->open_to_work);
        $this->assertFalse($b->fresh()->open_to_work);
        Livewire::test(\Alle80\Devboard\Livewire\TodoList::class)->assertSee('in progress')->assertDontSee('Start the plan');

        // A done → B opens by the chain; nothing to start
        $a->update(['open_to_work' => false, 'completed' => true]);
        $this->assertTrue($b->fresh()->open_to_work);
        // user stops B → «Resume the plan»
        $b->fresh()->update(['open_to_work' => false]);
        Livewire::test(\Alle80\Devboard\Livewire\TodoList::class)->assertSee('Resume the plan')->call('startPlan');
        $this->assertTrue($b->fresh()->open_to_work);
        $b->fresh()->update(['open_to_work' => false, 'completed' => true]);
        Livewire::test(\Alle80\Devboard\Livewire\TodoList::class)->assertSee('plan completed');
    }

    public function test_fallback_without_ai_creates_a_plan_request_task(): void
    {
        $this->assertFalse(Plan::available());
        $list = Checklist::create(['name' => 'Plan', 'user_id' => auth()->id()]);
        $this->assertSame(0, Plan::build($list, 'Make a thing'));
        $t = $list->todos()->first();
        $this->assertSame('Build the plan', $t->title);
        $this->assertStringContainsString('Make a thing', $t->notes);
    }

    public function test_switcher_creates_the_list_as_a_plan(): void
    {
        Plan::$resolver = fn () => [['title' => 'One'], ['title' => 'Two']];
        Livewire::test(ChecklistSwitcher::class)->set('newName', 'Roadmap')->set('asPlan', true)->set('planPrompt', '')->call('create')->assertDispatched('toast');
        $this->assertNull(Checklist::where('name', 'Roadmap')->first(), 'prompt required');

        Livewire::test(ChecklistSwitcher::class)->set('newName', 'Roadmap')->set('asPlan', true)->set('planPrompt', 'Go')->call('create');
        $list = Checklist::where('name', 'Roadmap')->first();
        $this->assertNotNull($list);
        $this->assertSame(2, $list->todos()->count());
        $this->assertSame($list->id, session('checklist_id'));
        $this->assertSame('Go', $list->plan_prompt);
    }
}
