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
     * Un elemento completato è in sola lettura: note, domande e sotto-task non si toccano
     * finché non viene riaperto dalla lista. Restituisce il todo solo se modificabile.
     */
    protected function editable(): ?Todo
    {
        $todo = $this->todo();

        if ($todo && $todo->completed) {
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
        $wasWorking = $todo->working;
        $attrs = match ($state) {
            'waiting' => ['completed' => false, 'open_to_work' => false, 'working' => false, 'question' => false],
            'open' => ['completed' => false, 'open_to_work' => true, 'working' => false, 'question' => false, 'stopped_at' => null],
            'done' => ['completed' => true, 'open_to_work' => false, 'working' => false, 'question' => false, 'result_seen' => true, 'progress' => null],
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
        if (! $todo) {
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
        if (! $todo || $checklistId === (int) $todo->checklist_id || ! Checklist::mine()->whereKey($checklistId)->exists()) {
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
        if ($todo = $this->todo()) {
            $this->dispatch('cmd-archive', todoId: $todo->id);
            $this->close();
        }
    }

    public function deleteTodo(): void
    {
        if ($todo = $this->todo()) {
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
    }

    public function cancelTitle(): void
    {
        $this->titleDraft = null;
    }

    public function saveTitle(): void
    {
        if (! ($todo = $this->editable()) || $this->titleDraft === null) {
            return;
        }

        $title = trim($this->titleDraft);

        if ($title === '') {
            return;
        }

        if (mb_strlen($title) > TodoList::titleMax()) {
            $this->dispatch('toast', message: __('griglia::t.msg.title_too_long', ['max' => TodoList::titleMax(), 'n' => mb_strlen($title)]), type: 'error');

            return;
        }

        $todo->update(['title' => $title]);
        $this->titleDraft = null;
        $this->dispatch('ingredients-updated'); // la lista mostra il nuovo titolo
        $this->dispatch('toast', message: __('griglia::t.msg.renamed'));
    }

    // ----- Nota -----

    public function editNotes(): void
    {
        if (! ($todo = $this->editable())) {
            return;
        }

        $this->notesDraft = $todo->notes ?? '';
    }

    public function cancelNotes(): void
    {
        $this->notesDraft = null;
    }

    public function saveNotes(): void
    {
        if (! ($todo = $this->editable()) || $this->notesDraft === null) {
            return;
        }

        $notes = trim($this->notesDraft);
        $todo->notes = $notes === '' ? null : $notes;
        $todo->save();

        $this->notesDraft = null;
        $this->dispatch('ingredients-updated');
        $this->dispatch('toast', message: __('griglia::t.msg.note_saved'));
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
        if (! in_array($name, $chosen, true) && ! isset(\Alle80\Griglia\Support\Skills::all()[$name])) {
            return; // unknown skill
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

    /** Dati comuni a tutte le viste del modale (base e stili dedicati). */
    protected function viewData(): array
    {
        $todo = $this->todo();

        return [
            'todo' => $todo,
            'readonly' => (bool) $todo?->completed,
            'skills' => \Alle80\Griglia\Support\Skills::all(),
            'otherLists' => $todo ? Checklist::mine()->whereKeyNot($todo->checklist_id)->orderBy('name')->get(['id', 'name']) : collect(),
        ];
    }
}
