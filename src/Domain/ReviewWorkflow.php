<?php

namespace Alle80\Griglia\Domain;

use Alle80\Griglia\Agent;
use Alle80\Griglia\Models\Todo;
use DomainException;
use Illuminate\Support\Facades\DB;

/** Transaction boundary for submitting executor work to a separate review task. */
class ReviewWorkflow
{
    public function submit(Todo $todo, string $actor): Todo
    {
        return DB::transaction(function () use ($todo, $actor) {
            /** @var Todo $original */
            $original = Todo::query()->lockForUpdate()->findOrFail($todo->getKey());
            $reviewer = $original->reviewer_agent;

            if ($original->isReviewAttempt()) {
                throw new DomainException('A review attempt cannot be submitted for another review.');
            }
            if (! $reviewer || ! array_key_exists($reviewer, Agent::all())) {
                throw new DomainException('The task has no valid reviewer.');
            }
            if (Agent::effective($original) !== $actor) {
                throw new DomainException('Only the assigned executor may submit this task.');
            }
            if (! $original->working || $original->completed || $original->question) {
                throw new DomainException('Only a working task can be submitted for review.');
            }
            if ($original->reviewAttempts()->whereNull('review_outcome')->exists()) {
                throw new DomainException('This task already has a pending review attempt.');
            }

            $round = ((int) $original->reviewAttempts()->max('review_round')) + 1;
            $original->update([
                'working' => false,
                'open_to_work' => false,
                'question' => false,
                'stopped_at' => null,
                'progress' => null,
                'phase' => null,
                'review_status' => ReviewStatus::InReview,
            ]);

            return $original->reviewAttempts()->create([
                'title' => 'Review · '.$original->title,
                'order' => $original->order,
                'checklist_id' => $original->checklist_id,
                'agent' => $reviewer,
                'review_round' => $round,
                'open_to_work' => true,
            ]);
        }, 3);
    }
}
