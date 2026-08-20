<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('todos') && ! Schema::hasColumn('todos', 'result_summary')) {
            Schema::table('todos', fn (Blueprint $table) => $table->string('result_summary', 120)->nullable()->after('claude_comment'));
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('todos', 'result_summary')) {
            Schema::table('todos', fn (Blueprint $table) => $table->dropColumn('result_summary'));
        }
    }
};
