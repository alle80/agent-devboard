<?php

namespace Alle80\Griglia\Livewire;

use Alle80\Griglia\Http\Middleware\RememberStyle;
use Alle80\Griglia\Support\AgentStatus;
use Alle80\Griglia\Themes;
use Livewire\Component;

/** /agents — plan and usage windows of the coding agents (snapshot from the host), refreshed every minute. */
class AgentsPage extends Component
{
    public function render()
    {
        $style = RememberStyle::current();
        $skin = Themes::settingsSkin($style);

        return view('griglia::livewire.agents-page', [
            'skin' => $skin,
            'status' => AgentStatus::agents(),
        ])->layout($skin['layout'], $skin['layoutData'] + ['title' => 'Agents'])->title(__('griglia::t.agents.title'));
    }
}
