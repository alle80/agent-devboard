<?php

namespace Alle80\Griglia\Livewire;

use Alle80\Griglia\Models\Checklist;
use Alle80\Griglia\Models\Todo;
use Alle80\Griglia\Themes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;

class TodoList extends Component
{
    /** Valore di `order` che prenderà il nuovo todo (null = nessun inserimento in corso). */
    public ?int $insertAt = null;

    public string $newTitle = '';

    /** Vista archivio (true) o lista attiva (false). */
    public bool $showArchived = false;

    /** Ricerca a testo libero (titolo, nota, commento, sotto-task, immagini). */
    public string $search = '';

    /** Search in every active list owned by the current user. */
    public bool $searchAllLists = false;

    /** Filtro di stato: all | todo | done | otw | working | question */
    public string $filter = 'all';

    /** Lunghezza massima del titolo di un todo: default 50, modificabile da /settings. */
    public const TITLE_MAX = 50;

    public static function titleMax(): int
    {
        return (int) (app(\Alle80\Griglia\Settings\AppSettings::class)->title_max_length ?: self::TITLE_MAX);
    }

    /** Filter keys (labels come from the translations: griglia::t.filters). */
    public const FILTERS = ['all', 'todo', 'done', 'otw', 'working', 'question'];

    /** key => translated label */
    public static function filters(): array
    {
        $labels = (array) __('griglia::t.filters');

        return array_combine(self::FILTERS, array_map(fn ($k) => $labels[$k] ?? $k, self::FILTERS));
    }

    public function setFilter(string $filter): void
    {
        $this->filter = in_array($filter, self::FILTERS, true) ? $filter : 'all';
    }

    public function clearSearch(): void
    {
        $this->search = '';
    }

    public function toggleSearchScope(): void
    {
        $this->searchAllLists = ! $this->searchAllLists;
    }

    /** Applica ricerca e filtro di stato a una query di todo. */
    protected function applyFilters(Builder $q): Builder
    {
        $term = trim($this->search);

        if ($term !== '') {
            $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $term).'%';
            $q->where(function (Builder $w) use ($like) {
                $w->where('title', 'like', $like)
                    ->orWhere('notes', 'like', $like)
                    ->orWhere('claude_comment', 'like', $like)
                    ->orWhereHas('ingredients', fn ($i) => $i->where('name', 'like', $like))
                    ->orWhereHas('questions', fn ($qq) => $qq->where('question', 'like', $like)->orWhere('answer', 'like', $like))
                    ->orWhereHas('attachments', fn ($a) => $a->where('original_name', 'like', $like)->orWhere('description', 'like', $like));
            });
        }

