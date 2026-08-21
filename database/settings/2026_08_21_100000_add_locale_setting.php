<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/** Lingua dell'interfaccia della board ('' = come il config dell'applicazione, APP_LOCALE). */
return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->migrator->exists('app.locale')) {
            $this->migrator->add('app.locale', '');
        }
    }

    public function down(): void
    {
        if ($this->migrator->exists('app.locale')) {
            $this->migrator->delete('app.locale');
        }
    }
};
