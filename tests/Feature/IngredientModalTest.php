<?php

namespace Alle80\Griglia\Tests\Feature;

use Alle80\Griglia\Livewire\IngredientModal;
use Alle80\Griglia\Models\Checklist;
use Alle80\Griglia\Models\Todo;
use Alle80\Griglia\Tests\TestCase;
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

        $m->call('editNotes')->set('notesDraft', "line 1\nline 2")->call('finishNotes');
        $this->assertSame("line 1\nline 2", $this->todo->fresh()->notes);

        $m->call('editTitle')->set('titleDraft', 'Renamed')->call('finishTitle');
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

    public function test_working_todo_is_read_only_until_stopped(): void
    {
        $this->todo->update(['working' => true, 'notes' => 'keep']);
        $m = Livewire::test(IngredientModal::class)->call('openFor', $this->todo->id);
        $m->assertSee('read-only')->call('editTitle')->call('editNotes')->set('newIngredient', 'x')->call('addIngredient');
        $this->assertNull($m->get('titleDraft'));
        $this->assertNull($m->get('notesDraft'));
        $this->assertSame(0, $this->todo->ingredients()->count());

        $m->call('setState', 'waiting')->call('editTitle')->set('titleDraft', 'Editable')->call('finishTitle');
        $this->assertSame('Editable', $this->todo->fresh()->title);
    }

    public function test_questions_flow(): void
    {
        $q = $this->todo->questions()->create(['question' => 'Which colour?', 'choices' => ['Blue', 'Green'], 'order' => 1]);
        $this->todo->update(['question' => true]);

        $m = Livewire::test(IngredientModal::class)->call('openFor', $this->todo->id);
        $m->assertSee("Blue")->assertSee("Green")->assertSeeHtml("db-mic");
        $m->call('resumeWork'); // blocked: unanswered
        $this->assertTrue($this->todo->fresh()->question);

        $m->set("answers.{$q->id}", 'Blue')->call('saveAnswer', $q->id);
        $this->assertSame('Blue', $q->fresh()->answer);
        $m->call("selectAnswer", $q->id, "Green");
        $this->assertSame("Green", $q->fresh()->answer);

        $m->call('resumeWork');
        $this->todo->refresh();
        $this->assertFalse($this->todo->question);
        $this->assertTrue($this->todo->open_to_work);
    }

    public function test_foreign_todo_cannot_be_opened(): void
    {
        $other = \Alle80\Griglia\Tests\Support\User::create(['name' => 'O', 'email' => 'o@example.com', 'password' => 'x']);
        $foreign = Checklist::create(['name' => 'X', 'user_id' => $other->id]);
        $todo = Todo::create(['title' => 'Not mine', 'order' => 1, 'checklist_id' => $foreign->id]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        Livewire::test(IngredientModal::class)->call('openFor', $todo->id);
    }

    public function test_the_resumed_context_starts_collapsed(): void
    {
        // It is context, not the request of today: it must not push the note out of view (task 363).
        $old = Todo::create(['title' => 'Old work', 'order' => 5, 'checklist_id' => $this->todo->checklist_id, 'completed' => true, 'notes' => 'What was asked before']);
        $new = Todo::create(['title' => 'Old work', 'order' => 6, 'checklist_id' => $this->todo->checklist_id, 'parent_id' => $old->id]);

        $m = Livewire::test(IngredientModal::class)->call('openFor', $new->id);

        $m->assertSee('modal-parent', false);
        $m->assertSee('What was asked before', false);
        $m->assertDontSee('modal-parent group" open', false);
    }

    public function test_the_modal_shows_the_whole_resume_chain(): void
    {
        // A resume of a resume keeps every step of the history one tap away (task 416).
        $list = $this->todo->checklist_id;
        $first = Todo::create(['title' => 'Dark mode', 'order' => 7, 'checklist_id' => $list, 'completed' => true, 'notes' => 'The very first request', 'claude_comment' => 'The very first answer']);
        $second = Todo::create(['title' => 'Dark mode', 'order' => 8, 'checklist_id' => $list, 'completed' => true, 'parent_id' => $first->id, 'notes' => 'The second request']);
        $third = Todo::create(['title' => 'Dark mode', 'order' => 9, 'checklist_id' => $list, 'parent_id' => $second->id]);

        $m = Livewire::test(IngredientModal::class)->call('openFor', $third->id);

        $m->assertSee('The second request', false);
        $m->assertSee('The very first request', false);
        $m->assertSee('The very first answer', false);
        $m->assertSee('+1 earlier', false);
        $m->assertSee('Before that: «Dark mode»', false);
    }

    public function test_the_modal_shows_the_task_id_to_copy(): void
    {
        // Same «id:N» as in the row and in griglia:check, in the title bar next to the state badge (task 510):
        // a group of its own (.modal-cmds-id), so the phone layout can move it to the commands line.
        Livewire::test(IngredientModal::class)->call('openFor', $this->todo->id)
            ->assertSeeHtml('class="modal-cmds-id')
            ->assertSeeHtml('data-copy="'.$this->todo->id.'"')
            ->assertSeeHtml('>id:'.$this->todo->id.'</button>');
    }
}
