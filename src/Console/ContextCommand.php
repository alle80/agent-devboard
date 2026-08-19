<?php

namespace Alle80\Devboard\Console;

use Alle80\Devboard\Support\Context;
use Illuminate\Console\Command;

/**
 * `devboard:context import [--file=] [--replace]` — markdown (file or stdin) → groups/blocks;
 * `devboard:context export [--all]` — enabled context as markdown (what the instructions file should be);
 * `devboard:context status` — counts and token estimate.
 */
class ContextCommand extends Command
{
    protected $signature = 'devboard:context {action=status : import|export|status|enabled} {--file= : markdown file for import (default: stdin)} {--replace : import: wipe the current context first} {--all : export: include disabled groups/blocks}';

    protected $description = 'Agent context (instructions file) as switchable groups/blocks: import, export, status';

    public function handle(): int
    {
        switch ($this->argument('action')) {
            case 'import':
                $file = $this->option('file');
                $md = $file ? @file_get_contents($file) : stream_get_contents(STDIN);
                if ($md === false || trim((string) $md) === '') {
                    $this->error('No markdown received');

                    return self::FAILURE;
                }
                if (! $this->option('replace') && \Alle80\Devboard\Models\ContextGroup::exists()) {
                    $this->error('The context is not empty: use --replace to overwrite it (the switches are lost) or edit it in /context');

                    return self::FAILURE;
                }
                [$g, $b] = Context::import($md, (bool) $this->option('replace'));
                $this->info("$g groups, $b blocks imported");

                return self::SUCCESS;
            case 'enabled':
                // for the host sync: 1 = write the generated files, 0 = restore the originals
                $this->output->write(app(\Alle80\Devboard\Settings\AppSettings::class)->context_sync ? '1' : '0');

                return self::SUCCESS;
            case 'export':
                $this->output->write(Context::export((bool) $this->option('all')));

                return self::SUCCESS;
            default:
                [$on, $total] = Context::tokens();
                $groups = \Alle80\Devboard\Models\ContextGroup::withCount('blocks')->orderBy('order')->get();
                $this->info(sprintf('%d groups, %d blocks — ≈%d tokens enabled of %d', $groups->count(), $groups->sum('blocks_count'), $on, $total));
                foreach ($groups as $g) {
                    $this->line(sprintf('  %s %s (%d blocks, %d enabled)', $g->enabled ? '🟢' : '⚫', $g->title, $g->blocks_count, $g->blocks()->where('enabled', true)->count()));
                }

                return self::SUCCESS;
        }
    }
}
