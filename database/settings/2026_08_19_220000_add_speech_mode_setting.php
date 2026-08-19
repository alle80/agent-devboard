<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/** Speech to text mode (auto|server|browser). */
return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->migrator->exists('app.speech_mode')) {
            $this->migrator->add('app.speech_mode', 'auto');
        }
    }

    public function down(): void
    {
        if ($this->migrator->exists('app.speech_mode')) {
            $this->migrator->delete('app.speech_mode');
        }
    }
};
