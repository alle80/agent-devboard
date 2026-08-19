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

    public function mount(): void
    {
        if (! $this->listId || ! Checklist::mine()->whereKey($this->listId)->exists()) {
            $this->listId = Checklist::currentId();
        }
        if (! in_array($this->days, [7, 30, 90, 365, 0], true)) {
            $this->days = 30;
        }
    }

    public function setList(int $id): void
    {
        if (Checklist::mine()->whereKey($id)->exists()) {
            $this->listId = $id;
        }
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
        $list = $lists->firstWhere('id', $this->listId) ?? $lists->first();
        $from = $this->days > 0 ? CarbonImmutable::today()->subDays($this->days - 1) : null;
        $rows = $list ? Stats::history($list, $from) : collect();
        $allRows = $list ? Stats::history($list) : collect();

        return view('devboard::livewire.stats-page', [
            'skin' => $skin,
            'lists' => $lists,
            'list' => $list,
            'rows' => $rows,
            'agg' => Stats::aggregate($rows),
            'aggAll' => Stats::aggregate($allRows),
            'series' => Stats::series($rows, $this->days > 0 ? min($this->days, 90) : 90),
            'overview' => Stats::overview($lists, $from),
            'prices' => Stats::prices(),
            'currency' => Stats::currency(),
        ])->layout($skin['layout'], $skin['layoutData'] + ['title' => 'Stats'])->title(__('devboard::t.stats_page.title'));
    }
}
