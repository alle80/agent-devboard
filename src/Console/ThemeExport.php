<?php

namespace Alle80\Griglia\Console;

use Alle80\Griglia\ThemeStore;
use Illuminate\Console\Command;

class ThemeExport extends Command
{
    protected $signature = 'griglia:theme-export {slug : Slug of a generic theme (installed, config, registered or built-in)} {--out= : Output zip (default storage/app/theme-<slug>.zip)} {--css-from= : CSS file to extract the .theme-<slug> rules from (for themes defined in code)}';

    protected $description = 'Exports a generic theme as an installable zip pack';

    public function handle(): int
    {
        $slug = (string) $this->argument('slug');
        $out = (string) ($this->option('out') ?: storage_path("app/theme-{$slug}.zip"));

        try {
            ThemeStore::export($slug, $out, $this->option('css-from') ?: null);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Theme pack written: {$out}");

        return self::SUCCESS;
    }
}
