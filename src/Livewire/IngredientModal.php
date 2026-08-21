<?php

namespace Alle80\Griglia\Livewire;

use Alle80\Griglia\Models\Attachment;
use Alle80\Griglia\Models\Checklist;
use Alle80\Griglia\Models\Ingredient;
use Alle80\Griglia\Models\Question;
use Alle80\Griglia\Models\Todo;
use Alle80\Griglia\Support\ImageDescription;
use Alle80\Griglia\Support\ImageStore;
use Alle80\Griglia\Themes;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;

class IngredientModal extends Component
{
    use WithFileUploads;

    public bool $open = false;

    /** Immagini in arrivo (upload da file, fotocamera o incolla). */
    public array $images = [];

    public ?string $imageError = null;

    public ?int $todoId = null;

    public string $newIngredient = '';

    /** Bozza della nota mentre è in modifica (null = non in modifica). */
    public ?string $notesDraft = null;

    /** Bozza del titolo mentre è in rinomina dal modale (null = non in modifica). */
    public ?string $titleDraft = null;

    /**
     * Valori com'erano all'apertura della modifica: il salvataggio è live (a ogni pausa nella
     * digitazione), quindi «annulla» non basta più a buttare via la bozza — deve rimettere questi.
     */
    public ?string $titleOriginal = null;

    public ?string $notesOriginal = null;

    /** Ingrediente in rinomina e relativa bozza. */
    public ?int $editingIngredientId = null;

    public string $ingredientDraft = '';

    /** Todo raggiungibili dall'utente: solo quelli delle sue liste. */
    protected function reachable(): Builder
    {
        return Todo::whereIn('checklist_id', Checklist::mine()->select('id'));
    }

    /** Il todo aperto nel modale (null se chiuso o non più raggiungibile). */
    protected function todo(): ?Todo
    {
        return $this->todoId ? $this->reachable()->with(['ingredients', 'attachments', 'questions', 'parent.ingredients'])->find($this->todoId) : null;
    }

    /**
     * Un elemento completato o in lavorazione è in sola lettura: note, domande e sotto-task non si toccano
     * finché non torna a uno stato precedente. Restituisce il todo solo se modificabile.
     */
    protected function editable(): ?Todo
    {
        $todo = $this->todo();

        if ($todo && ($todo->completed || $todo->working)) {
            $this->dispatch('toast', message: __('griglia::t.msg.readonly'), type: 'info');

            return null;
        }

        return $todo;
    }

    /**
     * "New task" button: create a blank todo in the current list and open it in title editing.
     * Done in the modal (via a client dispatch) so it reliably opens — a server-side dispatch from
     * the list to this child component gets lost when the list re-renders after creating the todo.
     */
    #[On('open-new-task')]
    public function createNew(?int $position = null): void
    {
        $listId = Checklist::currentId();
        if (! $listId) {
            return;
        }

        $active = Todo::where('checklist_id', $listId)->whereNull('archived_at');
        $end = ((int) (clone $active)->max('order')) + 1;
        $order = $position !== null ? max(1, min($end, $position)) : $end;
        if ($order < $end) {
            (clone $active)->where('order', '>=', $order)->increment('order'); // the «+» between rows: make room here
        }

        $todo = Todo::create([
            'title' => '',
            'order' => $order,
            'completed' => false,
            'checklist_id' => $listId,
        ]);

        // Plan lists: keep the chain = list order (the task after the inserted one now depends on it)
        if ($order < $end) {
            $prev = null;
            foreach (Todo::where('checklist_id', $listId)->whereNull('archived_at')->orderBy('order')->orderBy('id')->get(['id', 'depends_on_id']) as $t) {
                $dep = $prev?->id;
                if ($prev !== null && (int) $t->depends_on_id !== (int) $dep && Todo::where('checklist_id', $listId)->whereNotNull('depends_on_id')->exists()) {
                    Todo::whereKey($t->id)->update(['depends_on_id' => $dep]);
                }
                $prev = $t;
            }
        }

        $this->dispatch('ingredients-updated'); // the list shows the new (empty) row
        $this->openFor($todo->id, true);
    }

