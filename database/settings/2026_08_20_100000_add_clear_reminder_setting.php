<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/** Reminder to clear the agent session when the context gets heavy (task 372). */
return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->migrator->exists('optimization.clear_reminder_k')) {
            $this->migrator->add('optimization.clear_reminder_k', 400);
        }
    }

    public function down(): void
    {
        foreach (['clear_reminder_k'] as $k) {
            if ($this->migrator->exists("optimization.$k")) {
                $this->migrator->delete("optimization.$k");
            }
        }
    }
};
