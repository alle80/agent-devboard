<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Task 499: `agent.autonomy` grows from two values (ask / decide) into a five-step question scale —
 * autonomous, essential, ask, many, paranoid. The old `decide` becomes `autonomous`, `ask` keeps its name.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        if (! $this->migrator->exists('agent.autonomy')) {
            $this->migrator->add('agent.autonomy', 'ask');

            return;
        }
        $this->migrator->update('agent.autonomy', fn ($v) => match ((string) $v) {
            'decide' => 'autonomous',
            'autonomous', 'essential', 'ask', 'many', 'paranoid' => (string) $v,
            default => 'ask',
        });
    }

    public function down(): void
    {
        if ($this->migrator->exists('agent.autonomy')) {
            $this->migrator->update('agent.autonomy', fn ($v) => in_array($v, ['autonomous', 'essential'], true) ? 'decide' : 'ask');
        }
    }
};