    #[On('open-ingredients')]
    public function openFor(int $todoId, bool $edit = false): void
    {
        $this->reachable()->findOrFail($todoId);

        $this->todoId = $todoId;
        $this->resetDrafts();
        $this->answers = Question::where('todo_id', $todoId)->pluck('answer', 'id')->map(fn ($v) => (string) $v)->all();
        $this->open = true;

        // A brand-new task (created blank by "add") opens straight into title editing.
        if ($edit) {
            $this->titleDraft = (string) ($this->todo()?->title ?? '');
        }

        // Opening a completed task marks the agent's result as seen (removes the highlight).
        $todo = $this->todo();
        if ($todo && $todo->completed && ! $todo->result_seen) {
            $todo->update(['result_seen' => true]);
            \Alle80\Griglia\Support\Live::todoChanged($todo);
        }
    }

    /** Aggiornamento live (Reverb) ricevuto dalla lista: il modale aperto si ri-renderizza. */
    #[On('todo-changed-live')]
    public function refreshLive(): void
    {
        // Se il todo aperto non esiste più, chiudiamo
        if ($this->open && ! $this->todo()) {
            $this->close();
        }
    }

    public function close(): void
    {
        // Abandoned new task (created blank by "add" and never titled) → drop it on close.
        $todo = $this->todo();
        if ($todo && trim((string) $todo->title) === ''
            && trim((string) $todo->notes) === ''
            && $todo->ingredients()->count() === 0
            && $todo->attachments()->count() === 0) {
            $todo->forceDelete(); // blank and untouched: no stats to keep, no need to clog the trash
            $this->dispatch('ingredients-updated');
        }

        $this->open = false;
        $this->todoId = null;
        $this->resetDrafts();
    }

    protected function resetDrafts(): void
    {
        $this->newIngredient = '';
        $this->notesDraft = null;
        $this->titleDraft = null;
        $this->titleOriginal = null;
        $this->notesOriginal = null;
        $this->editingIngredientId = null;
        $this->ingredientDraft = '';
        $this->images = [];
        $this->imageError = null;
        $this->answers = [];
    }

    // ----- Immagini -----

