<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->string('reviewer_agent', 40)->nullable()->after('agent');
            $table->foreignId('review_of_id')->nullable()->after('depends_on_id')->constrained('todos')->restrictOnDelete();
            $table->unsignedInteger('review_round')->nullable()->after('review_of_id');
            $table->string('review_status', 24)->nullable()->after('review_round');
            $table->string('review_outcome', 24)->nullable()->after('review_status');
            $table->unique(['review_of_id', 'review_round'], 'todos_review_round_unique');
            $table->index(['review_of_id', 'review_outcome'], 'todos_pending_review_index');
        });
    }

    public function down(): void
    {
        Schema::table('todos', function (Blueprint $table) {
            $table->dropForeign(['review_of_id']);
            $table->dropUnique('todos_review_round_unique');
            $table->dropIndex('todos_pending_review_index');
            $table->dropColumn(['reviewer_agent', 'review_of_id', 'review_round', 'review_status', 'review_outcome']);
        });
    }
};
