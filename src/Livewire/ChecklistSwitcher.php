<?php

namespace Alle80\Griglia\Livewire;

use Alle80\Griglia\Models\Checklist;
use Livewire\Component;

class ChecklistSwitcher extends Component
{
    public string $newName = '';

    /** Lista in rinomina e relativa bozza. */
    public ?int $editingId = null;

    public string $nameDraft = '';

    /** Vista archivio: il menu elenca le liste archiviate invece di quelle attive. */
    public bool $showArchived = false;

    public function startRename(int $checklistId): void
    {
        $list = Checklist::mine()->findOrFail($checklistId);
        $this->editingId = $list->id;
        $this->nameDraft = $list->name;
    }

    public function cancelRename(): void
    {
        $this->editingId = null;
        $this->nameDraft = '';
    }

    public function saveRename(): void
    {
        $name = trim($this->nameDraft);

        if ($name === '' || ! $this->editingId) {
            return;
        }

        Checklist::mine()->whereKey($this->editingId)->update(['name' => $name]);

        $wasCurrent = $this->editingId === Checklist::currentId();
        $this->cancelRename();
        $this->dispatch('toast', message: __('griglia::t.msg.list_renamed'));

        // Il nome della lista corrente è il titolo della pagina: ricarico per aggiornarlo ovunque
        if ($wasCurrent) {
            $this->js('window.location.reload()');
        }
    }

    public function switchTo(int $checklistId): void
    {
        if (! Checklist::mine()->whereKey($checklistId)->exists()) {
            return;
        }

        session(['checklist_id' => $checklistId]);
        $this->js('window.location.reload()');
    }

    /** Plan mode: the new list is built from a prompt (chained tasks). */


    public function create(): void
    {
        $name = trim($this->newName);

        if ($name === '') {
            return;
        }
        // A plan is written on its own page (/plans/new): here we only create plain lists.
        $list = Checklist::create(['name' => $name, 'user_id' => auth()->id()]);
        session(['checklist_id' => $list->id]);
        $this->js('window.location.reload()');
    }

    public function toggleArchived(): void
    {
        $this->showArchived = ! $this->showArchived;
        $this->cancelRename();
    }

    /** Archivia una lista: sparisce dal menu, i suoi task restano. */
    public function archiveList(int $checklistId): void
    {
        // Come per l'eliminazione: l'ultima lista attiva non si archivia
        if (Checklist::mine()->count() <= 1) {
            $this->dispatch('toast', message: __('griglia::t.msg.list_archive_last'), type: 'error');

            return;
        }

        $list = Checklist::mine()->whereKey($checklistId)->first();
        if (! $list) {
            return;
        }

        $list->update(['archived_at' => now()]);
        $this->dispatch('toast', message: __('griglia::t.msg.list_archived', ['name' => $list->name]), type: 'info');

        if ((int) session('checklist_id') === $checklistId) {
            session()->forget('checklist_id');
            $this->js('window.location.reload()');
        }
    }

    /** Riporta una lista archiviata tra quelle attive. */
    public function restoreList(int $checklistId): void
    {
        $list = Checklist::mineArchived()->whereKey($checklistId)->first();
        if (! $list) {
            return;
        }

        $list->update(['archived_at' => null]);
        $this->dispatch('toast', message: __('griglia::t.msg.list_restored', ['name' => $list->name]));
    }

    public function deleteList(int $checklistId): void
    {
        // L'ultima lista attiva non si tocca (le archiviate si possono sempre eliminare)
        $archived = Checklist::mineArchived()->whereKey($checklistId)->exists();
        if (! $archived && Checklist::mine()->count() <= 1) {
            return;
        }

        $list = Checklist::mineWithArchived()->whereKey($checklistId)->first();
        $list?->delete();
        $this->dispatch('toast', message: __('griglia::t.msg.list_deleted', ['name' => $list?->name]), type: 'info');

        if ((int) session('checklist_id') === $checklistId) {
            session()->forget('checklist_id');
            $this->js('window.location.reload()');
        }
    }

    /** Start a plan list from the menu: first not-started task → open to work, then switch to that list. */
    public function startPlan(int $checklistId): void
    {
        $list = Checklist::mine()->whereKey($checklistId)->first();
        if (! $list) {
            return;
        }
        $next = $list->todos()->whereNull('archived_at')->orderBy('order')->get()
            ->first(fn ($t) => ! $t->completed && ! $t->open_to_work && ! $t->working && ! $t->paused && ! $t->question);
        if ($next) {
            $next->update(['open_to_work' => true, 'stopped_at' => null]);
        }
        session(['checklist_id' => $list->id]);
        $this->js('window.location.reload()');
    }

    public function render()
    {
        $lists = ($this->showArchived ? Checklist::mineArchived() : Checklist::mine())->withCount([
            'todos' => fn ($q) => $q->whereNull('archived_at'),
            'todos as done_count' => fn ($q) => $q->whereNull('archived_at')->where('completed', true),
            'todos as chained_count' => fn ($q) => $q->whereNull('archived_at')->whereNotNull('depends_on_id'),
            'todos as running_count' => fn ($q) => $q->whereNull('archived_at')->where('completed', false)->where(fn ($w) => $w->where('open_to_work', true)->orWhere('working', true)->orWhere('paused', true)->orWhere('question', true)),
        ])->orderBy('id')->get();

        return view('griglia::livewire.checklist-switcher', [
            'lists' => $lists,
            'currentId' => Checklist::currentId(),
            'archivedCount' => Checklist::mineArchived()->count(),
        ]);
    }
}
