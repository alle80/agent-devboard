<?php

namespace Alle80\Devboard\Events;

use Alle80\Devboard\Models\Todo;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Qualcosa è cambiato in un todo (stato, titolo, sotto-task…): lo si dice via
 * Reverb al proprietario della lista, così le pagine aperte (desktop, telefono)
 * si aggiornano senza ricaricare. Vedi Alle80\Devboard\Support\Live.
 */
class TodoChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public int $userId,
        public int $checklistId,
        public int $todoId,
        public string $title,
        public string $state,
        /** 'cli' quando la modifica arriva da artisan (es. sviluppo:check dell'assistente), 'web' altrimenti */
        public string $source,
        public bool $deleted = false,
        /** true se è cambiato lo stato (spunta / 🟢 / 🔧 / ❓): la pagina mostra un toast */
        public bool $stateChanged = false,
    ) {}

    public static function stateOf(Todo $todo): string
    {
        return match (true) {
            (bool) $todo->completed => 'done',
            (bool) $todo->question => 'question',
            (bool) $todo->working => 'working',
            (bool) $todo->open_to_work => 'otw',
            default => 'waiting',
        };
    }

    public function broadcastOn(): PrivateChannel|Channel
    {
        if (\Alle80\Devboard\Mode::isLocal()) {
            return new Channel(\Alle80\Devboard\Mode::broadcastChannel());
        }

        return new PrivateChannel(\Alle80\Devboard\Mode::broadcastChannel($this->userId));
    }

    public function broadcastAs(): string
    {
        return 'TodoChanged';
    }

    public function broadcastWith(): array
    {
        return [
            'checklist_id' => $this->checklistId,
            'todo_id' => $this->todoId,
            'title' => $this->title,
            'state' => $this->state,
            'source' => $this->source,
            'deleted' => $this->deleted,
            'state_changed' => $this->stateChanged,
        ];
    }
}
