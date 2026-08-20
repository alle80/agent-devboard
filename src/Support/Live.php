<?php

namespace Alle80\Griglia\Support;

use Alle80\Griglia\Events\TodoChanged;
use Alle80\Griglia\Models\Todo;
use Illuminate\Support\Facades\Log;

/**
 * Notifica live (Reverb) delle modifiche ai todo. Se il server WebSocket non
 * risponde, la modifica va comunque a buon fine: si logga e basta.
 */
class Live
{
    public static function todoChanged(Todo $todo, bool $deleted = false, bool $stateChanged = false): void
    {
        if (config('broadcasting.default') === 'null' || config('broadcasting.default') === 'log') {
            return;
        }

        $todo->loadMissing('checklist');

        if (! $todo->checklist) {
            return;
        }

        try {
            broadcast(new TodoChanged(
                userId: (int) $todo->checklist->user_id,
                checklistId: (int) $todo->checklist_id,
                todoId: (int) $todo->id,
                title: (string) $todo->title,
                state: TodoChanged::stateOf($todo),
                source: app()->runningInConsole() ? 'cli' : 'web',
                deleted: $deleted,
                stateChanged: $stateChanged,
            ));
        } catch (\Throwable $e) {
            Log::warning('Live: broadcast fallito', ['todo' => $todo->id, 'error' => $e->getMessage()]);
        }
    }
}
