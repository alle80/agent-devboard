<?php

namespace Alle80\Devboard\Console;

use Alle80\Devboard\Support\Skills;
use Illuminate\Console\Command;

/**
 * Imports the catalogue of the agent's skills (JSON list of {name, description, source}) from a file
 * or from stdin: `scripts/sync-skills.py | docker exec -i app php artisan devboard:skills-import`.
 */
class SkillsImport extends Command
{
    protected $signature = 'devboard:skills-import {--file= : JSON file (default: stdin)}';

    protected $description = 'Imports the list of skills the agent can use (shown in the task modal)';

    public function handle(): int
    {
        $file = $this->option('file');
        $json = $file ? @file_get_contents($file) : stream_get_contents(STDIN);
        if ($json === false || trim((string) $json) === '') {
            $this->error('No JSON received ('.($file ? "file $file" : 'stdin').')');

            return self::FAILURE;
        }
        $list = json_decode($json, true);
        if (! is_array($list)) {
            $this->error('Invalid JSON: expected a list of {name, description, source}');

            return self::FAILURE;
        }
        $n = Skills::import($list);
        $this->info(sprintf('%d skills imported → %s', $n, Skills::path()));

        return self::SUCCESS;
    }
}
