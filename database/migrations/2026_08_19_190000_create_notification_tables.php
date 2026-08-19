<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tables for the app-side notifications: Laravel's `notifications` (database channel, in-app bell) and
 * `push_subscriptions` (Web Push, laravel-notification-channels/webpush). Both idempotent: skipped when the
 * host app already has them.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }

        $push = (string) config('webpush.table_name', 'push_subscriptions');
        if (! Schema::hasTable($push)) {
            Schema::create($push, function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->morphs('subscribable', 'push_subscriptions_subscribable_morph_idx');
                $table->string('endpoint', 500)->charset('ascii')->unique();
                $table->string('public_key')->nullable();
                $table->string('auth_token')->nullable();
                $table->string('content_encoding')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Shared tables: left in place on purpose.
    }
};
