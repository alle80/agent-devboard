<?php

namespace Alle80\Griglia\Livewire;

use Alle80\Griglia\Mode;
use Alle80\Griglia\Models\Checklist;
use Livewire\Component;

/**
 * In-app bell 🔔: the board's own notifications (database channel) for the logged-in user, with the
 * unread count; refreshed live through the same private broadcast channel as the lists.
 */
class NotificationBell extends Component
{
    public int $userId = 0;

    public function boot(): void
    {
        $this->userId = (int) auth()->id();
    }

    protected function getListeners(): array
    {

        return [Mode::echoListener() => '$refresh'];
    }

    protected function user(): ?object
    {
        $u = auth()->user();

        return $u && method_exists($u, 'notifications') ? $u : null;
    }

    /** Click on a notification: mark it read and open the todo (same list → modal; other list → switch). */
    public function openNotification(string $id): void
    {
        if (! ($u = $this->user())) {
            return;
        }
        $n = $u->notifications()->whereKey($id)->first();
        if (! $n) {
            return;
        }
        $n->markAsRead();
        $data = (array) $n->data;
        $todoId = (int) ($data['todo_id'] ?? 0);
        $listId = (int) ($data['checklist_id'] ?? 0);
        if (! $todoId) {
            return;
        }
        if ($listId && $listId !== Checklist::currentId() && Checklist::mine()->whereKey($listId)->exists()) {
            session(['checklist_id' => $listId, 'griglia_open_todo' => $todoId]);
            $this->js('window.location.reload()');

            return;
        }
        $this->dispatch('open-ingredients', todoId: $todoId);
    }

    public function markAllRead(): void
    {
        $this->user()?->unreadNotifications()->update(['read_at' => now()]);
    }

    public function render()
    {
        $u = $this->user();

        return view('griglia::livewire.notification-bell', [
            'items' => $u ? $u->notifications()->latest()->limit(20)->get() : collect(),
            'unread' => $u ? $u->unreadNotifications()->count() : 0,
        ]);
    }
}
