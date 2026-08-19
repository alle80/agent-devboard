<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/** Adds `agent.task_mode` (ordered|multitasking) for pre-existing installs. */
return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->migrator->exists('agent.task_mode')) {
            $this->migrator->add('agent.task_mode', 'ordered');
        }
    }

    public function down(): void
    {
        if ($this->migrator->exists('agent.task_mode')) {
            $this->migrator->delete('agent.task_mode');
        }
    }
};
