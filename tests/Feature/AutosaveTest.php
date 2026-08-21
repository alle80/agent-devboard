<?php

namespace Alle80\Griglia\Tests\Feature;

use Alle80\Griglia\Livewire\IngredientModal;
use Alle80\Griglia\Livewire\TodoList;
use Alle80\Griglia\Models\Checklist;
use Alle80\Griglia\Models\Todo;
use Alle80\Griglia\Tests\TestCase;
use Livewire\Livewire;

/**
 * Salvataggio live (task 433): titolo e nota si salvano da soli mentre si scrive, senza
 * schiacciare «Salva»; «Annulla» rimette il valore di partenza.
 */
class AutosaveTest extends TestCase
{
    protected Todo $todo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsUser();
        $this->todo = Todo::create(['title' => 'Task', 'order' => 1, 'checklist_id' => Checklist::currentId()]);
    }

    public function test_modal_title_and_notes_save_without_the_button(): void
    {
        $m = Livewire::test(IngredientModal::class)->call('openFor', $this->todo->id);

        $m->call('editTitle')->set('titleDraft', 'Live title');
        $this->assertSame('Live title', $this->todo->fresh()->title);
        $m->assertDispatched('griglia-autosaved');
        $this->assertSame('Live title', $m->get('titleDraft'), 'the field stays in edit mode');

        $m->call('editNotes')->set('notesDraft', "line 1\nline 2");
        $this->assertSame("line 1\nline 2", $this->todo->fresh()->notes);
        $this->assertNotNull($m->get('notesDraft'));

        // Il bottone chiude soltanto: quello che c'è nel campo è già salvato.
        $m->call('saveNotes')->call('saveTitle');
        $this->assertNull($m->get('notesDraft'));
        $this->assertNull($m->get('titleDraft'));
        $this->assertSame('Live title', $this->todo->fresh()->title);
    }

    public function test_cancel_puts_back_the_starting_value(): void
    {
        $this->todo->update(['notes' => 'first note']);
        $m = Livewire::test(IngredientModal::class)->call('openFor', $this->todo->id);

        $m->call('editTitle')->set('titleDraft', 'Oops')->call('cancelTitle');
        $this->assertSame('Task', $this->todo->fresh()->title);

        $m->call('editNotes')->set('notesDraft', 'oops')->call('cancelNotes');
        $this->assertSame('first note', $this->todo->fresh()->notes);
    }

    public function test_autosave_refuses_an_empty_or_too_long_title(): void
    {
        $m = Livewire::test(IngredientModal::class)->call('openFor', $this->todo->id);

        $m->call('editTitle')->set('titleDraft', '   ');
        $this->assertSame('Task', $this->todo->fresh()->title);

        $m->set('titleDraft', str_repeat('a', TodoList::titleMax() + 1));
        $this->assertSame('Task', $this->todo->fresh()->title);
        $m->assertDispatched('toast');
    }

    public function test_completed_todo_never_autosaves(): void
    {
        $this->todo->update(['completed' => true, 'notes' => 'keep']);
        $m = Livewire::test(IngredientModal::class)->call('openFor', $this->todo->id);

        $m->set('titleDraft', 'nope')->set('notesDraft', 'nope');
        $this->assertSame('Task', $this->todo->fresh()->title);
        $this->assertSame('keep', $this->todo->fresh()->notes);
    }

    public function test_inline_rename_in_the_list_saves_while_typing(): void
    {
        $l = Livewire::test(TodoList::class);

        $l->call('startEdit', $this->todo->id)->set('titleDraft', 'Renamed live');
        $this->assertSame('Renamed live', $this->todo->fresh()->title);
        $l->assertDispatched('griglia-autosaved');
        $this->assertSame($this->todo->id, $l->get('editingId'), 'the row stays in edit mode');

        $l->call('cancelEdit');
        $this->assertSame('Task', $this->todo->fresh()->title);
        $this->assertNull($l->get('editingId'));

        $l->call('startEdit', $this->todo->id)->set('titleDraft', 'Kept')->call('saveEdit');
        $this->assertSame('Kept', $this->todo->fresh()->title);
        $this->assertNull($l->get('editingId'));
    }
}
