<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/** Agent context: generate the instruction files from the board (on) or keep the original files (off). */
return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->migrator->exists('app.context_sync')) {
            $this->migrator->add('app.context_sync', true);
        }
    }

    public function down(): void
    {
        if ($this->migrator->exists('app.context_sync')) {
            $this->migrator->delete('app.context_sync');
        }
    }
};
