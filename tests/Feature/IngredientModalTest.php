<?php

namespace Alle80\Devboard\Tests\Feature;

use Alle80\Devboard\Livewire\IngredientModal;
use Alle80\Devboard\Models\Checklist;
use Alle80\Devboard\Models\Todo;
use Alle80\Devboard\Tests\TestCase;
use Livewire\Livewire;

class IngredientModalTest extends TestCase
{
    protected Todo $todo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsUser();
        $this->todo = Todo::create(['title' => 'Task', 'order' => 1, 'checklist_id' => Checklist::currentId()]);
    }

    public function test_subtasks_notes_and_title(): void
    {
        $m = Livewire::test(IngredientModal::class)->call('openFor', $this->todo->id);
        $m->set('newIngredient', 'first')->call('addIngredient');
        $this->assertSame(['first'], $this->todo->ingredients()->pluck('name')->all());

        $ing = $this->todo->ingredients()->first();
        $m->call('toggleIngredient', $ing->id);
        $this->assertTrue($ing->fresh()->checked);

        $m->call('editNotes')->set('notesDraft', "line 1\nline 2")->call('saveNotes');
        $this->assertSame("line 1\nline 2", $this->todo->fresh()->notes);

        $m->call('editTitle')->set('titleDraft', 'Renamed')->call('saveTitle');
        $this->assertSame('Renamed', $this->todo->fresh()->title);
    }

    public function test_completed_todo_is_read_only(): void
    {
        $this->todo->update(['completed' => true, 'notes' => 'keep']);
        $m = Livewire::test(IngredientModal::class)->call('openFor', $this->todo->id);
        $m->assertSee('read-only');
        $m->set('newIngredient', 'x')->call('addIngredient');
        $m->call('editNotes');
        $this->assertNull($m->get('notesDraft'));
        $this->assertSame(0, $this->todo->ingredients()->count());
    }

    public function test_questions_flow(): void
    {
        $q = $this->todo->questions()->create(['question' => 'Which colour?', 'order' => 1]);
        $this->todo->update(['question' => true]);

        $m = Livewire::test(IngredientModal::class)->call('openFor', $this->todo->id);
        $m->call('resumeWork'); // blocked: unanswered
        $this->assertTrue($this->todo->fresh()->question);

        $m->set("answers.{$q->id}", 'Blue')->call('saveAnswer', $q->id);
        $this->assertSame('Blue', $q->fresh()->answer);

        $m->call('resumeWork');
        $this->todo->refresh();
        $this->assertFalse($this->todo->question);
        $this->assertTrue($this->todo->open_to_work);
    }

    public function test_foreign_todo_cannot_be_opened(): void
    {
        $other = \Alle80\Devboard\Tests\Support\User::create(['name' => 'O', 'email' => 'o@example.com', 'password' => 'x']);
        $foreign = Checklist::create(['name' => 'X', 'user_id' => $other->id]);
        $todo = Todo::create(['title' => 'Not mine', 'order' => 1, 'checklist_id' => $foreign->id]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        Livewire::test(IngredientModal::class)->call('openFor', $todo->id);
    }
}
