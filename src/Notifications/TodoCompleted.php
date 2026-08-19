<?php

namespace Alle80\Devboard\Notifications;

use Illuminate\Support\Str;

/** The agent closed a task (devboard:check --done). */
class TodoCompleted extends DevboardNotification
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
        return __('devboard::t.notif.done_title', ['title' => $this->todo->title]);
    }

    public function body(): string
    {
        $c = trim((string) $this->todo->claude_comment);

        return $c !== '' ? Str::limit(preg_replace('/\s+/', ' ', $c), 180) : __('devboard::t.notif.done_body');
    }
}
