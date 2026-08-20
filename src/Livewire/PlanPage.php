<?php

namespace Alle80\Griglia\Livewire;

use Alle80\Griglia\Http\Middleware\RememberStyle;
use Alle80\Griglia\Models\Checklist;
use Alle80\Griglia\Support\Plan;
use Alle80\Griglia\Themes;
use Livewire\Component;

/**
 * /plans/new — building a plan needs room to write, an explicit wait while the AI answers and a result to
 * look at: it is a page, not the cramped textarea that used to live in the lists dropdown (see the decision
 * in docs/agents/plan-creation-ui.md of the origin repository).
 */
class PlanPage extends Component
{
    /** The goal, in the user's words: this is the field that matters. */
    public string $prompt = '';

    /** Optional: derived from the goal when left empty. */
    public string $name = '';

    /** Agent of the list, when more than one is configured ('' = the default one). */
    public string $agent = '';

    public function create(): void
    {
        $prompt = trim($this->prompt);

        if (mb_strlen($prompt) < 10) {
            $this->addError('prompt', __('griglia::t.plan.goal_too_short'));

            return;
        }

        $agents = \Alle80\Griglia\Agent::all();

        $list = Checklist::create([
            'name' => $this->listName($prompt),
            'user_id' => auth()->id(),
            'agent' => isset($agents[$this->agent]) ? $this->agent : null,
        ]);

        $built = Plan::build($list, $prompt);

        session(['checklist_id' => $list->id]);
        session()->flash('griglia_toast', $built > 0
            ? ['message' => __('griglia::t.plan.built', ['count' => $built]), 'type' => 'success']
            : ['message' => __('griglia::t.plan.not_built'), 'type' => 'info']);

        $this->redirect(Themes::url(RememberStyle::current()), navigate: false);
    }

    /** The name typed by the user, or the first words of the goal. */
    protected function listName(string $prompt): string
    {
        $name = trim($this->name);

        if ($name !== '') {
            return mb_substr($name, 0, 60);
        }

        $first = preg_split('/(?<=[.!?])\s|\R/u', $prompt)[0] ?? $prompt;
        $words = preg_split('/\s+/u', trim($first)) ?: [];
        $name = trim(implode(' ', array_slice($words, 0, 6)), " \t\n\r\0\x0B.,;:—-");

        return $name !== '' ? mb_substr($name, 0, 60) : __('griglia::t.plan.label');
    }

    public function render()
    {
        $style = RememberStyle::current();
        $skin = Themes::settingsSkin($style);

        return view('griglia::livewire.plan-page', [
            'skin' => $skin,
            'aiAvailable' => Plan::available(),
            'agents' => \Alle80\Griglia\Agent::all(),
        ])->layout($skin['layout'], $skin['layoutData'] + ['title' => __('griglia::t.plan.page_title')])
            ->title(__('griglia::t.plan.page_title'));
    }
}
