<?php

namespace Alle80\Devboard\Notifications;

use Alle80\Devboard\Models\Todo;
use Illuminate\Support\Str;

/** The agent asked questions on a task (devboard:check --ask). */
class QuestionAsked extends DevboardNotification
{
    /** @param string[] $questions */
    public function __construct(Todo $todo, public array $questions = [])
    {
        parent::__construct($todo);
    }

    public function kind(): string
    {
        return 'question_asked';
    }

    public function icon(): string
    {
        return '❓';
    }

    public function title(): string
    {
        return __('devboard::t.notif.question_title', ['title' => $this->todo->title, 'count' => count($this->questions)]);
    }

    public function body(): string
    {
        $first = $this->questions[0] ?? '';

        return $first !== '' ? Str::limit(preg_replace('/\s+/', ' ', $first), 180) : __('devboard::t.notif.question_body');
    }
}
