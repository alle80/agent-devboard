<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Adds `todos.skills` (agent skills chosen for the task) for pre-existing installs. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('todos') && ! Schema::hasColumn('todos', 'skills')) {
            Schema::table('todos', fn (Blueprint $table) => $table->json('skills')->nullable()->after('tokens_out'));
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('todos', 'skills')) {
            Schema::table('todos', fn (Blueprint $table) => $table->dropColumn('skills'));
        }
    }
};
