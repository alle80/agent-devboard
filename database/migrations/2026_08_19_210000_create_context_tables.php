<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Agent context (the instructions file, e.g. CLAUDE.md) split into groups and blocks, each switchable. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('context_groups')) {
            Schema::create('context_groups', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->unsignedInteger('order')->default(0);
                $table->boolean('enabled')->default(true);
                $table->timestamps();
            });
        }
        if (! Schema::hasTable('context_blocks')) {
            Schema::create('context_blocks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('group_id')->constrained('context_groups')->cascadeOnDelete();
                $table->string('title')->nullable();
                $table->text('body');
                $table->unsignedInteger('order')->default(0);
                $table->boolean('enabled')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('context_blocks');
        Schema::dropIfExists('context_groups');
    }
};
