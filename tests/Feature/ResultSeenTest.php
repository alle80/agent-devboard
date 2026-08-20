<?php

namespace Alle80\Griglia\Tests\Feature;

use Alle80\Griglia\Livewire\IngredientModal;
use Alle80\Griglia\Models\Checklist;
use Alle80\Griglia\Models\Todo;
use Alle80\Griglia\Tests\TestCase;
use Livewire\Livewire;

class ResultSeenTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $user = $this->actingAsUser();
        $this->list = Checklist::create(['name' => 'dev', 'user_id' => $user->id]);
    }

    public function test_agent_done_marks_result_unseen(): void
    {
        $todo = Todo::create(['title' => 'X', 'order' => 1, 'checklist_id' => $this->list->id, 'open_to_work' => true]);

        $this->artisan('griglia:check', ['--done' => $todo->id, '--comment' => 'Shipped'])->assertSuccessful();

        $todo->refresh();
        $this->assertTrue($todo->completed);
        $this->assertFalse($todo->result_seen, 'a fresh agent completion is unseen');
    }

    public function test_opening_a_completed_task_marks_it_seen(): void
    {
        $todo = Todo::create(['title' => 'X', 'order' => 1, 'checklist_id' => $this->list->id, 'completed' => true, 'result_seen' => false]);

        Livewire::test(IngredientModal::class)->call('openFor', $todo->id);

        $this->assertTrue($todo->fresh()->result_seen);
    }

    public function test_new_todos_default_to_seen(): void
    {
        $todo = Todo::create(['title' => 'X', 'order' => 1, 'checklist_id' => $this->list->id]);
        $this->assertTrue($todo->fresh()->result_seen);
    }
}
