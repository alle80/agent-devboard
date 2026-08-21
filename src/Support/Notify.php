<?php

namespace Alle80\Griglia\Support;

use Alle80\Griglia\Models\Todo;
use Alle80\Griglia\Notifications\QuestionAsked;
use Alle80\Griglia\Notifications\TodoCompleted;
use Alle80\Griglia\Settings\AgentSettings;
use Illuminate\Support\Facades\Log;

/**
 * Sends the board's own notifications to the owner of the todo's list. Never breaks the caller:
 * a failing channel (push service down, no mailer…) is logged as a warning.
 */
class Notify
{
    public static function todoCompleted(Todo $todo): void
    {
        if (app(AgentSettings::class)->notify_on_done) {
            self::send($todo, new TodoCompleted($todo));
        }
    }

    /** @param string[] $questions */
    public static function questionAsked(Todo $todo, array $questions): void
    {
        if (app(AgentSettings::class)->notify_on_question) {
            self::send($todo, new QuestionAsked($todo, $questions));
        }
    }

    /** The user who owns the todo's list (config griglia.user_model), or null. */
    public static function recipient(Todo $todo): ?object
    {
        $userId = $todo->checklist?->user_id;
        $model = (string) config('griglia.user_model', 'App\\Models\\User');
        if (! $userId || ! class_exists($model)) {
            return null;
        }
        $user = $model::find($userId);

        return $user && method_exists($user, 'notify') ? $user : null;
    }

    private static function send(Todo $todo, object $notification): void
    {
        $user = self::recipient($todo);
        if (! $user) {
            return;
        }
        try {
            $user->notify($notification);
        } catch (\Throwable $e) {
            Log::warning('griglia: notification failed: '.$e->getMessage(), ['todo' => $todo->id, 'type' => $notification::class]);
        }
    }
}
