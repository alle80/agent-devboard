{{-- Desktop: contenitore più largo e, da xl, storico affiancato a grafico e panoramica (task 321).
     Sotto xl resta la colonna unica di prima. --}}
<div class="mx-auto w-full max-w-3xl px-4 pt-24 pb-16 sm:pt-24 lg:max-w-5xl xl:max-w-7xl xl:px-8" style="{{ $skin['vars'] }}">
    <div class="mb-4 flex items-center justify-between gap-3">
        <h1 class="{{ $skin['h1'] }} db-ctx-h1">{{ __('griglia::t.stats_page.title') }}</h1>
        <a href="{{ $skin['home'] }}" class="{{ $skin['back'] }} inline-flex items-center gap-1"><x-griglia::icon name="arrow-left" /> {{ __('griglia::t.back_to_list') }}</a>
    </div>
    <p class="{{ $skin['sub'] }} mb-4">{{ __('griglia::t.stats_page.intro') }}</p>

    {{-- Selectors: list + period --}}
    <div class="{{ $skin['card'] }} mb-4 flex flex-wrap items-center gap-2">
        <label class="{{ $skin['label'] }} text-sm" for="stats-list">{{ __('griglia::t.stats_page.list') }}</label>
        <select id="stats-list" class="{{ $skin['input'] }} w-full min-w-0 text-sm sm:w-auto sm:flex-1" wire:change="setList($event.target.value)">
            <option value="0" @selected($selection === 0)>{{ __('griglia::t.stats_page.all_lists') }}</option>
            @if ($plansCount > 0)<option value="-1" @selected($selection === -1)>{{ __('griglia::t.stats_page.all_plans', ['n' => $plansCount]) }}</option>@endif
            @foreach ($lists as $l)
                <option value="{{ $l->id }}" @selected($selection === $l->id)>{{ $l->name }}@if ($l->trashed()) {{ __('griglia::t.stats_page.deleted_list') }}@endif</option>
            @endforeach
        </select>
        <span class="flex flex-wrap items-center gap-1 text-xs">
            @foreach ([7 => '7g', 30 => '30g', 90 => '90g', 365 => '1a', 0 => __('griglia::t.stats_page.all_time')] as $d => $lbl)
                <button type="button" wire:click="setDays({{ $d }})" class="{{ $days === $d ? 'tl-check tl-check-on tl-display' : 'tl-check tl-display' }} cursor-pointer px-2 py-1 leading-none" aria-pressed="{{ $days === $d ? 'true' : 'false' }}">{{ $lbl }}</button>
            @endforeach
        </span>
    </div>

    @if ($selectedCount === 0)
        <p class="{{ $skin['help'] }} text-center">{{ __('griglia::t.stats_page.no_list') }}</p>
    @else
        {{-- KPIs --}}
        <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach ([
                ['label' => __('griglia::t.stats_page.kpi_done'), 'value' => $agg['count'], 'sub' => __('griglia::t.stats_page.kpi_done_all', ['n' => $aggAll['count']])],
                ['label' => __('griglia::t.stats_page.kpi_time'), 'value' => \Alle80\Griglia\Support\Stats::duration($agg['timed_count'] ? $agg['work_seconds'] : null), 'sub' => $agg['avg_work_seconds'] !== null ? __('griglia::t.stats_page.kpi_avg', ['v' => \Alle80\Griglia\Support\Stats::duration($agg['avg_work_seconds'])]) : __('griglia::t.stats_page.untracked')],
                ['label' => __('griglia::t.stats_page.kpi_tokens'), 'value' => $agg['tokens_count'] ? \Alle80\Griglia\Models\Todo::formatTokens($agg['tokens_in'] + $agg['tokens_out']) : '—', 'sub' => $agg['tokens_count'] ? \Alle80\Griglia\Models\Todo::formatTokens($agg['tokens_in']).' in / '.\Alle80\Griglia\Models\Todo::formatTokens($agg['tokens_out']).' out' : __('griglia::t.stats_page.untracked')],
                ['label' => __('griglia::t.stats_page.kpi_cost'), 'value' => \Alle80\Griglia\Support\Stats::money($agg['cost']), 'sub' => ($prices[0] <= 0 && $prices[1] <= 0) ? __('griglia::t.stats_page.no_prices') : __('griglia::t.stats_page.kpi_costed', ['n' => $agg['costed_count'], 'total' => $agg['count']])],
            ] as $k)
                <div class="{{ $skin['card'] }} db-kpi">
                    <div class="{{ $skin['help'] }} text-xs uppercase tracking-wide">{{ $k['label'] }}</div>
                    <div class="tl-display db-kpi-value mt-1 text-2xl tabular-nums">{{ $k['value'] }}</div>
                    <div class="{{ $skin['help'] }} mt-0.5 text-xs">{{ $k['sub'] }}</div>
                </div>
            @endforeach
        </div>

        {{-- Da xl i tre blocchi si affiancano: storico a sinistra (2 colonne), grafico e panoramica a destra --}}
        <div class="lg:grid lg:grid-cols-2 lg:items-start lg:gap-4 xl:grid-cols-3">

        {{-- Per-day series: completed tasks (bars) + cost/time on hover --}}
        @php($max = max(1, max(array_column($series, 'count'))))
        <div class="{{ $skin['card'] }} mb-4 lg:order-1 lg:mb-0 xl:order-2">
            <h2 class="{{ $skin['h2'] }} mb-2 text-base">{{ __('griglia::t.stats_page.series_title', ['days' => count($series)]) }}</h2>
            <div class="db-series flex h-28 items-end gap-px" role="img" aria-label="{{ __('griglia::t.stats_page.series_title', ['days' => count($series)]) }}">
                @foreach ($series as $date => $p)
                    <div class="db-series-bar flex-1" style="height: {{ $p['count'] ? max(6, round($p['count'] / $max * 100)) : 2 }}%" title="{{ \Carbon\Carbon::parse($date)->format('d/m') }}: {{ $p['count'] }} · {{ \Alle80\Griglia\Support\Stats::duration($p['work_seconds'] ?: null) }} · {{ $p['cost'] ? \Alle80\Griglia\Support\Stats::money($p['cost']) : '—' }}"></div>
                @endforeach
            </div>
            <div class="{{ $skin['help'] }} mt-1 flex justify-between text-[10px]"><span>{{ \Carbon\Carbon::parse(array_key_first($series))->format('d/m') }}</span><span>{{ \Carbon\Carbon::parse(array_key_last($series))->format('d/m') }}</span></div>
        </div>

        {{-- History --}}
        <div class="{{ $skin['card'] }} mb-4 lg:order-3 lg:col-span-2 lg:mb-0 xl:order-1 xl:row-span-2">
            <h2 class="{{ $skin['h2'] }} mb-2 text-base">{{ __('griglia::t.stats_page.history_title', ['n' => $rows->count()]) }}</h2>
            @if ($rows->isEmpty())
                <p class="{{ $skin['help'] }} py-3 text-center text-sm">{{ __('griglia::t.stats_page.empty') }}</p>
            @else
                {{-- Mobile: one card per task --}}
                <ul class="{{ $skin['divide'] }} sm:hidden">
                    @foreach ($rows as $r)
                        @php($t = $r['todo'])
                        <li wire:key="hm-{{ $t->id }}" class="py-2">
                            <div class="flex items-start justify-between gap-2">
                                <span class="{{ $skin['label'] }} min-w-0 break-words">{{ $t->title }}@if ($selectedCount > 1) <span class="{{ $skin['help'] }} font-normal">· {{ $t->checklist?->name }}</span>@endif</span>
                                <span class="{{ $skin['help'] }} shrink-0 text-xs tabular-nums">{{ $t->completed_at->format('d/m H:i') }}</span>
                            </div>
                            <div class="{{ $skin['help'] }} mt-0.5 grid grid-cols-3 gap-1 text-xs tabular-nums">
                                <span>{{ __('griglia::t.stats_page.col_time') }}: {{ \Alle80\Griglia\Support\Stats::duration($r['work_seconds']) }}</span>
                                <span>{{ __('griglia::t.stats_page.col_tokens_short') }}: {{ $r['tokens_in'] + $r['tokens_out'] ? \Alle80\Griglia\Models\Todo::formatTokens($r['tokens_in'] + $r['tokens_out']) : '—' }}</span>
                                <span class="text-right">{{ \Alle80\Griglia\Support\Stats::money($r['cost']) }}</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
                <div class="db-panel-scroll hidden overflow-x-auto sm:block">
                    <table class="db-history w-full text-sm">
                        <thead class="db-sticky-head">
                            <tr class="{{ $skin['help'] }} text-left text-xs uppercase tracking-wide">
                                <th class="py-1 pr-2">{{ __('griglia::t.stats_page.col_date') }}</th>
                                <th class="py-1 pr-2">{{ __('griglia::t.stats_page.col_task') }}</th>
                                <th class="py-1 pr-2 text-right">{{ __('griglia::t.stats_page.col_time') }}</th>
                                <th class="py-1 pr-2 text-right">{{ __('griglia::t.stats_page.col_tokens') }}</th>
                                <th class="py-1 text-right">{{ __('griglia::t.stats_page.col_cost') }}</th>
                            </tr>
                        </thead>
                        <tbody class="{{ $skin['divide'] }}">
                            @foreach ($rows as $r)
                                @php($t = $r['todo'])
                                <tr wire:key="h-{{ $t->id }}" class="align-top">
                                    <td class="py-1.5 pr-2 whitespace-nowrap tabular-nums {{ $skin['help'] }}">{{ $t->completed_at->format('d/m H:i') }}</td>
                                    <td class="py-1.5 pr-2">
                                        <span class="{{ $skin['label'] }}">{{ $t->title }}</span>@if ($selectedCount > 1) <span class="{{ $skin['help'] }} text-xs">· {{ $t->checklist?->name }}</span>@endif
                                        <span class="{{ $skin['help'] }} block text-xs">
                                            @if ($t->archived_at)<span class="mr-1">{{ __('griglia::t.stats_page.archived') }}</span>@endif
                                            @if ($t->ingredients_count ?? $t->ingredients->count()){{ $t->ingredients_done_count }}/{{ $t->ingredients->count() }} {{ __('griglia::t.stats_page.subtasks') }}@endif
                                            @if ($t->questions_count) · {{ $t->questions_count }} {{ __('griglia::t.stats_page.questions') }}@endif
                                            @if ($r['lead_seconds'] !== null) · {{ __('griglia::t.stats_page.lead') }} {{ \Alle80\Griglia\Support\Stats::duration($r['lead_seconds']) }}@endif
                                            @if ($t->parent) · {{ __('griglia::t.stats_page.resumes', ['title' => $t->parent->title]) }}@endif
                                        </span>
                                    </td>
                                    <td class="py-1.5 pr-2 text-right whitespace-nowrap tabular-nums">{{ \Alle80\Griglia\Support\Stats::duration($r['work_seconds']) }}</td>
                                    <td class="py-1.5 pr-2 text-right whitespace-nowrap tabular-nums">{{ $r['tokens_in'] + $r['tokens_out'] ? \Alle80\Griglia\Models\Todo::formatTokens($r['tokens_in']).' / '.\Alle80\Griglia\Models\Todo::formatTokens($r['tokens_out']) : '—' }}</td>
                                    <td class="py-1.5 text-right whitespace-nowrap tabular-nums">{{ \Alle80\Griglia\Support\Stats::money($r['cost']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Overview of every list --}}
        <div class="{{ $skin['card'] }} lg:order-2 xl:order-3">
            <h2 class="{{ $skin['h2'] }} mb-2 text-base">{{ __('griglia::t.stats_page.overview_title') }}</h2>
            <div class="db-panel-scroll db-panel-overview overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr class="{{ $skin['help'] }} text-left text-xs uppercase tracking-wide"><th class="py-1 pr-2">{{ __('griglia::t.stats_page.list') }}</th><th class="py-1 pr-2 text-right">{{ __('griglia::t.stats_page.kpi_done') }}</th><th class="py-1 pr-2 text-right">{{ __('griglia::t.stats_page.kpi_time') }}</th><th class="py-1 text-right">{{ __('griglia::t.stats_page.kpi_cost') }}</th></tr></thead>
                    <tbody class="{{ $skin['divide'] }}">
                        @foreach ($overview as $o)
                            <tr wire:key="ov-{{ $o['list']->id }}" class="{{ $list && $o['list']->id === $list->id ? 'font-bold' : '' }}">
                                <td class="py-1.5 pr-2"><button type="button" wire:click="setList({{ $o['list']->id }})" class="cursor-pointer text-left hover:underline">{{ $o['list']->name }}@if ($o['list']->trashed()) <span class="opacity-60">{{ __('griglia::t.stats_page.deleted_list') }}</span>@endif</button></td>
                                <td class="py-1.5 pr-2 text-right tabular-nums">{{ $o['agg']['count'] }}</td>
                                <td class="py-1.5 pr-2 text-right tabular-nums">{{ \Alle80\Griglia\Support\Stats::duration($o['agg']['timed_count'] ? $o['agg']['work_seconds'] : null) }}</td>
                                <td class="py-1.5 text-right tabular-nums">{{ \Alle80\Griglia\Support\Stats::money($o['agg']['cost']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        </div>{{-- /griglia desktop --}}
    @endif
</div>
