<?php

namespace Alle80\Devboard\Support;

use Carbon\CarbonImmutable;

/**
 * Status of the coding agents (plan + usage windows): reads the snapshot written by `devboard:agent-status-import`
 * (produced on the host by scripts/agent-status.py, which keeps the credentials there and sends only percentages)
 * and computes the derived values for the UI: used %, remaining %, bar width, level, reset countdown, staleness.
 */
class AgentStatus
{
    public const STALE_MINUTES = 15;

    public static function path(): string
    {
        return (string) (config('devboard.agent_status_file') ?: storage_path('app/devboard/agent-status.json'));
    }

    /** Raw snapshot (['updated_at' => ISO, 'agents' => [...]]) or null when never imported. */
    public static function snapshot(): ?array
    {
        $path = self::path();
        if (! is_file($path)) {
            return null;
        }
        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) && isset($data['agents']) ? $data : null;
    }

    /** Store a snapshot (validated/normalized). Returns the number of agents. */
    public static function import(array $data): int
    {
        $agents = [];
        foreach ((array) ($data['agents'] ?? []) as $a) {
            if (! is_array($a) || trim((string) ($a['key'] ?? '')) === '') {
                continue;
            }
            $windows = [];
            foreach ((array) ($a['windows'] ?? []) as $w) {
                if (! is_array($w) || trim((string) ($w['key'] ?? '')) === '') {
                    continue;
                }
                $windows[] = [
                    'key' => (string) $w['key'],
                    'label' => (string) ($w['label'] ?? $w['key']),
                    'utilization' => isset($w['utilization']) && is_numeric($w['utilization']) ? (float) $w['utilization'] : null,
                    'resets_at' => ! empty($w['resets_at']) ? (string) $w['resets_at'] : null,
                    'limit_dollars' => isset($w['limit_dollars']) && is_numeric($w['limit_dollars']) ? (float) $w['limit_dollars'] : null,
                    'used_dollars' => isset($w['used_dollars']) && is_numeric($w['used_dollars']) ? (float) $w['used_dollars'] : null,
                ];
            }
            $agents[] = [
                'key' => (string) $a['key'],
                'name' => (string) ($a['name'] ?? $a['key']),
                'plan' => ! empty($a['plan']) ? (string) $a['plan'] : null,
                'plan_kind' => ! empty($a['plan_kind']) ? (string) $a['plan_kind'] : null,
                'windows' => $windows,
                'extra_usage' => is_array($a['extra_usage'] ?? null) ? $a['extra_usage'] : null,
                'error' => ! empty($a['error']) ? (string) $a['error'] : null,
            ];
        }
        $snapshot = ['updated_at' => ! empty($data['updated_at']) ? (string) $data['updated_at'] : now()->toIso8601String(), 'agents' => $agents];
        $path = self::path();
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }
        file_put_contents($path, json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return count($agents);
    }

    /**
     * Derived values for one window: used %, remaining %, bar %, level (ok|warn|critical|over|na), reset countdown.
     * Division by zero is impossible (utilization is already a %); null utilization → level 'na'.
     */
    public static function computeWindow(array $w, ?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();
        $u = $w['utilization'] ?? null;
        $used = $u === null ? null : max(0.0, round((float) $u, 1));
        $remaining = $used === null ? null : max(0.0, round(100 - $used, 1));
        $bar = $used === null ? 0 : (int) min(100, round($used));
        $level = match (true) {
            $used === null => 'na',
            $used > 100 => 'over',
            $used >= 90 => 'critical',
            $used >= 70 => 'warn',
            default => 'ok',
        };
        $resets = null;
        $resetsIn = null;
        if (! empty($w['resets_at'])) {
            try {
                $resets = CarbonImmutable::parse($w['resets_at']);
                $resetsIn = max(0, (int) $now->diffInSeconds($resets, false));
            } catch (\Throwable) {
                $resets = null;
            }
        }

        return $w + ['used' => $used, 'remaining' => $remaining, 'bar' => $bar, 'level' => $level, 'resets' => $resets, 'resets_in' => $resetsIn];
    }

    /** Agents with computed windows + snapshot meta (updated_at, stale). Empty list when no snapshot. */
    public static function agents(?CarbonImmutable $now = null): array
    {
        $now ??= CarbonImmutable::now();
        $snap = self::snapshot();
        if (! $snap) {
            return ['updated_at' => null, 'stale' => true, 'agents' => []];
        }
        $updated = null;
        try {
            $updated = CarbonImmutable::parse($snap['updated_at']);
        } catch (\Throwable) {
        }
        $agents = array_map(function (array $a) use ($now) {
            $a['windows'] = array_map(fn ($w) => self::computeWindow($w, $now), $a['windows']);
            // overall level = worst window
            $rank = ['na' => 0, 'ok' => 1, 'warn' => 2, 'critical' => 3, 'over' => 4];
            $a['level'] = collect($a['windows'])->map(fn ($w) => $w['level'])->sortByDesc(fn ($l) => $rank[$l])->first() ?? 'na';
            if ($a['error']) {
                $a['level'] = 'error';
            }

            return $a;
        }, $snap['agents']);

        return ['updated_at' => $updated, 'stale' => ! $updated || $updated->diffInMinutes($now) > self::STALE_MINUTES, 'agents' => $agents];
    }

    /** "2h 10m" style countdown. */
    public static function countdown(?int $seconds): string
    {
        if ($seconds === null) {
            return '—';
        }
        if ($seconds <= 0) {
            return '0m';
        }
        $d = intdiv($seconds, 86400);
        $h = intdiv($seconds % 86400, 3600);
        $m = intdiv($seconds % 3600, 60);
        if ($d > 0) return sprintf('%dg %dh', $d, $h);
        if ($h > 0) return sprintf('%dh %02dm', $h, $m);

        return sprintf('%dm', max(1, $m));
    }
}
