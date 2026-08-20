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

        Livewire::test(TodoList::class)->call('startEdit', $todo->id)->set('titleDraft', 'Buy oat milk')->call('saveEdit');
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
}
