<?php

namespace Alle80\Griglia\Tests\Feature;

use Alle80\Griglia\Livewire\TodoList;
use Alle80\Griglia\Models\Checklist;
use Alle80\Griglia\Models\Todo;
use Alle80\Griglia\Tests\TestCase;
use Livewire\Livewire;

class TodoListComponentTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsUser();
    }

    protected function add(string $title, int $pos = 1): Todo
    {
        Livewire::test(TodoList::class)->call('startInsert', $pos)->set('newTitle', $title)->call('saveInsert');

        return Todo::where('title', $title)->firstOrFail();
    }

    public function test_add_toggle_rename_and_delete(): void
    {
        $todo = $this->add('Buy milk');
        $this->assertSame(1, $todo->order);

        Livewire::test(TodoList::class)->call('toggle', $todo->id);
        $this->assertTrue($todo->fresh()->completed);

        Livewire::test(TodoList::class)->call('startEdit', $todo->id)->set('titleDraft', 'Buy oat milk')->call('finishEdit');
        $this->assertSame('Buy oat milk', $todo->fresh()->title);

        Livewire::test(TodoList::class)->call('delete', $todo->id);
        $this->assertSoftDeleted('todos', ['id' => $todo->id]); // soft: statistics survive (task 298)
        $this->assertSame(0, Todo::count(), 'gone from the board scope');
    }

    public function test_result_summary_is_shown_below_the_title(): void
    {
        $todo = $this->add('Repeated task');
        $todo->update(['completed' => true, 'claude_comment' => 'Detailed result', 'result_summary' => 'Implemented compact labels']);

        Livewire::test(TodoList::class)->assertSee('Implemented compact labels');
    }

    public function test_completed_task_uses_the_done_state_icon(): void
    {
        $todo = $this->add('Finished task');
        $todo->update(['completed' => true, 'open_to_work' => true]);
        Livewire::test(TodoList::class)
            ->assertSee('title="Completed task"', false)            ->assertSee('class="todo-action db-badge db-badge-done', false);
    }
    public function test_working_task_cannot_be_renamed_completed_archived_or_deleted(): void
    {
        $todo = $this->add('Agent owns this');
        $todo->update(['working' => true]);
        Livewire::test(TodoList::class)->call('startEdit', $todo->id)->assertSet('editingId', null)
            ->call('toggle', $todo->id)->call('archive', $todo->id)->call('delete', $todo->id);
        $todo->refresh();
        $this->assertFalse($todo->completed);
        $this->assertNull($todo->archived_at);
        $this->assertNull($todo->deleted_at);
        config(["griglia.agents" => "claude:Claude Code,codex:Codex CLI"]);
        Livewire::test(TodoList::class)
            ->assertSee("Claude Code")
            ->assertDontSeeHtml("setTodoAgent(".$todo->id);
    }

    public function test_title_length_is_enforced(): void
    {
        Livewire::test(TodoList::class)->call('startInsert', 1)->set('newTitle', str_repeat('x', 51))->call('saveInsert')
            ->assertDispatched('toast');
        $this->assertDatabaseCount('todos', 0);

        $this->add(str_repeat('y', 50));
        $this->assertDatabaseCount('todos', 1);
    }

    public function test_archive_compacts_order_and_unarchive_appends(): void
    {
        $a = $this->add('A', 1);
        $b = $this->add('B', 2);
        $c = $this->add('C', 3);

        Livewire::test(TodoList::class)->call('archive', $a->id);
        $this->assertNotNull($a->fresh()->archived_at);
        $this->assertSame([1, 2], [$b->fresh()->order, $c->fresh()->order]);

        Livewire::test(TodoList::class)->call('unarchive', $a->id);
        $this->assertNull($a->fresh()->archived_at);
        $this->assertSame(3, $a->fresh()->order);
    }

    public function test_search_and_filters(): void
    {
        $milk = $this->add('Buy milk', 1);
        $this->add('Call mom', 2);
        $milk->update(['completed' => true]);

        $t = Livewire::test(TodoList::class)->set('search', 'milk');
        $this->assertCount(1, $t->viewData('todos'));

        $t = Livewire::test(TodoList::class)->call('setFilter', 'done');
        $this->assertSame(['Buy milk'], $t->viewData('todos')->pluck('title')->all());

        $t = Livewire::test(TodoList::class)->call('setFilter', 'todo');
        $this->assertSame(['Call mom'], $t->viewData('todos')->pluck('title')->all());
    }

    public function test_search_can_include_all_owned_active_lists(): void
    {
        $this->add('Current needle');
        $other = Checklist::create(['name' => 'Other project', 'user_id' => auth()->id()]);
        Todo::create(['title' => 'Other needle', 'order' => 1, 'checklist_id' => $other->id]);
        $archived = Checklist::create(['name' => 'Old project', 'user_id' => auth()->id(), 'archived_at' => now()]);
        Todo::create(['title' => 'Archived needle', 'order' => 1, 'checklist_id' => $archived->id]);
        $foreignUser = \Alle80\Griglia\Tests\Support\User::create(['name' => 'O', 'email' => 'other-search@example.com', 'password' => 'x']);
        $foreign = Checklist::create(['name' => 'Foreign', 'user_id' => $foreignUser->id]);
        Todo::create(['title' => 'Foreign needle', 'order' => 1, 'checklist_id' => $foreign->id]);

        $component = Livewire::test(TodoList::class)->set('search', 'needle');
        $this->assertSame(['Current needle'], $component->viewData('todos')->pluck('title')->all());

        $component->call('toggleSearchScope');
        $this->assertEqualsCanonicalizing(
            ['Current needle', 'Other needle'],
            $component->viewData('todos')->pluck('title')->all(),
        );
        $component->assertSee('Other project');
    }

    public function test_filter_by_effective_agent(): void
    {
        config(['griglia.agents' => 'claude:Claude Code,codex:Codex CLI', 'griglia.agent_key' => 'claude']);
        Checklist::findOrFail(Checklist::currentId())->update(['agent' => 'codex']);
        $inherited = $this->add('Inherited Codex', 1);
        $explicitClaude = $this->add('Explicit Claude', 2);
        $explicitClaude->update(['agent' => 'claude']);

        $other = Checklist::create(['name' => 'Default list', 'user_id' => auth()->id()]);
        Todo::create(['title' => 'Global Claude', 'order' => 1, 'checklist_id' => $other->id]);

        $component = Livewire::test(TodoList::class)->assertSee('All agents')->call('setAgentFilter', 'codex');
        $this->assertSame(['Inherited Codex'], $component->viewData('todos')->pluck('title')->all());
        $component->assertSet('agentFilter', 'codex');
        $this->assertTrue($component->viewData('filtering'), 'the agent filter alone counts as filtering');

        // …so drag & drop is refused while it is on, like with the state filters and the search
        $component->call('reorder', [$explicitClaude->id, $inherited->id]);
        $this->assertSame(1, $inherited->fresh()->order);

        $component->set('search', 'Claude')->call('toggleSearchScope')->call('setAgentFilter', 'claude');
        $this->assertEqualsCanonicalizing(
            ['Explicit Claude', 'Global Claude'],
            $component->viewData('todos')->pluck('title')->all(),
        );

        $component->call('setAgentFilter', 'unknown')->assertSet('agentFilter', '');
        $this->assertTrue($component->viewData('filtering'), 'the active search still counts as filtering');
    }

    public function test_agent_filter_is_hidden_with_a_single_agent(): void
    {
        $this->add('Only one agent here');
        Livewire::test(TodoList::class)->assertDontSee('All agents')
            ->call('setAgentFilter', 'nobody')->assertSet('agentFilter', '');
    }

    public function test_open_to_work_stop_and_resume(): void
    {
        $todo = $this->add('Task');

        Livewire::test(TodoList::class)->call('toggleOpenToWork', $todo->id);
        $this->assertTrue($todo->fresh()->open_to_work);

        $todo->update(['working' => true]);
        Livewire::test(TodoList::class)->call('toggleOpenToWork', $todo->id); // stops the agent
        $todo->refresh();
        $this->assertFalse($todo->working);
        $this->assertFalse($todo->open_to_work);
        $this->assertNotNull($todo->stopped_at);

        $todo->update(['completed' => true, 'notes' => 'old note']);
        Livewire::test(TodoList::class)->call('resume', $todo->id)->assertDispatched('open-ingredients');
        $new = Todo::where('parent_id', $todo->id)->firstOrFail();
        $this->assertSame('Task', $new->title);
        $this->assertSame($todo->order + 1, $new->order);
        $this->assertFalse($new->completed);
    }

    public function test_todos_of_other_users_are_invisible(): void
    {
        $this->add('Mine');
        $other = \Alle80\Griglia\Tests\Support\User::create(['name' => 'O', 'email' => 'o@example.com', 'password' => 'x']);
        $foreign = Checklist::create(['name' => 'X', 'user_id' => $other->id]);
        Todo::create(['title' => 'Not mine', 'order' => 1, 'checklist_id' => $foreign->id]);

        $t = Livewire::test(TodoList::class);
        $this->assertSame(['Mine'], $t->viewData('todos')->pluck('title')->all());
    }

    public function test_a_closed_task_cannot_be_reopened_from_the_board(): void
    {
        // Done is done: continuing means a new task made with «resume» (task 348).
        $todo = $this->add('Already answered');
        Livewire::test(TodoList::class)->call('toggle', $todo->id);
        $this->assertTrue($todo->fresh()->completed);

        Livewire::test(TodoList::class)->call('toggle', $todo->id)->assertDispatched('toast');
        $this->assertTrue($todo->fresh()->completed, 'the checkbox does not reopen it');

        Livewire::test(TodoList::class)->call('toggleOpenToWork', $todo->id);
        $todo->refresh();
        $this->assertTrue($todo->completed);
        $this->assertFalse($todo->open_to_work, 'and the dot does not put it back to work');

        // The way to carry on: resume creates a new task linked to it.
        Livewire::test(TodoList::class)->call('resume', $todo->id);
        $new = Todo::where('parent_id', $todo->id)->first();
        $this->assertNotNull($new);
        $this->assertFalse($new->completed);
    }

    public function test_deleting_a_task_hands_its_resume_chain_to_the_task_before(): void
    {
        // The history must survive a task leaving the board: the child is re-linked to the grandparent (task 416).
        $first = $this->add('Dark mode');
        Livewire::test(TodoList::class)->call('toggle', $first->id)->call('resume', $first->id);
        $second = Todo::where('parent_id', $first->id)->firstOrFail();
        Livewire::test(TodoList::class)->call('toggle', $second->id)->call('resume', $second->id);
        $third = Todo::where('parent_id', $second->id)->firstOrFail();

        Livewire::test(TodoList::class)->call('delete', $second->id);

        $this->assertSame($first->id, $third->fresh()->parent_id, 'the chain skips the deleted step instead of breaking');
        $this->assertSame([$first->id], $third->fresh()->resumeChain()->pluck('id')->all());
    }

    public function test_the_row_shows_the_task_id_to_copy(): void
    {
        // The same «id:N» the agent prints in griglia:check, next to the state dot; one tap copies the number (task 510).
        $todo = $this->add('Find me by id');

        Livewire::test(TodoList::class)
            ->assertSeeHtml('data-copy="'.$todo->id.'"')
            ->assertSeeHtml('>id:'.$todo->id.'</button>');
    }
}
