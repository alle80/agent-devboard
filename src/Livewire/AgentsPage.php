<?php

namespace Alle80\Devboard\Livewire;

use Alle80\Devboard\Http\Middleware\RememberStyle;
use Alle80\Devboard\Support\AgentStatus;
use Alle80\Devboard\Themes;
use Livewire\Component;

/** /agents — plan and usage windows of the coding agents (snapshot from the host), refreshed every minute. */
class AgentsPage extends Component
{
    public function render()
    {
        $style = RememberStyle::current();
        $skin = Themes::settingsSkin($style);

        return view('devboard::livewire.agents-page', [
            'skin' => $skin,
            'status' => AgentStatus::agents(),
        ])->layout($skin['layout'], $skin['layoutData'] + ['title' => 'Agents'])->title(__('devboard::t.agents.title'));
    }
}
