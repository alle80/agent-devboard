<?php

namespace Alle80\Griglia\Support;

use Alle80\Griglia\Models\Checklist;
use Alle80\Griglia\Models\Todo;
use Alle80\Griglia\Settings\AppSettings;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Statistics & history: completed tasks per list (project) with times, tokens and costs, plus aggregates
 * and per-day series. Costs come from the price list in AppSettings (cost_per_m_in/out); null = unknown.
 */
class Stats
{
    /** Cost of the given tokens, or null when the price list is empty or there are no tokens. */
    public static function cost(int $tokensIn, int $tokensOut): ?float
    {
        [$pin, $pout] = self::prices();
        if (($pin <= 0 && $pout <= 0) || ($tokensIn <= 0 && $tokensOut <= 0)) {
            return null;
        }

        return round($tokensIn / 1_000_000 * $pin + $tokensOut / 1_000_000 * $pout, 4);
    }

    /** [price per M input, price per M output] as floats. */
    public static function prices(): array
    {
        try {
            $s = app(AppSettings::class);

            return [(float) str_replace(',', '.', (string) $s->cost_per_m_in), (float) str_replace(',', '.', (string) $s->cost_per_m_out)];
        } catch (\Throwable) {
            return [0.0, 0.0];
        }
    }

    public static function currency(): string
    {
        try {
            return (string) (app(AppSettings::class)->cost_currency ?: 'EUR');
        } catch (\Throwable) {
            return 'EUR';
        }
    }

    /**
     * History of completed tasks of a list (archived included), newest first.
     * Each row: todo (model) + work_seconds|null, lead_seconds|null, tokens_in, tokens_out, cost|null.
     */
    public static function history(Checklist $list, ?CarbonImmutable $from = null, ?CarbonImmutable $to = null): Collection
    {
        $q = $list->todos()->withTrashed()->where('completed', true)->whereNotNull('completed_at')
            ->with(['ingredients', 'parent:id,title', 'checklist:id,name'])->withCount(['questions', 'ingredients as ingredients_done_count' => fn ($i) => $i->where('checked', true)])
            ->orderByDesc('completed_at')->orderByDesc('id');
        if ($from) {
            $q->where('completed_at', '>=', $from);
        }
        if ($to) {
            $q->where('completed_at', '<=', $to);
        }

        return $q->get()->map(fn (Todo $t) => self::row($t));
    }

    public static function row(Todo $t): array
    {
        return [
            'todo' => $t,
            'work_seconds' => $t->work_seconds > 0 ? (int) $t->work_seconds : null,
            'lead_seconds' => $t->completed_at && $t->created_at ? max(0, (int) $t->created_at->diffInSeconds($t->completed_at)) : null,
            'tokens_in' => (int) $t->tokens_in,
            'tokens_out' => (int) $t->tokens_out,
            'cost' => self::cost((int) $t->tokens_in, (int) $t->tokens_out),
        ];
    }

    /**
     * Aggregates over a history collection: count, sums (time/tokens/cost), averages over tracked items only,
     * and how many items had time/tokens tracked.
     */
    public static function aggregate(Collection $rows): array
    {
        $timed = $rows->filter(fn ($r) => $r['work_seconds'] !== null);
        $tok = $rows->filter(fn ($r) => $r['tokens_in'] > 0 || $r['tokens_out'] > 0);
        $costed = $rows->filter(fn ($r) => $r['cost'] !== null);

        return [
            'count' => $rows->count(),
            'work_seconds' => (int) $timed->sum('work_seconds'),
            'timed_count' => $timed->count(),
            'avg_work_seconds' => $timed->count() ? (int) round($timed->sum('work_seconds') / $timed->count()) : null,
            'tokens_in' => (int) $tok->sum('tokens_in'),
            'tokens_out' => (int) $tok->sum('tokens_out'),
            'tokens_count' => $tok->count(),
            'cost' => $costed->count() ? round((float) $costed->sum('cost'), 2) : null,
            'costed_count' => $costed->count(),
            'lead_avg_seconds' => $rows->whereNotNull('lead_seconds')->count() ? (int) round($rows->whereNotNull('lead_seconds')->avg('lead_seconds')) : null,
        ];
    }

    /** Per-day series (last $days days up to today): [date => ['count','work_seconds','cost','tokens']] with zeros for empty days. */
    public static function series(Collection $rows, int $days = 30): array
    {
        $out = [];
        $today = CarbonImmutable::today();
        for ($i = $days - 1; $i >= 0; $i--) {
            $out[$today->subDays($i)->toDateString()] = ['count' => 0, 'work_seconds' => 0, 'cost' => 0.0, 'tokens' => 0];
        }
        foreach ($rows as $r) {
            $d = $r['todo']->completed_at?->toDateString();
            if ($d === null || ! isset($out[$d])) {
                continue;
            }
            $out[$d]['count']++;
            $out[$d]['work_seconds'] += (int) ($r['work_seconds'] ?? 0);
            $out[$d]['cost'] += (float) ($r['cost'] ?? 0);
            $out[$d]['tokens'] += $r['tokens_in'] + $r['tokens_out'];
        }

        return $out;
    }

    /** Overview for every list of the user: [list => aggregate]. */
    public static function overview(Collection $lists, ?CarbonImmutable $from = null): Collection
    {
        return $lists->map(fn (Checklist $l) => ['list' => $l, 'agg' => self::aggregate(self::history($l, $from))]);
    }

    /** Format helpers shared with the UI. */
    public static function money(?float $v): string
    {
        return $v === null ? '—' : number_format($v, 2, ',', '.').' '.self::currency();
    }

    public static function duration(?int $s): string
    {
        return $s === null ? '—' : Todo::formatDuration($s);
    }
}
