<?php

namespace Alle80\Devboard\Livewire;

use Alle80\Devboard\Http\Middleware\RememberStyle;
use Alle80\Devboard\Themes;
use Livewire\Attributes\Layout;

/**
 * Desktop dashboard: the same board, rendered wider and roomier for large screens.
 * Uses the current style when it is a generic theme, otherwise the default theme
 * (dedicated app styles have no shared CSS variables). Also embedded, responsively,
 * in the slide-out board tab (<x-devboard::board-tab />).
 */
#[Layout('devboard::layouts.themed')]
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

        return view('devboard::livewire.todo-list', [
            'todos' => $this->todos(),
            't' => $t,
            'listName' => $this->listName(),
            'archivedCount' => $this->archivedCount(),
            'filtering' => $this->isFiltering(),
            'plan' => $this->planStatus(),
            'wide' => true,
        ])->title($this->listName().' — Dashboard');
    }
}
