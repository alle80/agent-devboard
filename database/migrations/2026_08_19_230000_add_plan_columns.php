<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Plan mode: the plan prompt on the list, and the chain between tasks (`depends_on_id`). */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('checklists') && ! Schema::hasColumn('checklists', 'plan_prompt')) {
            Schema::table('checklists', fn (Blueprint $table) => $table->text('plan_prompt')->nullable()->after('name'));
        }
        if (Schema::hasTable('todos') && ! Schema::hasColumn('todos', 'depends_on_id')) {
            Schema::table('todos', fn (Blueprint $table) => $table->foreignId('depends_on_id')->nullable()->after('parent_id')->constrained('todos')->nullOnDelete());
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('todos', 'depends_on_id')) {
            Schema::table('todos', function (Blueprint $table) {
                $table->dropConstrainedForeignId('depends_on_id');
            });
        }
        if (Schema::hasColumn('checklists', 'plan_prompt')) {
            Schema::table('checklists', fn (Blueprint $table) => $table->dropColumn('plan_prompt'));
        }
    }
};
