<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Adds the per-todo statistics columns (working time + tokens) for pre-existing installs. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('todos')) {
            return;
        }
        Schema::table('todos', function (Blueprint $table) {
            if (! Schema::hasColumn('todos', 'working_since')) {
                $table->timestamp('working_since')->nullable()->after('progress');
            }
            if (! Schema::hasColumn('todos', 'work_seconds')) {
                $table->unsignedInteger('work_seconds')->default(0)->after('working_since');
            }
            if (! Schema::hasColumn('todos', 'tokens_in')) {
                $table->unsignedBigInteger('tokens_in')->default(0)->after('work_seconds');
            }
            if (! Schema::hasColumn('todos', 'tokens_out')) {
                $table->unsignedBigInteger('tokens_out')->default(0)->after('tokens_in');
            }
        });
    }

    public function down(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            foreach (['working_since', 'work_seconds', 'tokens_in', 'tokens_out'] as $col) {
                if (Schema::hasColumn('todos', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
