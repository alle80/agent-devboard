<?php

namespace Alle80\Griglia\Notifications;

use Illuminate\Support\Str;

/** The agent closed a task (griglia:check --done). */
class TodoCompleted extends GrigliaNotification
{
    public function kind(): string
    {
        return 'todo_completed';
    }

    public function icon(): string
    {
        return '✔';
    }

    public function title(): string
    {
        return __('griglia::t.notif.done_title', ['title' => $this->todo->title]);
    }

    public function body(): string
    {
        $c = trim((string) $this->todo->claude_comment);

        return $c !== '' ? Str::limit(preg_replace('/\s+/', ' ', $c), 180) : __('griglia::t.notif.done_body');
    }
}
