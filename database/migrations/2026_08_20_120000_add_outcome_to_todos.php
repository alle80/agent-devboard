<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds `todos.outcome` for installs created before it was part of the consolidated migration.
 * How the agent's result feels to the user: ok (nothing to check), alert (finished with caveats)
 * or blocked (something is in the way) → colour of the row highlight until the result is opened.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('todos') && ! Schema::hasColumn('todos', 'outcome')) {
            Schema::table('todos', function (Blueprint $table) {
                $table->string('outcome', 16)->nullable()->after('result_seen');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('todos', 'outcome')) {
            Schema::table('todos', fn (Blueprint $table) => $table->dropColumn('outcome'));
        }
    }
};
