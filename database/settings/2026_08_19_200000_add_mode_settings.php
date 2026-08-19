<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/** Board mode override + dashboard tab switch. */
return new class extends SettingsMigration
{
    public function up(): void
    {
        foreach (['app.mode' => '', 'app.show_dashboard_tab' => true] as $key => $default) {
            if (! $this->migrator->exists($key)) {
                $this->migrator->add($key, $default);
            }
        }
    }

    public function down(): void
    {
        foreach (['app.mode', 'app.show_dashboard_tab'] as $key) {
            if ($this->migrator->exists($key)) {
                $this->migrator->delete($key);
            }
        }
    }
};