        return match ($this->filter) {
            'todo' => $q->where('completed', false),
            'done' => $q->where('completed', true),
            'otw' => $q->where('open_to_work', true)->where('completed', false),
            'working' => $q->where('working', true)->where('completed', false),
            'question' => $q->where('question', true),
            default => $q,
        };
    }

    protected function isFiltering(): bool
    {
        return trim($this->search) !== '' || $this->filter !== 'all';
    }

    /** Todo in rinomina e relativa bozza. */
    public ?int $editingId = null;

    public string $titleDraft = '';

    /** Titolo com'era quando è iniziata la rinomina: il salvataggio è live, «annulla» rimette questo. */
    public ?string $titleOriginal = null;

    /** Query dei todo della lista corrente. */
    protected function scoped(): Builder
    {
        if ($this->searchAllLists && trim($this->search) !== '') {
            return Todo::whereIn('checklist_id', Checklist::mine()->select('id'));
        }

        return Todo::where('checklist_id', Checklist::currentId());
    }

    /** Todo della lista corrente, ordinati, con sotto-task: usato dai render di tutte le varianti. */
    protected function todos(): Collection
    {
        return $this->applyFilters($this->scoped())
            ->when($this->showArchived, fn ($q) => $q->whereNotNull('archived_at')->orderByDesc('archived_at'), fn ($q) => $q->whereNull('archived_at')->orderBy('order'))
            ->with(['checklist:id,name', 'ingredients', 'dependsOn:id,title,completed,order'])->withCount('attachments')->get();
    }

    /** Query dei todo attivi (non archiviati) della lista corrente: la numerazione `order` vive solo qui. */
    protected function active(): Builder
    {
        return Todo::where('checklist_id', Checklist::currentId())->whereNull('archived_at');
    }

    /** Multi-agent: default agent of the current list ('' = the global default). */
    public function setListAgent(string $agent): void
    {
        $agent = trim($agent);
        if ($agent !== '' && ! array_key_exists($agent, \Alle80\Griglia\Agent::all())) {
            return;
        }
        Checklist::mine()->whereKey(Checklist::currentId())->update(['agent' => $agent ?: null]);
        $this->dispatch('toast', message: __('griglia::t.agent_set', ['agent' => \Alle80\Griglia\Agent::label($agent ?: \Alle80\Griglia\Agent::defaultKey())]));
    }

    /** Multi-agent: agent of a single task from the list row ('' = the list's default). */
    public function setTodoAgent(int $todoId, string $agent): void
    {
        $agent = trim($agent);
        if ($agent !== '' && ! array_key_exists($agent, \Alle80\Griglia\Agent::all())) {
            return;
        }
        $todo = $this->scoped()->findOrFail($todoId);
        if ($todo->working) { return; }
        $todo->update(['agent' => $agent ?: null]);
        $this->dispatch('toast', message: __('griglia::t.agent_set_task', [
            'title' => $todo->title,
            'agent' => \Alle80\Griglia\Agent::label(\Alle80\Griglia\Agent::effective($todo)),
        ]));
    }

    /* ---------- Plan mode: start / status ---------- */

    /** Plan status of the current list: null if not a plan, else [next id|null, done, total, running]. */
    protected function planStatus(): ?array
    {
        $chained = $this->active()->whereNotNull('depends_on_id')->exists();
        $list = Checklist::find(Checklist::currentId());
        if (! $chained && ! ($list?->plan_prompt)) {
            return null;
        }
        $todos = $this->active()->orderBy('order')->get(['id', 'completed', 'open_to_work', 'working', 'question']);
        $next = $todos->first(fn ($t) => ! $t->completed && ! $t->open_to_work && ! $t->working && ! $t->question);
        $running = $todos->contains(fn ($t) => ! $t->completed && ($t->open_to_work || $t->working || $t->question));

        return ['next' => $next?->id, 'done' => $todos->where('completed', true)->count(), 'total' => $todos->count(), 'running' => $running, 'paused' => (bool) $list?->plan_paused];
    }

    /** Pause the plan: open tasks go back to waiting ⚪, the chain stops opening the next ones (a working task is left to the agent). */
    public function pausePlan(): void
    {
        if (! $this->planStatus()) {
            return;
        }
        $list = Checklist::find(Checklist::currentId());
        $list?->update(['plan_paused' => true]);
        $this->active()->where('completed', false)->where('open_to_work', true)->where('working', false)->update(['open_to_work' => false]);
        $this->dispatch('toast', message: __('griglia::t.plan.paused'), type: 'info');
    }

    /** Start (or resume) the plan: the first not-started task becomes open to work 🟢; the chain does the rest. */
    public function startPlan(): void
    {
        $status = $this->planStatus();
        if (! $status) {
            return;
        }
        Checklist::whereKey(Checklist::currentId())->update(['plan_paused' => false]);
        if (! $status['next']) {
            return;
        }
        $todo = $this->active()->findOrFail($status['next']);
        $todo->update(['open_to_work' => true, 'stopped_at' => null]);
        $this->dispatch('toast', message: __('griglia::t.plan.started', ['title' => $todo->title]), type: 'success');
    }

    protected function archivedCount(): int
    {
        return $this->scoped()->whereNotNull('archived_at')->count();
    }

    public function toggleArchived(): void
    {
        $this->showArchived = ! $this->showArchived;
        $this->cancelInsert();
        $this->closeEdit(); // quello che si stava scrivendo è già salvato: non si butta via
    }

    public function archive(int $todoId): void
    {
        $todo = $this->active()->findOrFail($todoId);
        if ($todo->working) { return; }
        $todo->update(['archived_at' => now()]);

        // Richiude il buco nella numerazione degli attivi
        $this->active()->where('order', '>', $todo->order)->decrement('order');
        $this->dispatch('toast', message: __('griglia::t.msg.archived', ['title' => $todo->title]), type: 'info');
    }

    public function unarchive(int $todoId): void
    {
        $todo = $this->scoped()->whereNotNull('archived_at')->findOrFail($todoId);
        $todo->update(['archived_at' => null, 'order' => ((int) $this->active()->max('order')) + 1]);
        $this->dispatch('toast', message: __('griglia::t.msg.restored', ['title' => $todo->title]));
    }

    /** Nome della lista corrente: è il titolo di tutte le pagine. */
    protected function listName(): string
    {
        return Checklist::findOrFail(Checklist::currentId())->name;
    }

    public function toggle(int $todoId): void
    {
        $todo = $this->scoped()->findOrFail($todoId);
        if ($todo->working) { return; }

        // A closed task stays closed: reopening it would put back in front of the agent something it had
        // already answered. To carry on, «resume» makes a new task linked to this one (task 348).
        if ($todo->completed) {
            $this->dispatch('toast', message: __('griglia::t.msg.done_is_done'), type: 'info');

            return;
        }

        $todo->update(['completed' => true]);

        $this->dispatch('toast', message: __('griglia::t.msg.completed', ['title' => $todo->title]), type: 'success');
    }

    public function toggleOpenToWork(int $todoId): void
    {
        $todo = $this->scoped()->findOrFail($todoId);

        // Done is done: the dot of a closed task does not put it back to work (task 348).
        if ($todo->completed) {
            $this->dispatch('toast', message: __('griglia::t.msg.done_is_done'), type: 'info');

            return;
        }

        // Con domande aperte il pallino porta al modale per rispondere
        if ($todo->question) {
            $this->dispatch('open-ingredients', todoId: $todo->id);

            return;
        }

        // In lavorazione (🔧): il click ferma il lavoro dell'assistente → torna ⚪ e resta traccia in stopped_at
        if ($todo->working) {
            $todo->update(['working' => false, 'open_to_work' => false, 'stopped_at' => now()]);
            $this->dispatch('toast', message: __('griglia::t.msg.stopped', ['title' => $todo->title]), type: 'info');

            return;
        }

        $todo->open_to_work = ! $todo->open_to_work;
        if ($todo->open_to_work) {
            $todo->stopped_at = null; // rimesso 🟢: lo stop non vale più
        }
        $todo->save();

        $this->dispatch('toast', message: __($todo->open_to_work ? 'griglia::t.msg.otw_on' : 'griglia::t.msg.otw_off', ['title' => $todo->title]), type: $todo->open_to_work ? 'success' : 'info');
    }

    /**
     * «Riprendi»: da un todo completato apre un nuovo todo collegato (parent_id) subito dopo,
     * stesso titolo, nota vuota da compilare con aggiunte/modifiche; il contesto del vecchio
     * (nota, commento, sotto-task, immagini) resta consultabile dal nuovo.
     */
    public function resume(int $todoId): void
    {
        $old = $this->scoped()->findOrFail($todoId);

        if (! $old->completed) {
            $this->dispatch('toast', message: __('griglia::t.msg.resume_only_done'), type: 'error');

            return;
        }

        // Posizione: subito dopo l'originale se è attivo, altrimenti in fondo
        $position = $old->archived_at ? ((int) $this->active()->max('order') + 1) : $old->order + 1;
        $this->active()->where('order', '>=', $position)->increment('order');

        $new = Todo::create([
            'title' => $old->title,
            'order' => $position,
            'completed' => false,
            'checklist_id' => $old->checklist_id,
            'parent_id' => $old->id,
        ]);

        if ($this->showArchived) {
            $this->showArchived = false;
        }

        $this->dispatch('toast', message: __('griglia::t.msg.resumed'));
        $this->dispatch('open-ingredients', todoId: $new->id);
    }

    public function startEdit(int $todoId): void
    {
        $todo = $this->scoped()->findOrFail($todoId);
        if ($todo->working) { return; }
        $this->editingId = $todo->id;
        $this->titleDraft = $todo->title;
        $this->titleOriginal = $todo->title;
    }

    /**
     * «Annulla»: rimette il titolo com'era quando la modifica è cominciata.
     * La rinomina resta aperta — è un passo indietro, non una chiusura (task 438).
     */
    public function revertEdit(): void
    {
        if (! $this->editingId || $this->titleOriginal === null) {
            return;
        }

        $this->scoped()->whereKey($this->editingId)->where('title', '!=', $this->titleOriginal)
            ->update(['title' => $this->titleOriginal]);

        $this->titleDraft = $this->titleOriginal;
        $this->dispatch('toast', message: __('griglia::t.msg.reverted'));
    }

    /** Chiude la rinomina senza toccare quello che è già stato salvato. */
    protected function closeEdit(): void
    {
        $this->editingId = null;
        $this->titleDraft = '';
        $this->titleOriginal = null;
    }

    /** Salvataggio live: la bozza arriva dal campo (wire:model.live) a ogni pausa nella digitazione. */
    public function updatedTitleDraft(): void
    {
        $this->autosaveEdit();
    }

    /** Persiste la bozza senza chiudere la rinomina e senza toast (sarebbe uno a ogni pausa). */
    protected function autosaveEdit(): bool
    {
        $title = trim($this->titleDraft);

        if ($title === '' || ! $this->editingId || ! $this->titleFits($title)) {
            return false;
        }

        $saved = $this->scoped()->whereKey($this->editingId)->where('working', false)->where('title', '!=', $title)
            ->update(['title' => $title]) > 0;

        if ($saved) {
            $this->dispatch('griglia-autosaved'); // spia «salvato» accanto al campo
        }

        return $saved;
    }

    /**
     * Chiude la rinomina senza bottoni: Invio, Esc o un clic fuori dal campo (task 438). Quello che
     * c'è scritto è già salvato; con un titolo vuoto o troppo lungo si resta dentro, altrimenti il
     * testo appena scritto sparirebbe senza essere mai stato salvato.
     */
    public function finishEdit(): void
    {
        $title = trim($this->titleDraft);

        if ($title === '' || ! $this->editingId || ! $this->titleFits($title)) {
            return;
        }

        $this->autosaveEdit();
        $this->closeEdit();
    }

    /** Titolo entro il limite? Altrimenti avvisa e non salva (niente troncamenti silenziosi). */
    protected function titleFits(string $title): bool
    {
        if (mb_strlen($title) <= self::titleMax()) {
            return true;
        }

        $this->dispatch('toast', message: __('griglia::t.msg.title_too_long', ['max' => self::titleMax(), 'n' => mb_strlen($title)]), type: 'error');

        return false;
    }

    public function startInsert(int $position): void
    {
        $this->insertAt = $position;
        $this->newTitle = '';
    }

    public function cancelInsert(): void
    {
        $this->insertAt = null;
        $this->newTitle = '';
    }

    public function saveInsert(): void
    {
        $title = trim($this->newTitle);

        if ($title === '' || $this->insertAt === null || ! $this->titleFits($title)) {
            return;
        }

        // Fa spazio: tutti i todo della lista dalla posizione in poi scalano di uno.
        $this->active()->where('order', '>=', $this->insertAt)->increment('order');

        Todo::create([
            'title' => $title,
            'order' => $this->insertAt,
            'completed' => false,
            'checklist_id' => Checklist::currentId(),
        ]);

        $this->cancelInsert();
        $this->dispatch('toast', message: __('griglia::t.msg.added', ['title' => $title]));
    }

    /** @param array<int, int|string> $orderedIds Id dei todo nell'ordine mostrato dopo il drag. */
    public function reorder(array $orderedIds): void
    {
        if ($this->showArchived || $this->isFiltering()) {
            return;
        }

        foreach ($orderedIds as $index => $id) {
            $this->active()->whereKey($id)->update(['order' => $index + 1]);
        }
        $this->rechainPlan();
    }

    /** Plan lists: the chain follows the list order — each task depends on the previous one (by `order`). */
    protected function rechainPlan(): void
    {
        if (! $this->planStatus()) {
            return;
        }
        $prev = null;
        foreach ($this->active()->orderBy('order')->orderBy('id')->get(['id', 'depends_on_id']) as $t) {
            $dep = $prev?->id;
            if ((int) $t->depends_on_id !== (int) $dep) {
                Todo::whereKey($t->id)->update(['depends_on_id' => $dep]);
            }
            $prev = $t;
        }
    }

    public function delete(int $todoId): void
    {
        $todo = $this->scoped()->findOrFail($todoId);
        if ($todo->working) { return; }
        // Resume chain: chi era «ripreso» da questo passa al nonno, così lo storico non si spezza (task 416)
        Todo::where('parent_id', $todo->id)->update(['parent_id' => $todo->parent_id]);
        $todo->delete();

        // Richiude il buco lasciato dall'elemento eliminato (solo se era attivo: gli archiviati non hanno posto in numerazione)
        if (! $todo->archived_at) {
            $this->active()->where('order', '>', $todo->order)->decrement('order');
        }
        $this->dispatch('toast', message: __('griglia::t.msg.deleted', ['title' => $todo->title]), type: 'info');
    }

    /** Proprietario delle liste: identifica il canale privato Reverb su cui arrivano gli aggiornamenti. */
    public int $userId = 0;

    public function boot(): void
    {
        // In boot() (non mount): le sottoclassi con mount($theme) resterebbero incompatibili
        $this->userId = (int) auth()->id();
    }

    /**
     * Aggiornamento live: un todo della lista è cambiato altrove (comando artisan
     * dell'assistente, altro dispositivo). Se riguarda la lista corrente si ri-renderizza
     * lista e modale; se lo stato è stato cambiato da console, lo si dice con un toast.
     */
    /** Listeners: the private broadcast channel comes from config (griglia.broadcast_channel). */
    protected function getListeners(): array
    {

        return [
            \Alle80\Griglia\Mode::echoListener() => 'onTodoChanged',
            'live-resync' => 'resync',
            'ingredients-updated' => 'refreshList',
            'resume-todo' => 'resume',
            'cmd-archive' => 'archive',
            'cmd-delete' => 'delete',
        ];
    }

    public function onTodoChanged(array $event = []): void
    {
        if ((int) ($event['checklist_id'] ?? 0) !== Checklist::currentId()) {
            return;
        }

        $this->dispatch('todo-changed-live'); // il modale, se aperto, si aggiorna

        if (($event['source'] ?? '') === 'cli' && ! empty($event['state_changed']) && app(\Alle80\Griglia\Settings\AppSettings::class)->toast_console_changes) {
            $title = (string) ($event['title'] ?? '');
            [$key, $type] = match ($event['state'] ?? '') {
                'working' => ['agent_working', 'info'],
                'done' => ['agent_done', 'success'],
                'question' => ['agent_question', 'info'],
                default => ['agent_updated', 'info'],
            };
            $message = __('griglia::t.msg.'.$key, ['title' => $title]);
            $this->dispatch('toast', message: $message, type: $type);
        }
    }

    /** Ri-sincronizzazione dopo background/riconnessione (vedi resources/js/echo.js): basta ri-renderizzare. */
    public function resync(): void
    {
        $this->dispatch('todo-changed-live');
    }

    public function refreshList(): void
    {
        // Vuoto di proposito: l'evento forza il re-render della lista
        // così i contatori sotto-task restano allineati al modal.
    }

    public function render()
    {
        // Default: the generic themed view with the default theme (dedicated styles override render())
        $t = Themes::get(Themes::default());

        return view('griglia::livewire.todo-list', [
            'todos' => $this->todos(),
            't' => $t,
            'theme' => Themes::default(),
            'listName' => $this->listName(),
            'archivedCount' => $this->archivedCount(),
            'filtering' => $this->isFiltering(),
            'plan' => $this->planStatus(),
            'listAgent' => (string) (Checklist::find(Checklist::currentId())?->agent ?? ''),
        ])->layout('griglia::layouts.themed', ['theme' => Themes::default()])->title($this->listName());
    }
}
