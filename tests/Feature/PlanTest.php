<?php

namespace Alle80\Griglia\Tests\Feature;

use Alle80\Griglia\Livewire\ChecklistSwitcher;
use Alle80\Griglia\Models\Checklist;
use Alle80\Griglia\Models\Todo;
use Alle80\Griglia\Support\Plan;
use Alle80\Griglia\Tests\TestCase;
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
        $list = Checklist::create(['name' => 'dev', 'user_id' => auth()->id()]); // the agent list, so griglia:check sees it
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
        $this->artisan('griglia:check', ['--done' => $b->id])->assertSuccessful();
        $this->assertTrue($c->fresh()->open_to_work);
        $this->artisan('griglia:check')->expectsOutputToContain('⛓ plan chain: after «Build the API»')->assertSuccessful();
    }

    public function test_start_plan_from_the_list(): void
    {
        Plan::$resolver = fn () => [['title' => 'A'], ['title' => 'B']];
        $list = Checklist::create(['name' => 'Plan', 'user_id' => auth()->id()]);
        Plan::build($list, 'Go');
        session(['checklist_id' => $list->id]);
        [$a, $b] = $list->todos()->orderBy('order')->get()->all();

        $c = Livewire::test(\Alle80\Griglia\Livewire\TodoList::class)->assertSee('Start the plan');
        $c->call('startPlan')->assertDispatched('toast');
        $this->assertTrue($a->fresh()->open_to_work);
        $this->assertFalse($b->fresh()->open_to_work);
        Livewire::test(\Alle80\Griglia\Livewire\TodoList::class)->assertSee('in progress')->assertDontSee('Start the plan');

        // A done → B opens by the chain; nothing to start
        $a->update(['open_to_work' => false, 'completed' => true]);
        $this->assertTrue($b->fresh()->open_to_work);
        // user stops B → «Resume the plan»
        $b->fresh()->update(['open_to_work' => false]);
        Livewire::test(\Alle80\Griglia\Livewire\TodoList::class)->assertSee('Resume the plan')->call('startPlan');
        $this->assertTrue($b->fresh()->open_to_work);
        $b->fresh()->update(['open_to_work' => false, 'completed' => true]);
        Livewire::test(\Alle80\Griglia\Livewire\TodoList::class)->assertSee('plan completed');
    }

    public function test_check_includes_plan_lists_after_the_agent_list(): void
    {
        Plan::$resolver = fn () => [['title' => 'Plan step 1'], ['title' => 'Plan step 2']];
        $dev = Checklist::create(['name' => 'dev', 'user_id' => auth()->id()]);
        Todo::create(['title' => 'Dev task', 'order' => 1, 'checklist_id' => $dev->id, 'open_to_work' => true]);
        $plan = Checklist::create(['name' => 'Roadmap', 'user_id' => auth()->id()]);
        Plan::build($plan, 'Go');
        $first = $plan->todos()->orderBy('order')->first();

        // plan not started → not listed
        $this->artisan('griglia:check')->expectsOutputToContain('Dev task')->doesntExpectOutputToContain('Plan step 1')->assertSuccessful();
        $first->update(['open_to_work' => true]);
        $this->artisan('griglia:check')->expectsOutputToContain('Plan «Roadmap»')->expectsOutputToContain('Plan step 1')->doesntExpectOutputToContain('Plan step 2')->assertSuccessful();

        // take / done work on plan todos too; the chain opens step 2
        $this->artisan('griglia:check', ['--take' => $first->id])->expectsOutputToContain('taken in charge')->assertSuccessful();
        $this->artisan('griglia:check', ['--done' => $first->id, '--comment' => 'ok'])->assertSuccessful();
        $second = $plan->todos()->orderBy('order')->skip(1)->first();
        $this->assertTrue($second->fresh()->open_to_work);
        $this->artisan('griglia:check')->expectsOutputToContain('Plan step 2')->assertSuccessful();
    }

    public function test_new_tasks_join_the_chain_of_a_plan_list_and_the_plan_can_resume(): void
    {
        Plan::$resolver = fn () => [['title' => 'A'], ['title' => 'B']];
        $list = Checklist::create(['name' => 'Plan', 'user_id' => auth()->id()]);
        Plan::build($list, 'Go');
        session(['checklist_id' => $list->id]);
        [$a, $b] = $list->todos()->orderBy('order')->get()->all();
        $a->update(['completed' => true]);
        $b->fresh()->update(['open_to_work' => false, 'completed' => true]);
        Livewire::test(\Alle80\Griglia\Livewire\TodoList::class)->assertSee('plan completed');

        // user adds a task at the end (via the modal «new task») → chained to B, plan resumable
        Livewire::test(\Alle80\Griglia\Livewire\IngredientModal::class)->call('createNew');
        $c = $list->todos()->orderByDesc('order')->first();
        $c->update(['title' => 'C']);
        $this->assertSame($b->id, $c->fresh()->depends_on_id, 'new task joins the chain');
        $this->assertFalse($c->fresh()->open_to_work);
        Livewire::test(\Alle80\Griglia\Livewire\TodoList::class)->assertSee('Resume the plan')->call('startPlan');
        $this->assertTrue($c->fresh()->open_to_work);

        // a normal list does not chain
        $plain = Checklist::create(['name' => 'plain', 'user_id' => auth()->id()]);
        Todo::create(['title' => 'x', 'order' => 1, 'checklist_id' => $plain->id]);
        $y = Todo::create(['title' => 'y', 'order' => 2, 'checklist_id' => $plain->id]);
        $this->assertNull($y->depends_on_id);
    }

    public function test_pause_and_resume_the_plan(): void
    {
        Plan::$resolver = fn () => [['title' => 'A'], ['title' => 'B'], ['title' => 'C']];
        $list = Checklist::create(['name' => 'Plan', 'user_id' => auth()->id()]);
        Plan::build($list, 'Go');
        session(['checklist_id' => $list->id]);
        [$a, $b, $c] = $list->todos()->orderBy('order')->get()->all();
        $cmp = Livewire::test(\Alle80\Griglia\Livewire\TodoList::class);
        $cmp->call('startPlan');
        $this->assertTrue($a->fresh()->open_to_work);
        $cmp->assertSee('Pause the plan');

        $cmp->call('pausePlan')->assertDispatched('toast');
        $this->assertTrue($list->fresh()->plan_paused);
        $this->assertFalse($a->fresh()->open_to_work, 'open task back to waiting');
        Livewire::test(\Alle80\Griglia\Livewire\TodoList::class)->assertSee('paused')->assertSee('Resume the plan');

        // completing while paused does not open the next one
        $a->fresh()->update(['completed' => true]);
        $this->assertFalse($b->fresh()->open_to_work);

        Livewire::test(\Alle80\Griglia\Livewire\TodoList::class)->call('startPlan');
        $this->assertFalse($list->fresh()->plan_paused);
        $this->assertTrue($b->fresh()->open_to_work, 'resume opens the next not-started task');
        $b->fresh()->update(['open_to_work' => false, 'completed' => true]);
        $this->assertTrue($c->fresh()->open_to_work, 'chain works again');
    }

    public function test_drag_and_drop_reorders_the_chain_of_a_plan(): void
    {
        Plan::$resolver = fn () => [['title' => 'A'], ['title' => 'B'], ['title' => 'C']];
        $list = Checklist::create(['name' => 'Plan', 'user_id' => auth()->id()]);
        Plan::build($list, 'Go');
        session(['checklist_id' => $list->id]);
        [$a, $b, $c] = $list->todos()->orderBy('order')->get()->all();

        // user drags C to the top: C, A, B
        Livewire::test(\Alle80\Griglia\Livewire\TodoList::class)->call('reorder', [$c->id, $a->id, $b->id]);
        $this->assertNull($c->fresh()->depends_on_id);
        $this->assertSame($c->id, $a->fresh()->depends_on_id);
        $this->assertSame($a->id, $b->fresh()->depends_on_id);

        // start → C opens first; completing C opens A
        Livewire::test(\Alle80\Griglia\Livewire\TodoList::class)->call('startPlan');
        $this->assertTrue($c->fresh()->open_to_work);
        $c->fresh()->update(['open_to_work' => false, 'completed' => true]);
        $this->assertTrue($a->fresh()->open_to_work);

        // a plain list is not chained by reorder
        $plain = Checklist::create(['name' => 'plain', 'user_id' => auth()->id()]);
        $x = Todo::create(['title' => 'x', 'order' => 1, 'checklist_id' => $plain->id]);
        $y = Todo::create(['title' => 'y', 'order' => 2, 'checklist_id' => $plain->id]);
        session(['checklist_id' => $plain->id]);
        Livewire::test(\Alle80\Griglia\Livewire\TodoList::class)->call('reorder', [$y->id, $x->id]);
        $this->assertNull($x->fresh()->depends_on_id);
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
