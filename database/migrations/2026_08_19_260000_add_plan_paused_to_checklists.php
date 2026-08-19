<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Adds `checklists.plan_paused` (a paused plan does not open the next task when one is completed). */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('checklists') && ! Schema::hasColumn('checklists', 'plan_paused')) {
            Schema::table('checklists', fn (Blueprint $table) => $table->boolean('plan_paused')->default(false)->after('plan_prompt'));
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('checklists', 'plan_paused')) {
            Schema::table('checklists', fn (Blueprint $table) => $table->dropColumn('plan_paused'));
        }
    }
};
