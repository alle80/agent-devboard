<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/** Default values of the package settings (guarded: only added when missing). */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $defaults = [
            // Agent
            'agent.commit_after_task' => true,
            'agent.push_after_commit' => true,
            'agent.autonomy' => 'ask',
            'agent.notify_on_done' => true,
            'agent.notify_on_question' => true,
            'agent.verify_before_close' => false,
            'agent.comment_detail' => 'detailed',
            'agent.git_flow' => 'main',
            'agent.daily_summary' => false,
            'agent.daily_summary_time' => '21:00',
            'agent.check_subtasks_on_done' => true,
            // App
            'app.default_style' => '',
            'app.title_max_length' => 50,
            'app.auto_archive_days' => 0,
            'app.ai_describe_images' => true,
            'app.ai_image_provider' => '',
            'app.ai_image_model' => '',
            'app.toast_console_changes' => true,
        ];

        foreach ($defaults as $key => $value) {
            if (! $this->migrator->exists($key)) {
                $this->migrator->add($key, $value);
            }
        }
    }
};
