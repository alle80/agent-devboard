<?php

namespace Alle80\Devboard\Livewire;

use Alle80\Devboard\Http\Middleware\RememberStyle;
use Alle80\Devboard\Models\Checklist;
use Alle80\Devboard\Support\Stats;
use Alle80\Devboard\Themes;
use Carbon\CarbonImmutable;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * /stats — statistics of a list (project): KPIs, per-day series and the history of completed tasks with
 * working time, tokens and cost (price list in /settings). Period filter; one list at a time + overview.
 */
class StatsPage extends Component
{
    #[Url(as: 'list')]
    public ?int $listId = null;

    /** Period in days (0 = all time). */
    #[Url]
    public int $days = 30;

    /** Special selections: 0 = all lists, -1 = all plan lists. */
    public const ALL = 0;

    public const PLANS = -1;

    public function mount(): void
    {
        if ($this->listId === null || ($this->listId > 0 && ! Checklist::mine()->whereKey($this->listId)->exists())) {
            $this->listId = Checklist::currentId();
        }
        if ($this->listId < 0 && $this->listId !== self::PLANS) {
            $this->listId = Checklist::currentId();
        }
        if (! in_array($this->days, [7, 30, 90, 365, 0], true)) {
            $this->days = 30;
        }
    }

    public function setList(int $id): void
    {
        if ($id === self::ALL || $id === self::PLANS || Checklist::mine()->whereKey($id)->exists()) {
            $this->listId = $id;
        }
    }

    /** Lists covered by the current selection. */
    protected function selectedLists($lists)
    {
        if ($this->listId === self::ALL) {
            return $lists;
        }
        if ($this->listId === self::PLANS) {
            return $lists->filter(fn (Checklist $l) => $l->plan_prompt || $l->todos()->whereNotNull('depends_on_id')->exists())->values();
        }

        return $lists->where('id', $this->listId)->values();
    }

    public function setDays(int $days): void
    {
        $this->days = in_array($days, [7, 30, 90, 365, 0], true) ? $days : 30;
    }

    public function render()
    {
        $style = RememberStyle::current();
        $skin = Themes::settingsSkin($style);
        $lists = Checklist::mine()->orderBy('id')->get();
        $selected = $this->selectedLists($lists);
        if ($selected->isEmpty() && $lists->isNotEmpty() && $this->listId > 0) {
            $this->listId = $lists->first()->id;
            $selected = $lists->take(1);
        }
        $list = $selected->count() === 1 && $this->listId > 0 ? $selected->first() : null;
        $from = $this->days > 0 ? CarbonImmutable::today()->subDays($this->days - 1) : null;
        $merge = fn ($f) => $selected->flatMap(fn (Checklist $l) => Stats::history($l, $f))->sortByDesc(fn ($r) => $r['todo']->completed_at?->timestamp ?? 0)->values();
        $rows = $merge($from);
        $allRows = $merge(null);
        $plansCount = $lists->filter(fn (Checklist $l) => $l->plan_prompt || $l->todos()->whereNotNull('depends_on_id')->exists())->count();

        return view('devboard::livewire.stats-page', [
            'skin' => $skin,
            'lists' => $lists,
            'list' => $list,
            'selectedCount' => $selected->count(),
            'plansCount' => $plansCount,
            'selection' => $this->listId,
            'rows' => $rows,
            'agg' => Stats::aggregate($rows),
            'aggAll' => Stats::aggregate($allRows),
            'series' => Stats::series($rows, $this->days > 0 ? min($this->days, 60) : 60),
            'overview' => Stats::overview($lists, $from),
            'prices' => Stats::prices(),
            'currency' => Stats::currency(),
        ])->layout($skin['layout'], $skin['layoutData'] + ['title' => 'Stats'])->title(__('devboard::t.stats_page.title'));
    }
}