    /** Appena Livewire ha ricevuto i file (da <input type=file> o da incolla), li salviamo. */
    public function updatedImages(): void
    {
        $this->imageError = null;

        if (! ($todo = $this->todo())) {
            $this->images = [];

            return;
        }

        try {
            $this->validate([
                'images.*' => ['image', 'mimes:jpeg,jpg,png,gif', 'max:20480'],
            ], [
                'images.*.image' => __('griglia::t.msg.not_an_image'),
                'images.*.mimes' => __('griglia::t.msg.image_formats'),
                'images.*.max' => __('griglia::t.msg.image_too_big'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->imageError = collect($e->errors())->flatten()->first();
            $this->images = [];
            $this->dispatch('toast', message: $this->imageError, type: 'error');

            return;
        }

        $saved = 0;

        try {
            foreach ($this->images as $file) {
                $attachment = ImageStore::store($todo, $file);
                $saved++;

                // Descrizione AI per la ricerca: dopo la risposta, così l'upload resta veloce
                if (ImageDescription::enabled()) {
                    dispatch(fn () => ImageDescription::describe($attachment->fresh()))->afterResponse();
                }
            }
        } catch (RuntimeException $e) {
            $this->imageError = $e->getMessage();
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }

        $this->images = [];
        $this->dispatch('ingredients-updated');

        if ($saved > 0) {
            $this->dispatch('toast', message: $saved === 1 ? __('griglia::t.msg.image_uploaded') : __('griglia::t.msg.images_uploaded', ['count' => $saved]));
        }
    }

    public function deleteAttachment(int $attachmentId): void
    {
        if (! $this->todo()) {
            return;
        }

        Attachment::where('todo_id', $this->todoId)->whereKey($attachmentId)->first()?->delete();

        $this->dispatch('ingredients-updated');
        $this->dispatch('toast', message: __('griglia::t.msg.image_deleted'), type: 'info');
    }

    // ----- Domande dell'assistente -----

    /** Risposte in bozza, indicizzate per id domanda. */
    public array $answers = [];

    public function saveAnswer(int $questionId): void
    {
        if (! $this->editable()) {
            return;
        }

        $q = Question::where('todo_id', $this->todoId)->findOrFail($questionId);
        $answer = trim($this->answers[$questionId] ?? '');
        $q->answer = $answer === '' ? null : $answer;
        $q->save();

        $this->dispatch('toast', message: __($q->answer ? 'griglia::t.msg.answer_saved' : 'griglia::t.msg.answer_removed'), type: $q->answer ? 'success' : 'info');
    }

    /** Ultimo passo: tutte le domande hanno risposta → l'elemento torna "open to work". */
    public function resumeWork(): void
    {
        if (! ($todo = $this->editable())) {
            return;
        }

        if ($todo->questions()->whereNull('answer')->exists()) {
            $this->dispatch('toast', message: __('griglia::t.msg.answer_all_first'), type: 'error');

            return;
        }

        $todo->update(['question' => false, 'open_to_work' => true, 'working' => false]);

        $this->dispatch('ingredients-updated');
        $this->dispatch('toast', message: __('griglia::t.msg.restarted', ['title' => $todo->title]));
    }

    /** «Riprendi» dal modale: la logica (posizione, scoping) sta in TodoList::resume. */
    public function resumeTodo(): void
    {
        if (! ($todo = $this->todo())) {
            return;
        }

        $this->dispatch('resume-todo', todoId: $todo->id);
    }

    // ----- Comandi nella testata -----

    /**
     * Ids of the active tasks of the list, in the order they are shown: the modal walks them with the
     * arrows, which is how you follow a plan from one task to the next (task 365).
     *
     * @return array<int, int>
     */
    public function siblingIds(): array
    {
        $todo = $this->todo();

        if (! $todo) {
            return [];
        }

        return Todo::where('checklist_id', $todo->checklist_id)
            ->whereNull('archived_at')
            ->orderBy('order')->orderBy('id')
            ->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    /** Position of the open task among its siblings, 1-based (0 when it is not in the list). */
    public function position(): int
    {
        $todo = $this->todo();
        $i = $todo ? array_search((int) $todo->id, $this->siblingIds(), true) : false;

        return $i === false ? 0 : $i + 1;
    }

    /** Id of the previous (-1) or next (+1) task, or null at the ends. */
    public function siblingId(int $delta): ?int
    {
        $ids = $this->siblingIds();
        $position = $this->position();

        return $position === 0 ? null : ($ids[$position - 1 + $delta] ?? null);
    }

    /** Open the previous (-1) or next (+1) task without leaving the modal. */
    public function goSibling(int $delta): void
    {
        if ($id = $this->siblingId($delta === -1 ? -1 : 1)) {
            $this->openFor($id);
        }
    }

    /** State of the current todo, for the coloured badge: waiting|open|working|question|done. */
    public function stateKey(): string
    {
        $todo = $this->todo();

        return match (true) {
            ! $todo => 'waiting',
            (bool) $todo->completed => 'done',
            (bool) $todo->question => 'question',
            (bool) $todo->working => 'working',
            (bool) $todo->open_to_work => 'open',
            default => 'waiting',
        };
    }

    /** Toggle open-to-work / stop, mirroring the row dot (no archived/order changes). */
    public function toggleOpenToWork(): void
    {
        if (! ($todo = $this->editable())) {
            return;
        }

        if ($todo->working) {
            $todo->update(['working' => false, 'open_to_work' => false, 'stopped_at' => now()]);
            $this->dispatch('toast', message: __('griglia::t.msg.stopped', ['title' => $todo->title]), type: 'info');
        } else {
            $todo->open_to_work = ! $todo->open_to_work;
            if ($todo->open_to_work) {
                $todo->stopped_at = null;
            }
            $todo->save();
            $this->dispatch('toast', message: __($todo->open_to_work ? 'griglia::t.msg.otw_on' : 'griglia::t.msg.otw_off', ['title' => $todo->title]), type: $todo->open_to_work ? 'success' : 'info');
        }

        \Alle80\Griglia\Support\Live::todoChanged($todo);
        $this->dispatch('ingredients-updated');
    }

    /**
     * Set the state from the badge in the modal header: 'waiting' ⚪, 'open' 🟢 or 'done' ✔
     * (the agent states working/question are left to the agent; choosing a state while the agent works = stop).
     */
    public function setState(string $state): void
    {
        $todo = $this->todo();
        if (! $todo || ! in_array($state, ['waiting', 'open', 'done'], true)) {
            return;
        }

        // A closed task stays closed: carry on with «resume», which makes a new task linked to it (task 348).
        if ($todo->completed && $state !== 'done') {
            $this->dispatch('toast', message: __('griglia::t.msg.done_is_done'), type: 'info');

            return;
        }
        $wasWorking = $todo->working;
        $attrs = match ($state) {
            'waiting' => ['completed' => false, 'open_to_work' => false, 'working' => false, 'question' => false, 'outcome' => null],
            'open' => ['completed' => false, 'open_to_work' => true, 'working' => false, 'question' => false, 'stopped_at' => null, 'outcome' => null],
            // closed by the user: there is no agent result to flag, so no outcome
            'done' => ['completed' => true, 'open_to_work' => false, 'working' => false, 'question' => false, 'result_seen' => true, 'progress' => null, 'outcome' => null],
        };
        if ($wasWorking && $state !== 'open') {
            $attrs['stopped_at'] = now();
        }
        $todo->update($attrs);
        $this->dispatch('toast', message: __('griglia::t.msg.state_set', ['state' => __('griglia::t.state.'.$state), 'title' => $todo->title]), type: $state === 'done' ? 'success' : 'info');
        \Alle80\Griglia\Support\Live::todoChanged($todo);
        $this->dispatch('ingredients-updated');
    }

    /** Multi-agent: choose which agent handles this task ('' = the list's default). */
    public function setAgent(string $agent): void
    {
        $todo = $this->todo();
        if (! $todo || $todo->working) {
            return;
        }
        $agent = trim($agent);
        if ($agent !== '' && ! array_key_exists($agent, \Alle80\Griglia\Agent::all())) {
            return;
        }
        $todo->update(['agent' => $agent ?: null]);
        $this->dispatch('ingredients-updated');
    }

    /** Move the todo to another list of the user (appended at the end of the active items there). */
    public function moveTo(int $checklistId): void
    {
        $todo = $this->todo();
        if (! $todo || $todo->working || $checklistId === (int) $todo->checklist_id || ! Checklist::mine()->whereKey($checklistId)->exists()) {
            return;
        }
        $from = $todo->checklist_id;
        $order = $todo->order;
        $newOrder = ((int) Todo::where('checklist_id', $checklistId)->whereNull('archived_at')->max('order')) + 1;
        $todo->update(['checklist_id' => $checklistId, 'order' => $todo->archived_at ? 0 : $newOrder]);
        if (! $todo->archived_at) {
            Todo::where('checklist_id', $from)->whereNull('archived_at')->where('order', '>', $order)->decrement('order'); // close the gap
        }
        $target = Checklist::find($checklistId);
        $this->dispatch('toast', message: __('griglia::t.msg.moved', ['title' => $todo->title, 'list' => $target?->name]));
        $this->dispatch('ingredients-updated');
        $this->close();
    }

    /** Archive / delete reuse the list logic (order reindex) then close the modal. */
    public function archiveTodo(): void
    {
        if (($todo = $this->todo()) && ! $todo->working) {
            $this->dispatch('cmd-archive', todoId: $todo->id);
            $this->close();
        }
    }

    public function deleteTodo(): void
    {
        if (($todo = $this->todo()) && ! $todo->working) {
            $this->dispatch('cmd-delete', todoId: $todo->id);
            $this->close();
        }
    }

    // ----- Titolo -----

    public function editTitle(): void
    {
        if (! ($todo = $this->editable())) {
            return;
        }

        $this->titleDraft = $todo->title;
        $this->titleOriginal = $todo->title;
    }

    /**
     * «Annulla»: rimette il titolo com'era quando la modifica è cominciata.
     * Il campo resta aperto — è un passo indietro, non una chiusura (task 438).
     */
    public function revertTitle(): void
    {
        if (! ($todo = $this->editable()) || $this->titleOriginal === null) {
            return;
        }

        if ($todo->title !== $this->titleOriginal) {
            $todo->update(['title' => $this->titleOriginal]);
            $this->dispatch('ingredients-updated');
        }

        $this->titleDraft = $this->titleOriginal;
        $this->dispatch('toast', message: __('griglia::t.msg.reverted'));
    }

    /** Salvataggio live: la bozza arriva dal campo (wire:model.live) a ogni pausa nella digitazione. */
    public function updatedTitleDraft(): void
    {
        $this->autosaveTitle();
    }

    /**
     * Persiste la bozza del titolo senza chiudere la modifica e senza toast (sarebbe uno a ogni pausa):
     * la spia «salvato» accanto al campo basta. Restituisce false se non c'era niente da salvare.
     */
    protected function autosaveTitle(): bool
    {
        if (! ($todo = $this->editable()) || $this->titleDraft === null) {
            return false;
        }

        $title = trim($this->titleDraft);

        if ($title === '' || $title === $todo->title) {
            return false;
        }

        if (mb_strlen($title) > TodoList::titleMax()) {
            $this->dispatch('toast', message: __('griglia::t.msg.title_too_long', ['max' => TodoList::titleMax(), 'n' => mb_strlen($title)]), type: 'error');

            return false;
        }

        $todo->update(['title' => $title]);
        $this->dispatch('ingredients-updated'); // la lista mostra il nuovo titolo
        $this->dispatch('griglia-autosaved'); // spia «salvato» accanto al campo

        return true;
    }

    /**
     * Chiude la modifica del titolo senza bottoni: Invio, Esc o un clic fuori dal campo (task 438).
     * Quello che c'è scritto è già salvato.
     */
    public function finishTitle(): void
    {
        if (! $this->editable() || $this->titleDraft === null) {
            return;
        }

        $this->autosaveTitle();
        $title = trim($this->titleDraft);

        if ($title === '' || mb_strlen($title) > TodoList::titleMax()) {
            return; // titolo non valido: si resta in modifica, l'avviso l'ha già dato l'autosalvataggio
        }

        $this->titleDraft = null;
        $this->titleOriginal = null;
    }

    // ----- Nota -----

    public function editNotes(): void
    {
        if (! ($todo = $this->editable())) {
            return;
        }

        $this->notesDraft = $todo->notes ?? '';
        $this->notesOriginal = $todo->notes ?? '';
    }

    /**
     * «Annulla»: rimette la nota com'era quando la modifica è cominciata.
     * L'editor resta aperto — è un passo indietro, non una chiusura (task 438).
     */
    public function revertNotes(): void
    {
        if (! ($todo = $this->editable()) || $this->notesOriginal === null) {
            return;
        }

        if ((string) $todo->notes !== $this->notesOriginal) {
            $todo->notes = $this->notesOriginal === '' ? null : $this->notesOriginal;
            $todo->save();
            $this->dispatch('ingredients-updated');
        }

        $this->notesDraft = $this->notesOriginal;
        $this->dispatch('toast', message: __('griglia::t.msg.reverted'));
    }

    /** Salvataggio live: la bozza arriva dall'editor (wire:model.live) a ogni pausa nella digitazione. */
    public function updatedNotesDraft(): void
    {
        $this->autosaveNotes();
    }

    /** Persiste la bozza della nota senza chiudere l'editor e senza toast. */
    protected function autosaveNotes(): bool
    {
        if (! ($todo = $this->editable()) || $this->notesDraft === null) {
            return false;
        }

        $notes = trim($this->notesDraft);
        $notes = $notes === '' ? null : $notes;

        if ($notes === $todo->notes) {
            return false;
        }

        $todo->notes = $notes;
        $todo->save();
        $this->dispatch('ingredients-updated');
        $this->dispatch('griglia-autosaved'); // spia «salvato» accanto all'editor

        return true;
    }

    /**
     * Chiude l'editor della nota senza bottoni: Esc o un clic fuori (task 438). Quello che c'è
     * scritto è già salvato.
     */
    public function finishNotes(): void
    {
        if (! $this->editable() || $this->notesDraft === null) {
            return;
        }

        $this->autosaveNotes();
        $this->notesDraft = null;
        $this->notesOriginal = null;
    }

    // ----- Skills of the agent chosen for this task -----

    /** Toggle a skill (from the catalogue, or already chosen) on the open todo. */
    public function toggleSkill(string $name): void
    {
        if (! ($todo = $this->editable())) {
            return;
        }
        $name = trim($name);
        $chosen = array_values((array) $todo->skills);
        // Only a skill the agent of this task can really invoke; an already chosen one stays togglable, so a
        // leftover from another agent can still be removed
        if (! in_array($name, $chosen, true) && ! isset($this->skillCatalogue($todo)[$name])) {
            return; // unknown skill, or not available to this agent
        }
        $chosen = in_array($name, $chosen, true) ? array_values(array_diff($chosen, [$name])) : [...$chosen, $name];
        $todo->skills = $chosen ?: null;
        $todo->save();
        $this->dispatch('ingredients-updated');
    }

    // ----- Rinomina ingrediente -----

    public function editIngredient(int $ingredientId): void
    {
        if (! $this->editable()) {
            return;
        }

        $ingredient = Ingredient::where('todo_id', $this->todoId)->findOrFail($ingredientId);
        $this->editingIngredientId = $ingredient->id;
        $this->ingredientDraft = $ingredient->name;
    }

    public function cancelEditIngredient(): void
    {
        $this->editingIngredientId = null;
        $this->ingredientDraft = '';
    }

    public function saveIngredient(): void
    {
        $name = trim($this->ingredientDraft);

        if ($name === '' || ! $this->editingIngredientId || ! $this->editable()) {
            return;
        }

        Ingredient::where('todo_id', $this->todoId)->whereKey($this->editingIngredientId)->first()?->update(['name' => $name]);

        $this->cancelEditIngredient();
        $this->dispatch('ingredients-updated');
        $this->dispatch('toast', message: __('griglia::t.msg.subtask_renamed'));
    }

    public function addIngredient(): void
    {
        $name = trim($this->newIngredient);

        if ($name === '' || ! $this->editable()) {
            return;
        }

        Ingredient::create([
            'todo_id' => $this->todoId,
            'name' => $name,
            'checked' => false,
            'order' => (Ingredient::where('todo_id', $this->todoId)->max('order') ?? 0) + 1,
        ]);

        $this->newIngredient = '';
        $this->dispatch('ingredients-updated');
        $this->dispatch('toast', message: __('griglia::t.msg.subtask_added'));
    }

    /** @param array<int, int|string> $orderedIds Id dei sotto-task nell'ordine mostrato dopo il drag. */
    public function reorderIngredients(array $orderedIds): void
    {
        if (! $this->editable()) {
            return;
        }

        foreach ($orderedIds as $index => $id) {
            Ingredient::where('todo_id', $this->todoId)->whereKey($id)->update(['order' => $index + 1]);
        }
    }

    public function deleteIngredient(int $ingredientId): void
    {
        if (! $this->editable()) {
            return;
        }

        Ingredient::where('todo_id', $this->todoId)->whereKey($ingredientId)->first()?->delete();

        $this->dispatch('ingredients-updated');
        $this->dispatch('toast', message: __('griglia::t.msg.subtask_deleted'), type: 'info');
    }

    public function toggleIngredient(int $ingredientId): void
    {
        if (! $this->editable()) {
            return;
        }

        $ingredient = Ingredient::where('todo_id', $this->todoId)->findOrFail($ingredientId);
        $ingredient->checked = ! $ingredient->checked;
        $ingredient->save();

        $this->dispatch('ingredients-updated');
    }

    public function render()
    {
        // Default view: the generic themed modal with the default theme (dedicated styles override render())
        return view('griglia::livewire.ingredient-modal', $this->viewData() + ['t' => Themes::get(Themes::default())]);
    }

    /** Le skill che l'agente assegnato a questo task può davvero invocare (task 375). */
    protected function skillCatalogue(Todo $todo): array
    {
        return \Alle80\Griglia\Support\Skills::forAgent(\Alle80\Griglia\Agent::effective($todo));
    }

    /** Dati comuni a tutte le viste del modale (base e stili dedicati). */
    protected function viewData(): array
    {
        $todo = $this->todo();

        return [
            'todo' => $todo,
            'readonly' => (bool) ($todo?->completed || $todo?->working),
            'skills' => $todo ? $this->skillCatalogue($todo) : \Alle80\Griglia\Support\Skills::all(),
            'skillsAgent' => $todo ? \Alle80\Griglia\Agent::label(\Alle80\Griglia\Agent::effective($todo)) : \Alle80\Griglia\Agent::name(),
            'otherLists' => $todo ? Checklist::mine()->whereKeyNot($todo->checklist_id)->orderBy('name')->get(['id', 'name']) : collect(),
        ];
    }
}
