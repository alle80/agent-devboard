<?php

namespace Alle80\Griglia\Console;

use Alle80\Griglia\Support\AgentStatus;
use Illuminate\Console\Command;

/** `griglia:agent-status-import [--file=]` — stores the agents' plan/usage snapshot (JSON from file or stdin). */
class AgentStatusImport extends Command
{
    protected $signature = 'griglia:agent-status-import {--file= : JSON file (default: stdin)}';

    protected $description = 'Imports the agents status snapshot (plan + usage windows) shown in /agents';

    public function handle(): int
    {
        $file = $this->option('file');
        $json = $file ? @file_get_contents($file) : stream_get_contents(STDIN);
        if ($json === false || trim((string) $json) === '') {
            $this->error('No JSON received');

            return self::FAILURE;
        }
        $data = json_decode($json, true);
        if (! is_array($data) || ! isset($data['agents'])) {
            $this->error('Invalid JSON: expected {updated_at, agents: [...]}');

            return self::FAILURE;
        }
        $n = AgentStatus::import($data);
        $this->info("$n agents imported → ".AgentStatus::path());

        return self::SUCCESS;
    }
}
