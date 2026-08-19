<?php

namespace Alle80\Devboard\Console;

use Alle80\Devboard\Models\Checklist;
use Illuminate\Console\Command;

/**
 * Portable monitor for a coding agent: watches the agent list and prints ONLY the
 * changes an agent should react to — an item becoming "open to work", the answers to
 * a paused question arriving, or a stop being requested on something in progress.
 *
 * One command replaces the harness-specific monitors: `php artisan devboard:watch`.
 * Pair it with `devboard:check` (the agent runs that to read/take/close items).
 */
class Watch extends Command
{
    protected $signature = 'devboard:watch
        {--interval=10 : Seconds between polls}
        {--list= : List name to watch (default: config devboard.agent_list)}
        {--once : Poll once and exit (for testing/cron)}';

    protected $description = 'Watch the agent list and print only changes (open-to-work, answers, stops)';

    public function handle(): int
    {
        $name = (string) ($this->option('list') ?: config('devboard.agent_list', 'dev'));
        $interval = max(2, (int) $this->option('interval'));

        if (! Checklist::where('name', $name)->exists()) {
            $this->warn(sprintf('No list named "%s" (config devboard.agent_list).', $name));

            return self::FAILURE;
        }

        if (! $this->option('once')) {
            $this->info(sprintf('👀 watching list "%s" every %ds — Ctrl-C to stop', $name, $interval));
        }

        $prev = null;
        do {
            $snap = $this->snapshot($name);
            if ($prev !== null) {
                foreach (self::changes($prev, $snap, now()->format('H:i:s')) as $line) {
                    $this->line($line);
                }
            }
            $prev = $snap;

            if ($this->option('once')) {
                break;
            }
            sleep($interval);
        } while (true);

        return self::SUCCESS;
    }

    /** @return array<int,array<string,mixed>> keyed by todo id */
    private function snapshot(string $name): array
    {
        $list = Checklist::where('name', $name)->first();
        if (! $list) {
            return [];
        }

        $out = [];
        foreach ($list->todos()->whereNull('archived_at')->where('completed', false)->with('questions')->get() as $t) {
            $out[$t->id] = [
                'title' => $t->title,
                'otw' => (bool) $t->open_to_work,
                'working' => (bool) $t->working,
                'question' => (bool) $t->question,
                'stopped' => optional($t->stopped_at)->getTimestamp(),
                'answered' => $t->questions->whereNotNull('answer')->count(),
            ];
        }

        return $out;
    }

    /**
     * Pure diff of two snapshots → the lines to print. Static & public so it can be unit-tested.
     *
     * @param  array<int,array<string,mixed>>  $prev
     * @param  array<int,array<string,mixed>>  $now
     * @return list<string>
     */
    public static function changes(array $prev, array $now, string $stamp): array
    {
        $lines = [];

        foreach ($now as $id => $c) {
            $p = $prev[$id] ?? null;

            // Newly "open to work"
            if ($c['otw'] && ! ($p['otw'] ?? false)) {
                $lines[] = ($p && $p['question'] && ! $c['question'])
                    ? sprintf('[%s] 💬 ANSWERS RECEIVED, back to work: «%s» (id:%d)', $stamp, $c['title'], $id)
                    : sprintf('[%s] 🟢 OPEN TO WORK: «%s» (id:%d)', $stamp, $c['title'], $id);
            }

            // Stop requested on something that was in progress
            if ($c['stopped'] && $c['stopped'] !== ($p['stopped'] ?? null)) {
                $lines[] = sprintf('[%s] ⏹ STOP REQUESTED: «%s» (id:%d) — stop working on it now', $stamp, $c['title'], $id);
            }
        }

        return $lines;
    }
}
