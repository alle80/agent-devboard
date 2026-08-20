<?php

namespace Alle80\Griglia\Tests\Feature;

use Alle80\Griglia\Settings\AgentSettings;
use Alle80\Griglia\Settings\AppSettings;
use Alle80\Griglia\Tests\TestCase;
use Illuminate\Support\Facades\Schema;

class MigrationTest extends TestCase
{
    public function test_tables_and_settings_are_created(): void
    {
        foreach (['checklists', 'todos', 'ingredients', 'attachments', 'questions', 'settings'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "table {$table}");
        }
        $this->assertTrue(Schema::hasColumns('todos', ['open_to_work', 'working', 'stopped_at', 'question', 'claude_comment', 'result_summary', 'archived_at', 'parent_id']));

        $this->assertTrue(app(AgentSettings::class)->commit_after_task);
        $this->assertSame('ask', app(AgentSettings::class)->autonomy);
        $this->assertSame(50, app(AppSettings::class)->title_max_length);
    }

    public function test_migrations_are_idempotent(): void
    {
        // Running the guarded migration again on an existing database must be a no-op, not an error
        $migration = require __DIR__.'/../../database/migrations/2026_08_15_000000_create_todolist_tables.php';
        $migration->up();
        $migration->up();
        $this->assertTrue(Schema::hasTable('todos'));

        $this->artisan('migrate')->assertSuccessful(); // nothing left to migrate
        $this->assertTrue(app(AgentSettings::class)->commit_after_task);
    }
}
