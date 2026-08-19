<?php

namespace Alle80\Devboard\Tests\Feature;

use Alle80\Devboard\Livewire\IngredientModal;
use Alle80\Devboard\Livewire\TodoList;
use Alle80\Devboard\Models\Checklist;
use Alle80\Devboard\Models\Todo;
use Alle80\Devboard\Tests\TestCase;
use Livewire\Livewire;

class ModalCommandsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $user = $this->actingAsUser();
        $this->list = Checklist::create(['name' => 'dev', 'user_id' => $user->id]);
    }

    public function test_create_new_makes_a_blank_todo_and_opens_the_modal(): void
    {
        $modal = Livewire::test(IngredientModal::class)
            ->call('createNew')
            ->assertDispatched('ingredients-updated');

        $todo = Todo::latest('id')->first();
        $this->assertSame('', $todo->title);
        $this->assertSame($this->list->id, $todo->checklist_id);
        $this->assertTrue($modal->instance()->open, 'the modal opens for the new task');
    }

    public function test_create_new_at_a_position_makes_room_like_the_plus_between_rows(): void
    {
        $a = Todo::create(['title' => 'A', 'order' => 1, 'checklist_id' => $this->list->id]);
        $b = Todo::create(['title' => 'B', 'order' => 2, 'checklist_id' => $this->list->id]);

        $modal = Livewire::test(IngredientModal::class)->call('createNew', 2);
        $new = Todo::latest('id')->first();
        $this->assertSame('', $new->title);
        $this->assertSame(2, $new->order, 'inserted before B');
        $this->assertSame(1, $a->fresh()->order);
        $this->assertSame(3, $b->fresh()->order, 'B moved down');
        $this->assertTrue($modal->instance()->open);

        // Out-of-range position → appended at the end
        Livewire::test(IngredientModal::class)->call('createNew', 99);
        $this->assertSame(4, Todo::latest('id')->first()->order);
    }

    public function test_set_state_from_the_badge_menu(): void
    {
        $todo = Todo::create(['title' => 'S', 'order' => 1, 'checklist_id' => $this->list->id, 'working' => true, 'progress' => 40]);
        $modal = Livewire::test(IngredientModal::class)->call('openFor', $todo->id);

        $modal->call('setState', 'done')->assertDispatched('toast');
        $todo->refresh();
        $this->assertTrue($todo->completed);
        $this->assertFalse($todo->working);
        $this->assertNotNull($todo->stopped_at, 'choosing a state while the agent works = stop');
        $this->assertNull($todo->progress);
        $this->assertSame('done', $modal->instance()->stateKey());

        $modal->call('setState', 'open');
        $todo->refresh();
        $this->assertFalse($todo->completed);
        $this->assertTrue($todo->open_to_work);
        $this->assertNull($todo->stopped_at);

        $modal->call('setState', 'waiting');
        $todo->refresh();
        $this->assertFalse($todo->open_to_work);
        $this->assertFalse($todo->completed);

        $modal->call('setState', 'working'); // not settable by the user
        $this->assertFalse($todo->fresh()->working);
    }

    public function test_move_to_another_list(): void
    {
        $a = Todo::create(['title' => 'A', 'order' => 1, 'checklist_id' => $this->list->id]);
        $b = Todo::create(['title' => 'B', 'order' => 2, 'checklist_id' => $this->list->id]);
        $other = Checklist::create(['name' => 'other', 'user_id' => $this->list->user_id]);
        Todo::create(['title' => 'X', 'order' => 1, 'checklist_id' => $other->id]);
        $foreign = Checklist::create(['name' => 'foreign', 'user_id' => \Alle80\Devboard\Tests\Support\User::create(['name' => 'B', 'email' => 'b@x.it', 'password' => bcrypt('s')])->id]);

        $modal = Livewire::test(IngredientModal::class)->call('openFor', $a->id)->assertSee('other')->assertDontSee('foreign');
        $modal->call('moveTo', $foreign->id);
        $this->assertSame($this->list->id, $a->fresh()->checklist_id, 'not my list → ignored');

        $modal->call('moveTo', $other->id)->assertDispatched('toast');
        $a->refresh();
        $this->assertSame($other->id, $a->checklist_id);
        $this->assertSame(2, $a->order, 'appended after X');
        $this->assertSame(1, $b->fresh()->order, 'gap closed in the source list');
        $this->assertFalse($modal->instance()->open);
    }

    public function test_state_key_reflects_the_todo(): void
    {
        $todo = Todo::create(['title' => 'X', 'order' => 1, 'checklist_id' => $this->list->id, 'open_to_work' => true]);

        $modal = Livewire::test(IngredientModal::class)->call('openFor', $todo->id);
        $this->assertSame('open', $modal->instance()->stateKey());
    }

    public function test_modal_toggle_open_to_work(): void
    {
        $todo = Todo::create(['title' => 'X', 'order' => 1, 'checklist_id' => $this->list->id]);

        Livewire::test(IngredientModal::class)
            ->call('openFor', $todo->id)
            ->call('toggleOpenToWork')
            ->assertDispatched('ingredients-updated');

        $this->assertTrue($todo->fresh()->open_to_work);
    }

    public function test_closing_a_blank_untouched_new_task_deletes_it(): void
    {
        $todo = Todo::create(['title' => '', 'order' => 1, 'checklist_id' => $this->list->id]);

        Livewire::test(IngredientModal::class)
            ->call('openFor', $todo->id)
            ->call('close');

        $this->assertNull($todo->fresh());
    }
}
