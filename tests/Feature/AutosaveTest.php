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
 * schiacciare «Salva». Dal task 438 i bottoni «Salva» e «Annulla» non ci sono più: la modifica si
 * chiude e basta (finish*), e il passo indietro (revert*) rimette la versione di partenza senza
 * chiudere il campo.
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
        $m->call('finishTitle');

        $m->call('editNotes')->set('notesDraft', "line 1\nline 2");
        $this->assertSame("line 1\nline 2", $this->todo->fresh()->notes);
        $this->assertNotNull($m->get('notesDraft'));
        $m->assertDispatched('griglia-autosaved');
        $m->assertDontSee(__('griglia::t.autosaved'));

        // La chiusura non salva niente di nuovo: quello che c'è nel campo è già salvato.
        $m->call('finishNotes')->call('finishTitle');
        $this->assertNull($m->get('notesDraft'));
        $this->assertNull($m->get('titleDraft'));
        $this->assertSame('Live title', $this->todo->fresh()->title);
    }

    public function test_revert_puts_back_the_starting_value_without_closing(): void
    {
        $this->assertSame('Cancel', trans('griglia::t.revert', locale: 'en'));
        $this->assertSame('Annulla', trans('griglia::t.revert', locale: 'it'));

        $this->todo->update(['notes' => 'first note']);
        $m = Livewire::test(IngredientModal::class)->call('openFor', $this->todo->id);

        $m->call('editTitle')->set('titleDraft', 'Oops');
        $m->assertSee(__('griglia::t.revert'), false); // il passo indietro compare solo se il testo è cambiato
        $m->call('revertTitle');
        $this->assertSame('Task', $this->todo->fresh()->title);
        $this->assertSame('Task', $m->get('titleDraft'), 'the field stays open, on the old value');

        $m->call('editNotes')->set('notesDraft', 'oops')->call('revertNotes');
        $this->assertSame('first note', $this->todo->fresh()->notes);
        $this->assertSame('first note', $m->get('notesDraft'), 'the editor stays open, on the old note');
    }

    public function test_closing_the_editor_keeps_what_was_typed(): void
    {
        $m = Livewire::test(IngredientModal::class)->call('openFor', $this->todo->id);

        $m->call('editTitle')->set('titleDraft', 'Kept title')->call('finishTitle');
        $this->assertSame('Kept title', $this->todo->fresh()->title);
        $this->assertNull($m->get('titleDraft'));

        $m->call('editNotes')->set('notesDraft', 'kept note')->call('finishNotes');
        $this->assertSame('kept note', $this->todo->fresh()->notes);
        $this->assertNull($m->get('notesDraft'));
    }

    public function test_an_invalid_title_keeps_the_editor_open(): void
    {
        $m = Livewire::test(IngredientModal::class)->call('openFor', $this->todo->id);

        $m->call('editTitle')->set('titleDraft', '   ')->call('finishTitle');
        $this->assertSame('   ', $m->get('titleDraft'), 'nothing to save yet: the field stays open');
        $this->assertSame('Task', $this->todo->fresh()->title);
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

        $l->call('revertEdit');
        $this->assertSame('Task', $this->todo->fresh()->title);
        $this->assertSame($this->todo->id, $l->get('editingId'), 'the passo indietro does not close the row');
        $this->assertSame('Task', $l->get('titleDraft'));

        $l->set('titleDraft', 'Kept')->call('finishEdit');
        $this->assertSame('Kept', $this->todo->fresh()->title);
        $this->assertNull($l->get('editingId'));
    }
}
