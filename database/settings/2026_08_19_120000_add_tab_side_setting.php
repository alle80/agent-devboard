<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Adds the `app.tab_side` setting (desktop dashboard tab side) for installs that already ran the
 * initial settings migration. Guarded, so it is a no-op where the value is already present.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->migrator->exists('app.tab_side')) {
            $this->migrator->add('app.tab_side', 'right');
        }
    }
};
