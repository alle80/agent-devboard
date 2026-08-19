<?php

namespace Alle80\Devboard\Livewire;

use Alle80\Devboard\Themes;
use Livewire\Attributes\Layout;

#[Layout('devboard::layouts.themed')]
class ThemedTodoList extends TodoList
{
    public string $theme;

    public function mount(string $theme): void
    {
        abort_unless(Themes::has($theme), 404);
        $this->theme = $theme;
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
            'listAgent' => (string) (\Alle80\Devboard\Models\Checklist::find(\Alle80\Devboard\Models\Checklist::currentId())?->agent ?? ''),
        ])->title($this->listName().' — '.$t['label']);
    }
}
