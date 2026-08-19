<?php

namespace Alle80\Devboard\Livewire;

use Alle80\Devboard\Models\Checklist;
use Alle80\Devboard\Models\Todo;
use Alle80\Devboard\Themes;
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

    /** Filtro di stato: all | todo | done | otw | working | question */
    public string $filter = 'all';

    /** Lunghezza massima del titolo di un todo: default 50, modificabile da /settings. */
    public const TITLE_MAX = 50;

    public static function titleMax(): int
    {
        return (int) (app(\Alle80\Devboard\Settings\AppSettings::class)->title_max_length ?: self::TITLE_MAX);
    }

    /** Filter keys (labels come from the translations: devboard::t.filters). */
    public const FILTERS = ['all', 'todo', 'done', 'otw', 'working', 'question'];

    /** key => translated label */
    public static function filters(): array
    {
        $labels = (array) __('devboard::t.filters');

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

    /** Query dei todo della lista corrente. */
    protected function scoped(): Builder
    {
        return Todo::where('checklist_id', Checklist::currentId());
    }

    /** Todo della lista corrente, ordinati, con sotto-task: usato dai render di tutte le varianti. */
    protected function todos(): Collection
    {
        return $this->applyFilters($this->scoped())
            ->when($this->showArchived, fn ($q) => $q->whereNotNull('archived_at')->orderByDesc('archived_at'), fn ($q) => $q->whereNull('archived_at')->orderBy('order'))
            ->with('ingredients')->withCount('attachments')->get();
    }

    /** Query dei todo attivi (non archiviati) della lista corrente: la numerazione `order` vive solo qui. */
    protected function active(): Builder
    {
        return $this->scoped()->whereNull('archived_at');
    }

    protected function archivedCount(): int
    {
        return $this->scoped()->whereNotNull('archived_at')->count();
    }

    public function toggleArchived(): void
    {
        $this->showArchived = ! $this->showArchived;
        $this->cancelInsert();
        $this->cancelEdit();
    }

    public function archive(int $todoId): void
    {
        $todo = $this->active()->findOrFail($todoId);
        $todo->update(['archived_at' => now()]);

        // Richiude il buco nella numerazione degli attivi
        $this->active()->where('order', '>', $todo->order)->decrement('order');
        $this->dispatch('toast', message: __('devboard::t.msg.archived', ['title' => $todo->title]), type: 'info');
    }

    public function unarchive(int $todoId): void
    {
        $todo = $this->scoped()->whereNotNull('archived_at')->findOrFail($todoId);
        $todo->update(['archived_at' => null, 'order' => ((int) $this->active()->max('order')) + 1]);
        $this->dispatch('toast', message: __('devboard::t.msg.restored', ['title' => $todo->title]));
    }

    /** Nome della lista corrente: è il titolo di tutte le pagine. */
    protected function listName(): string
    {
        return Checklist::findOrFail(Checklist::currentId())->name;
    }

    public function toggle(int $todoId): void
    {
        $todo = $this->scoped()->findOrFail($todoId);
        $todo->completed = ! $todo->completed;
        $todo->save();

        $this->dispatch('toast', message: __($todo->completed ? 'devboard::t.msg.completed' : 'devboard::t.msg.reopened', ['title' => $todo->title]), type: $todo->completed ? 'success' : 'info');
    }

    public function toggleOpenToWork(int $todoId): void
    {
        $todo = $this->scoped()->findOrFail($todoId);

        // Con domande aperte il pallino porta al modale per rispondere
        if ($todo->question) {
            $this->dispatch('open-ingredients', todoId: $todo->id);

            return;
        }

        // In lavorazione (🔧): il click ferma il lavoro dell'assistente → torna ⚪ e resta traccia in stopped_at
        if ($todo->working) {
            $todo->update(['working' => false, 'open_to_work' => false, 'stopped_at' => now()]);
            $this->dispatch('toast', message: __('devboard::t.msg.stopped', ['title' => $todo->title]), type: 'info');

            return;
        }

        $todo->open_to_work = ! $todo->open_to_work;
        if ($todo->open_to_work) {
            $todo->stopped_at = null; // rimesso 🟢: lo stop non vale più
        }
        $todo->save();

        $this->dispatch('toast', message: __($todo->open_to_work ? 'devboard::t.msg.otw_on' : 'devboard::t.msg.otw_off', ['title' => $todo->title]), type: $todo->open_to_work ? 'success' : 'info');
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
            $this->dispatch('toast', message: __('devboard::t.msg.resume_only_done'), type: 'error');

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

        $this->dispatch('toast', message: __('devboard::t.msg.resumed'));
        $this->dispatch('open-ingredients', todoId: $new->id);
    }

    public function startEdit(int $todoId): void
    {
        $todo = $this->scoped()->findOrFail($todoId);
        $this->editingId = $todo->id;
        $this->titleDraft = $todo->title;
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
        $this->titleDraft = '';
    }

    public function saveEdit(): void
    {
        $title = trim($this->titleDraft);

        if ($title === '' || ! $this->editingId || ! $this->titleFits($title)) {
            return;
        }

        $this->scoped()->whereKey($this->editingId)->update(['title' => $title]);
        $this->cancelEdit();
        $this->dispatch('toast', message: __('devboard::t.msg.renamed'));
    }

    /** Titolo entro il limite? Altrimenti avvisa e non salva (niente troncamenti silenziosi). */
    protected function titleFits(string $title): bool
    {
        if (mb_strlen($title) <= self::titleMax()) {
            return true;
        }

        $this->dispatch('toast', message: __('devboard::t.msg.title_too_long', ['max' => self::titleMax(), 'n' => mb_strlen($title)]), type: 'error');

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
        $this->dispatch('toast', message: __('devboard::t.msg.added', ['title' => $title]));
    }

    /** "New task" button: create a blank todo at the end and open its modal straight in title editing. */
    public function createAndOpen(): void
    {
        $todo = Todo::create([
            'title' => '',
            'order' => ((int) $this->active()->max('order')) + 1,
            'completed' => false,
            'checklist_id' => Checklist::currentId(),
        ]);

        $this->dispatch('open-ingredients', todoId: $todo->id, edit: true);
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
    }

    public function delete(int $todoId): void
    {
        $todo = $this->scoped()->findOrFail($todoId);
        $todo->delete();

        // Richiude il buco lasciato dall'elemento eliminato (solo se era attivo: gli archiviati non hanno posto in numerazione)
        if (! $todo->archived_at) {
            $this->active()->where('order', '>', $todo->order)->decrement('order');
        }
        $this->dispatch('toast', message: __('devboard::t.msg.deleted', ['title' => $todo->title]), type: 'info');
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
    /** Listeners: the private broadcast channel comes from config (devboard.broadcast_channel). */
    protected function getListeners(): array
    {
        $channel = str_replace('{id}', '{userId}', (string) config('devboard.broadcast_channel', 'App.Models.User.{id}'));

        return [
            'echo-private:'.$channel.',.TodoChanged' => 'onTodoChanged',
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

        if (($event['source'] ?? '') === 'cli' && ! empty($event['state_changed']) && app(\Alle80\Devboard\Settings\AppSettings::class)->toast_console_changes) {
            $title = (string) ($event['title'] ?? '');
            [$key, $type] = match ($event['state'] ?? '') {
                'working' => ['agent_working', 'info'],
                'done' => ['agent_done', 'success'],
                'question' => ['agent_question', 'info'],
                default => ['agent_updated', 'info'],
            };
            $message = __('devboard::t.msg.'.$key, ['title' => $title]);
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

        return view('devboard::livewire.todo-list', [
            'todos' => $this->todos(),
            't' => $t,
            'theme' => Themes::default(),
            'listName' => $this->listName(),
            'archivedCount' => $this->archivedCount(),
            'filtering' => $this->isFiltering(),
        ])->layout('devboard::layouts.themed', ['theme' => Themes::default()])->title($this->listName());
    }
}
