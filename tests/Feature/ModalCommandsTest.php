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
