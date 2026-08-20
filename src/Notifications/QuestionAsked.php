<?php

namespace Alle80\Griglia\Notifications;

use Alle80\Griglia\Models\Todo;
use Illuminate\Support\Str;

/** The agent asked questions on a task (griglia:check --ask). */
class QuestionAsked extends GrigliaNotification
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
        return __('griglia::t.notif.question_title', ['title' => $this->todo->title, 'count' => count($this->questions)]);
    }

    public function body(): string
    {
        $first = $this->questions[0] ?? '';

        return $first !== '' ? Str::limit(preg_replace('/\s+/', ' ', $first), 180) : __('griglia::t.notif.question_body');
    }
}
