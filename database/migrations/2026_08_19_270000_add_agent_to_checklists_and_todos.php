<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Multi-agent: default agent of a list (`checklists.agent`) and per-task override (`todos.agent`). */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('checklists') && ! Schema::hasColumn('checklists', 'agent')) {
            Schema::table('checklists', fn (Blueprint $table) => $table->string('agent', 40)->nullable()->after('plan_paused'));
        }
        if (Schema::hasTable('todos') && ! Schema::hasColumn('todos', 'agent')) {
            Schema::table('todos', fn (Blueprint $table) => $table->string('agent', 40)->nullable()->after('skills'));
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('todos', 'agent')) {
            Schema::table('todos', fn (Blueprint $table) => $table->dropColumn('agent'));
        }
        if (Schema::hasColumn('checklists', 'agent')) {
            Schema::table('checklists', fn (Blueprint $table) => $table->dropColumn('agent'));
        }
    }
};
