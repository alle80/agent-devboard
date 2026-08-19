<?php

namespace Alle80\Devboard\Livewire;

use Alle80\Devboard\Models\Checklist;
use Livewire\Component;

class ChecklistSwitcher extends Component
{
    public string $newName = '';

    /** Lista in rinomina e relativa bozza. */
    public ?int $editingId = null;

    public string $nameDraft = '';

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
        $this->dispatch('toast', message: __('devboard::t.msg.list_renamed'));

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
    public bool $asPlan = false;

    public string $planPrompt = '';

    public function create(): void
    {
        $name = trim($this->newName);

        if ($name === '') {
            return;
        }
        if ($this->asPlan && trim($this->planPrompt) === '') {
            $this->dispatch('toast', message: __('devboard::t.plan.prompt_required'), type: 'error');

            return;
        }

        $list = Checklist::create(['name' => $name, 'user_id' => auth()->id()]);
        if ($this->asPlan) {
            $n = \Alle80\Devboard\Support\Plan::build($list, trim($this->planPrompt));
            session()->flash('devboard_toast', $n > 0 ? ['message' => __('devboard::t.plan.built', ['count' => $n]), 'type' => 'success'] : ['message' => __('devboard::t.plan.not_built'), 'type' => 'info']);
        }
        session(['checklist_id' => $list->id]);
        $this->js('window.location.reload()');
    }

    public function deleteList(int $checklistId): void
    {
        // L'ultima lista rimasta non si tocca
        if (Checklist::mine()->count() <= 1) {
            return;
        }

        $list = Checklist::mine()->whereKey($checklistId)->first();
        $list?->delete();
        $this->dispatch('toast', message: __('devboard::t.msg.list_deleted', ['name' => $list?->name]), type: 'info');

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
            ->first(fn ($t) => ! $t->completed && ! $t->open_to_work && ! $t->working && ! $t->question);
        if ($next) {
            $next->update(['open_to_work' => true, 'stopped_at' => null]);
        }
        session(['checklist_id' => $list->id]);
        $this->js('window.location.reload()');
    }

    public function render()
    {
        $lists = Checklist::mine()->withCount([
            'todos' => fn ($q) => $q->whereNull('archived_at'),
            'todos as done_count' => fn ($q) => $q->whereNull('archived_at')->where('completed', true),
            'todos as chained_count' => fn ($q) => $q->whereNull('archived_at')->whereNotNull('depends_on_id'),
            'todos as running_count' => fn ($q) => $q->whereNull('archived_at')->where('completed', false)->where(fn ($w) => $w->where('open_to_work', true)->orWhere('working', true)->orWhere('question', true)),
        ])->orderBy('id')->get();

        return view('devboard::livewire.checklist-switcher', [
            'lists' => $lists,
            'currentId' => Checklist::currentId(),
        ]);
    }
}
