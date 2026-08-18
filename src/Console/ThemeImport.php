<?php

namespace Alle80\Devboard\Console;

use Alle80\Devboard\ThemeStore;
use Illuminate\Console\Command;

class ThemeImport extends Command
{
    protected $signature = 'devboard:theme-import {zip : Path of the theme pack (zip)} {--uninstall= : Instead of importing, uninstall the theme with this slug}';

    protected $description = 'Installs (or uninstalls) a theme pack in storage/app/themes';

    public function handle(): int
    {
        if ($slug = $this->option('uninstall')) {
            $this->line(ThemeStore::uninstall($slug) ? "Uninstalled: {$slug}" : "Not installed: {$slug}");

            return self::SUCCESS;
        }

        $zip = (string) $this->argument('zip');
        if (! is_file($zip)) {
            $this->error("File not found: {$zip}");

            return self::FAILURE;
        }

        try {
            $def = ThemeStore::install($zip);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf('Installed theme «%s» (%s) → %s', $def['label'], $def['slug'], \Alle80\Devboard\Themes::url($def['slug'])));

        return self::SUCCESS;
    }
}
