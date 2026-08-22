<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Task 499: blocks the board writes by itself (e.g. the question level chosen in /settings) carry a key,
 * so the board finds them again and rewrites them in place when their source changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('context_blocks') && ! Schema::hasColumn('context_blocks', 'key')) {
            Schema::table('context_blocks', function (Blueprint $table) {
                $table->string('key', 64)->nullable()->unique()->after('group_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('context_blocks', 'key')) {
            Schema::table('context_blocks', function (Blueprint $table) {
                $table->dropColumn('key');
            });
        }
    }
};
