<?php

namespace Alle80\Griglia\Livewire;

use Alle80\Griglia\Http\Middleware\RememberStyle;
use Alle80\Griglia\Models\Checklist;
use Alle80\Griglia\Themes;
use Livewire\Attributes\Layout;

/**
 * Desktop dashboard: the same board, rendered wider and roomier for large screens.
 * Uses the current style when it is a generic theme, otherwise the default theme
 * (dedicated app styles have no shared CSS variables). Also embedded, responsively,
 * in the slide-out board tab (<x-griglia::board-tab />).
 */
#[Layout('griglia::layouts.themed')]
class DashboardTodoList extends TodoList
{
    public string $theme;

    public function mount(): void
    {
        $current = RememberStyle::current();
        $this->theme = Themes::has($current) ? $current : Themes::default();
    }

    public function render()
    {
        $t = Themes::get($this->theme);

        return view('griglia::livewire.todo-list', [
            'todos' => $this->todos(),
            't' => $t,
            'listName' => $this->listName(),
            'archivedCount' => $this->archivedCount(),
            'filtering' => $this->isFiltering(),
            'plan' => $this->planStatus(),
            'listAgent' => (string) (Checklist::find(Checklist::currentId())?->agent ?? ''),
            'wide' => true,
        ])->title($this->listName().' — Dashboard');
    }
}
