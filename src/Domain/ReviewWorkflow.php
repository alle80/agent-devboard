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

    public function approve(Todo $attempt, string $actor, array $report = []): Todo
    {
        return $this->decide($attempt, $actor, ReviewOutcome::Approved, $report);
    }

    public function requestChanges(Todo $attempt, string $actor, array $report = []): Todo
    {
        return $this->decide($attempt, $actor, ReviewOutcome::ChangesRequested, $report);
    }

    private function decide(Todo $attempt, string $actor, ReviewOutcome $outcome, array $report): Todo
    {
        return DB::transaction(function () use ($attempt, $actor, $outcome, $report) {
            if (! $attempt->review_of_id) {
                throw new DomainException('Only a review attempt can receive a review decision.');
            }
            $original = Todo::query()->lockForUpdate()->findOrFail($attempt->review_of_id);
            $lockedAttempt = Todo::query()->lockForUpdate()->findOrFail($attempt->getKey());
            if ($lockedAttempt->review_of_id !== $original->getKey()) {
                throw new DomainException('The review attempt no longer belongs to this original.');
            }
            if ($lockedAttempt->review_outcome !== null) {
                if ($lockedAttempt->review_outcome === $outcome) {
                    return $original;
                }
                throw new DomainException('This review attempt already has the opposite outcome.');
            }
            if (Agent::effective($lockedAttempt) !== $actor) {
                throw new DomainException('Only the assigned reviewer may decide this review.');
            }
            if (! $lockedAttempt->working || $lockedAttempt->completed || $lockedAttempt->question) {
                throw new DomainException('Only a working review attempt can be decided.');
            }
            if ($original->review_status !== ReviewStatus::InReview || $original->completed) {
                throw new DomainException('The original task is not awaiting this review.');
            }
            $allowedReport = array_intersect_key($report, array_flip([
                'claude_comment', 'result_summary', 'outcome', 'tokens_in', 'tokens_out',
            ]));
            $lockedAttempt->update($allowedReport + [
                'working' => false, 'completed' => true, 'question' => false, 'open_to_work' => false,
                'stopped_at' => null, 'progress' => null, 'phase' => null, 'result_seen' => false,
                'review_outcome' => $outcome,
            ]);
            if ($outcome === ReviewOutcome::Approved) {
                $original->update([
                    'completed' => true, 'result_seen' => false, 'review_status' => ReviewStatus::Approved,
                ]);
            } else {
                $original->update([
                    'completed' => false, 'open_to_work' => true, 'stopped_at' => null,
                    'progress' => null, 'phase' => null, 'outcome' => null,
                    'review_status' => ReviewStatus::ChangesRequested,
                ]);
            }
            return $original;
        }, 3);
    }
}
