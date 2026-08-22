<?php

namespace Alle80\Griglia\Livewire;

use Alle80\Griglia\Settings\AppSettings;
use Alle80\Griglia\Themes;
use Livewire\Attributes\Layout;

#[Layout('griglia::layouts.themed')]
class ThemedTodoList extends TodoList
{
    public string $theme;

    public function mount(): void
    {
        $configured = app(AppSettings::class)->default_style;
        $this->theme = Themes::has($configured) ? $configured : Themes::default();
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
            'listAgent' => (string) (\Alle80\Griglia\Models\Checklist::find(\Alle80\Griglia\Models\Checklist::currentId())?->agent ?? ''),
        ])->title($this->listName().' — '.$t['label']);
    }
}
