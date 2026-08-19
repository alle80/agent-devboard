<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/** Channels of the board's own notifications (bell / web push / mail). */
return new class extends SettingsMigration
{
    public function up(): void
    {
        foreach (['app.notify_in_app' => true, 'app.notify_webpush' => true, 'app.notify_mail' => false] as $key => $default) {
            if (! $this->migrator->exists($key)) {
                $this->migrator->add($key, $default);
            }
        }
    }

    public function down(): void
    {
        foreach (['app.notify_in_app', 'app.notify_webpush', 'app.notify_mail'] as $key) {
            if ($this->migrator->exists($key)) {
                $this->migrator->delete($key);
            }
        }
    }
};
