<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Adds `todos.completed_at` (history/statistics) and backfills it from `updated_at` for already completed items. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('todos')) {
            return;
        }
        if (! Schema::hasColumn('todos', 'completed_at')) {
            Schema::table('todos', fn (Blueprint $table) => $table->timestamp('completed_at')->nullable()->index()->after('completed'));
        }
        DB::table('todos')->where('completed', true)->whereNull('completed_at')->update(['completed_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('todos', 'completed_at')) {
            Schema::table('todos', fn (Blueprint $table) => $table->dropColumn('completed_at'));
        }
    }
};
