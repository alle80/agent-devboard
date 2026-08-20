<?php

namespace Alle80\Griglia\Livewire;

use Alle80\Griglia\Agent;
use Alle80\Griglia\Http\Middleware\RememberStyle;
use Alle80\Griglia\Models\Checklist;
use Alle80\Griglia\Support\Plan;
use Alle80\Griglia\Themes;
use Livewire\Component;

/**
 * /plans/new — building a plan needs room to write, an explicit wait while the AI answers and a result to
 * look at: it is a page, not the cramped textarea that used to live in the lists dropdown (see the decision
 * in docs/agents/plan-creation-ui.md of the origin repository).
 *
 * /plans/{list}/edit — the same page on an existing plan: change the goal, and optionally rebuild the tasks
 * that have not been started. Tasks already done or in the agent's hands are never touched.
 */
class PlanPage extends Component
{
    /** Session key of the draft: leaving the page must not lose what was written. */
    protected const DRAFT = 'griglia_plan_draft';

    /** The goal, in the user's words: this is the field that matters. */
    public string $prompt = '';

    /** Optional: derived from the goal when left empty. */
    public string $name = '';

    /** Agent of the list, when more than one is configured ('' = the default one). */
    public string $agent = '';

    /** The plan being edited, or null when creating a new one. */
    public ?int $listId = null;

    /** Where «cancel» goes back to. */
    public string $returnTo = '';

    public function mount(?Checklist $list = null): void
    {
        $this->returnTo = $this->backUrl();

        if ($list?->exists) {
            abort_unless(Checklist::mine()->whereKey($list->id)->exists(), 404);

            $this->listId = $list->id;
            $this->prompt = (string) $list->plan_prompt;
            $this->name = (string) $list->name;
            $this->agent = (string) ($list->agent ?? '');

            return;
        }

        $draft = (array) session(self::DRAFT, []);
        $this->prompt = (string) ($draft['prompt'] ?? '');
        $this->name = (string) ($draft['name'] ?? '');
        $this->agent = (string) ($draft['agent'] ?? '');
    }

    /** Every keystroke that reaches the server is also kept as a draft (only when creating). */
    public function updated(): void
    {
        if ($this->listId === null) {
            session([self::DRAFT => ['prompt' => $this->prompt, 'name' => $this->name, 'agent' => $this->agent]]);
        }
    }

    public function create(): void
    {
        $prompt = trim($this->prompt);

        if (mb_strlen($prompt) < 10) {
            $this->addError('prompt', __('griglia::t.plan.goal_too_short'));

            return;
        }

        $agents = Agent::all();

        $list = Checklist::create([
            'name' => $this->listName($prompt),
            'user_id' => auth()->id(),
            'agent' => isset($agents[$this->agent]) ? $this->agent : null,
        ]);

        try {
            $built = Plan::build($list, $prompt);
        } catch (\Throwable $e) {
            // Never leave a half-created list behind: the text stays here, the user can try again.
            report($e);
            $list->forceDelete();
            $this->addError('prompt', __('griglia::t.plan.build_failed'));

            return;
        }

        session()->forget(self::DRAFT);
        session(['checklist_id' => $list->id]);
        session()->flash('griglia_toast', $built > 0
            ? ['message' => __('griglia::t.plan.built', ['count' => $built]), 'type' => 'success']
            : ['message' => __('griglia::t.plan.not_built'), 'type' => 'info']);

        $this->redirect(Themes::url(RememberStyle::current()), navigate: false);
    }

    /** Edit mode: only the goal (and the name / agent of the list); the tasks stay as they are. */
    public function saveGoal(): void
    {
        $list = $this->editedList();
        $prompt = trim($this->prompt);

        if (mb_strlen($prompt) < 10) {
            $this->addError('prompt', __('griglia::t.plan.goal_too_short'));

            return;
        }

        $agents = Agent::all();
        $list->update([
            'plan_prompt' => $prompt,
            'name' => trim($this->name) !== '' ? mb_substr(trim($this->name), 0, 60) : $list->name,
            'agent' => isset($agents[$this->agent]) ? $this->agent : null,
        ]);

        $this->dispatch('toast', message: __('griglia::t.plan.goal_saved'), type: 'success');
    }

    /** Edit mode: rebuild the tasks that nobody has started yet, keeping the rest of the plan. */
    public function rebuild(): void
    {
        $list = $this->editedList();
        $prompt = trim($this->prompt);

        if (mb_strlen($prompt) < 10) {
            $this->addError('prompt', __('griglia::t.plan.goal_too_short'));

            return;
        }

        $this->untouched($list)->each->forceDelete();

        try {
            $built = Plan::build($list, $prompt);
        } catch (\Throwable $e) {
            report($e);
            $this->addError('prompt', __('griglia::t.plan.build_failed'));

            return;
        }

        session(['checklist_id' => $list->id]);
        session()->flash('griglia_toast', ['message' => __('griglia::t.plan.rebuilt', ['count' => $built]), 'type' => 'success']);
        $this->redirect(Themes::url(RememberStyle::current()), navigate: false);
    }

    public function cancel(): void
    {
        session()->forget(self::DRAFT);
        $this->redirect($this->returnTo ?: Themes::url(RememberStyle::current()), navigate: false);
    }

    /** Tasks nobody has started: not done, not taken, not waiting for an answer. */
    protected function untouched(Checklist $list)
    {
        return $list->todos()
            ->where('completed', false)
            ->where('working', false)
            ->where('question', false)
            ->whereNull('working_since')
            ->get();
    }

    protected function editedList(): Checklist
    {
        $list = Checklist::mine()->whereKey($this->listId)->first();
        abort_unless($list !== null, 404);

        return $list;
    }

    /** The page we came from, when it is one of ours; the board otherwise. */
    protected function backUrl(): string
    {
        $previous = (string) url()->previous();
        $home = Themes::url(RememberStyle::current());

        if ($previous === '' || ! str_starts_with($previous, url('/'))) {
            return $home;
        }

        return str_contains($previous, '/plans/') ? $home : $previous;
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
        $list = $this->listId ? Checklist::mine()->whereKey($this->listId)->first() : null;

        return view('griglia::livewire.plan-page', [
            'skin' => $skin,
            'aiAvailable' => Plan::available(),
            'agents' => Agent::all(),
            'list' => $list,
            'untouchedCount' => $list ? $this->untouched($list)->count() : 0,
        ])->layout($skin['layout'], $skin['layoutData'] + ['title' => __('griglia::t.plan.page_title')])
            ->title($this->listId ? __('griglia::t.plan.edit_title') : __('griglia::t.plan.page_title'));
    }
}
