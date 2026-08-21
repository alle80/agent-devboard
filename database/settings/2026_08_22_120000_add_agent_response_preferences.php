<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('agent.response_tone', 'clear');
        $this->migrator->add('agent.response_length', 'balanced');
    }

    public function down(): void
    {
        $this->migrator->delete('agent.response_tone');
        $this->migrator->delete('agent.response_length');
    }
};
