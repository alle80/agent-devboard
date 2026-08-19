<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/** «Optimization» group (token saving switches for the agent). */
return new class extends SettingsMigration
{
    public function up(): void
    {
        foreach ([
            'optimization.compact_check' => true,
            'optimization.terse_agent' => false,
            'optimization.context_max_chars' => 0,
            'optimization.progress_piggyback' => true,
            'optimization.token_report' => true,
        ] as $key => $default) {
            if (! $this->migrator->exists($key)) {
                $this->migrator->add($key, $default);
            }
        }
    }

    public function down(): void
    {
        foreach (['compact_check', 'terse_agent', 'context_max_chars', 'progress_piggyback', 'token_report'] as $k) {
            if ($this->migrator->exists("optimization.$k")) {
                $this->migrator->delete("optimization.$k");
            }
        }
    }
};
