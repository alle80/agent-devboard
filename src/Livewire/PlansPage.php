<?php

namespace Alle80\Griglia\Livewire;

use Alle80\Griglia\Http\Middleware\RememberStyle;
use Alle80\Griglia\Models\Checklist;
use Alle80\Griglia\Themes;
use Livewire\Component;

/** /plans — overview and controls for every active plan owned by the current user. */
class PlansPage extends Component
{
    public function open(int $checklistId): void
    {
        $list = $this->plan($checklistId);
        session(['checklist_id' => $list->id]);
        $this->redirect(Themes::url(RememberStyle::current()), navigate: false);
    }

    public function start(int $checklistId): void
    {
        $list = $this->plan($checklistId);
        $list->update(['plan_paused' => false]);
        $next = $list->todos()->whereNull('archived_at')->orderBy('order')->get()
            ->first(fn ($todo) => ! $todo->completed && ! $todo->open_to_work && ! $todo->working && ! $todo->paused && ! $todo->question);
        if ($next) {
            $next->update(['open_to_work' => true, 'stopped_at' => null]);
            $this->dispatch('toast', message: __('griglia::t.plan.started', ['title' => $next->title]), type: 'success');
        }
    }

    public function pause(int $checklistId): void
    {
        $list = $this->plan($checklistId);
        $list->update(['plan_paused' => true]);
        $list->todos()->whereNull('archived_at')->where('completed', false)
            ->where('open_to_work', true)->where('working', false)->update(['open_to_work' => false]);
        $this->dispatch('toast', message: __('griglia::t.plan.paused'), type: 'info');
    }

    protected function plan(int $checklistId): Checklist
    {
        $list = Checklist::mine()->whereKey($checklistId)->firstOrFail();
        abort_unless($list->plan_prompt || $list->todos()->whereNotNull('depends_on_id')->exists(), 404);
        return $list;
    }

    public function render()
    {
        $style = RememberStyle::current();
        $skin = Themes::settingsSkin($style);
        $plans = Checklist::mine()->where(function ($query) {
            $query->whereNotNull('plan_prompt')->orWhereHas('todos', fn ($todos) => $todos->whereNotNull('depends_on_id'));
        })->withCount([
            'todos' => fn ($query) => $query->whereNull('archived_at'),
            'todos as done_count' => fn ($query) => $query->whereNull('archived_at')->where('completed', true),
            'todos as running_count' => fn ($query) => $query->whereNull('archived_at')->where('completed', false)->where(fn ($state) => $state->where('open_to_work', true)->orWhere('working', true)->orWhere('paused', true)->orWhere('question', true)),
        ])->orderByDesc('id')->get();

        return view('griglia::livewire.plans-page', ['skin' => $skin, 'plans' => $plans])
            ->layout($skin['layout'], $skin['layoutData'] + ['title' => __('griglia::t.plan.index_title')])
            ->title(__('griglia::t.plan.index_title'));
    }
}
