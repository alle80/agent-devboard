<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds `todos.result_seen` for installs created before it was part of the consolidated migration.
 * false = the agent completed the item and the user hasn't opened the result yet (→ highlighted).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('todos') && ! Schema::hasColumn('todos', 'result_seen')) {
            Schema::table('todos', function (Blueprint $table) {
                $table->boolean('result_seen')->default(true)->after('claude_comment');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('todos', 'result_seen')) {
            Schema::table('todos', fn (Blueprint $table) => $table->dropColumn('result_seen'));
        }
    }
};
