<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/** Price list for the statistics (cost = tokens × price per million). */
return new class extends SettingsMigration
{
    public function up(): void
    {
        foreach (['app.cost_per_m_in' => '0', 'app.cost_per_m_out' => '0', 'app.cost_currency' => 'EUR'] as $key => $default) {
            if (! $this->migrator->exists($key)) {
                $this->migrator->add($key, $default);
            }
        }
    }

    public function down(): void
    {
        foreach (['app.cost_per_m_in', 'app.cost_per_m_out', 'app.cost_currency'] as $key) {
            if ($this->migrator->exists($key)) {
                $this->migrator->delete($key);
            }
        }
    }
};
