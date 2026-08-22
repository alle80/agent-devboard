<?php

namespace Alle80\Griglia\Tests\Feature;

use Alle80\Griglia\Domain\ReviewOutcome;
use Alle80\Griglia\Domain\ReviewStatus;
use Alle80\Griglia\Domain\ReviewWorkflow;
use Alle80\Griglia\Models\Checklist;
use Alle80\Griglia\Models\Todo;
use Alle80\Griglia\Tests\TestCase;
use DomainException;
use Illuminate\Database\QueryException;

class ReviewWorkflowTest extends TestCase
{
    private Checklist $list;

    protected function setUp(): void
    {
        parent::setUp();
        config(['griglia.agents' => ['codex' => 'Codex CLI', 'claude' => 'Claude Code'], 'griglia.agent_key' => 'codex']);
        $user = $this->actingAsUser();
        $this->list = Checklist::create(['name' => 'dev', 'user_id' => $user->id, 'agent' => 'codex']);
    }

    protected function tearDown(): void
    {
        // Testbench rolls host migrations back before package tables; remove self-FK children first on SQLite.
        Todo::query()->whereNotNull('review_of_id')->forceDelete();
        parent::tearDown();
    }

    public function test_done_without_reviewer_keeps_the_existing_completion_path(): void
    {
        $todo = $this->task();

        $this->artisan('griglia:check', ['--agent' => 'codex', '--done' => $todo->id])->assertSuccessful();

        $this->assertTrue($todo->fresh()->completed);
        $this->assertCount(0, $todo->reviewAttempts()->get());
    }

    public function test_done_with_reviewer_submits_and_creates_a_linked_attempt(): void
    {
        $todo = $this->task(['reviewer_agent' => 'claude']);

        $this->artisan('griglia:check', ['--agent' => 'codex', '--done' => $todo->id, '--comment' => 'Ready'])
            ->expectsOutputToContain('submitted for review')->assertSuccessful();

        $todo->refresh();
        $attempt = $todo->reviewAttempts()->sole();
        $this->assertFalse($todo->completed);
        $this->assertFalse($todo->working);
        $this->assertSame(ReviewStatus::InReview, $todo->review_status);
        $this->assertSame('Ready', $todo->claude_comment);
        $this->assertSame('claude', $attempt->agent);
        $this->assertSame(1, $attempt->review_round);
        $this->assertTrue($attempt->open_to_work);
        $this->assertNull($attempt->depends_on_id, 'review attempts do not join the plan chain');
    }

    public function test_invalid_reviewer_and_self_review_are_rejected(): void
    {
        foreach (['missing', 'codex'] as $reviewer) {
            try {
                $this->task(['reviewer_agent' => $reviewer]);
                $this->fail("Reviewer {$reviewer} should have been rejected");
            } catch (DomainException $e) {
                $this->assertNotSame('', $e->getMessage());
            }
        }
    }

    public function test_optional_reviewer_can_be_added_changed_and_removed_before_work_starts(): void
    {
        $todo = $this->task(['working' => false, 'open_to_work' => true]);

        $todo->update(['reviewer_agent' => 'claude']);
        $this->assertSame('claude', $todo->fresh()->reviewer_agent);

        $todo->update(['reviewer_agent' => null]);
        $this->assertNull($todo->fresh()->reviewer_agent);
    }

    public function test_reviewed_original_cannot_be_completed_directly(): void
    {
        $todo = $this->task(['reviewer_agent' => 'claude']);

        $this->expectException(DomainException::class);
        $todo->update(['completed' => true]);
    }

    public function test_review_round_is_unique_per_original(): void
    {
        $todo = $this->task(['reviewer_agent' => 'claude']);
        $attempt = $todo->reviewAttempts()->create([
            'title' => 'Review', 'order' => 1, 'checklist_id' => $this->list->id,
            'agent' => 'claude', 'review_round' => 1, 'open_to_work' => true,
        ]);

        $this->expectException(QueryException::class);
        $attempt->replicate()->save();
    }

    public function test_pending_attempt_cannot_use_ordinary_done_or_break_the_active_pair(): void
    {
        $original = $this->task(['reviewer_agent' => 'claude']);
        $this->artisan('griglia:check', ['--agent' => 'codex', '--done' => $original->id])->assertSuccessful();
        $attempt = $original->reviewAttempts()->sole();

        foreach ([
            fn () => $attempt->update(['completed' => true]),
            fn () => $attempt->update(['agent' => 'codex']),
            fn () => $original->update(['reviewer_agent' => null]),
        ] as $mutation) {
            try {
                $mutation();
                $this->fail('An invalid active-review mutation should have been rejected.');
            } catch (DomainException $e) {
                $this->assertNotSame('', $e->getMessage());
            }
        }
    }

    public function test_reviewer_can_approve_and_complete_the_aggregate(): void
    {
        $original = $this->task(['reviewer_agent' => 'claude']);
        $dependent = Todo::create([
            'title' => 'Next', 'order' => 2, 'checklist_id' => $this->list->id,
            'agent' => 'codex', 'depends_on_id' => $original->id,
        ]);
        $this->artisan('griglia:check', ['--agent' => 'codex', '--done' => $original->id])->assertSuccessful();
        $attempt = $original->reviewAttempts()->sole();
        $this->artisan('griglia:check', ['--agent' => 'claude', '--take' => $attempt->id])->assertSuccessful();

        $this->artisan('griglia:check', [
            '--agent' => 'claude', '--approve' => $attempt->id, '--comment' => 'Looks good',
        ])->expectsOutputToContain('review approved')->assertSuccessful();

        $this->assertSame(ReviewOutcome::Approved, $attempt->fresh()->review_outcome);
        $this->assertTrue($attempt->fresh()->completed);
        $this->assertSame('Looks good', $attempt->fresh()->claude_comment);
        $this->assertSame(ReviewStatus::Approved, $original->fresh()->review_status);
        $this->assertTrue($original->fresh()->completed);
        $this->assertTrue($dependent->fresh()->open_to_work);
    }

    public function test_reviewer_can_request_changes_and_executor_can_resubmit(): void
    {
        $original = $this->task(['reviewer_agent' => 'claude']);
        $this->artisan('griglia:check', ['--agent' => 'codex', '--done' => $original->id])->assertSuccessful();
        $attempt = $original->reviewAttempts()->sole();
        $this->artisan('griglia:check', ['--agent' => 'claude', '--take' => $attempt->id])->assertSuccessful();

        $this->artisan('griglia:check', [
            '--agent' => 'claude', '--request-changes' => $attempt->id,
            '--comment' => 'Add the missing regression test',
        ])->expectsOutputToContain('changes requested')->assertSuccessful();

        $this->assertSame(ReviewOutcome::ChangesRequested, $attempt->fresh()->review_outcome);
        $this->assertSame('Add the missing regression test', $attempt->fresh()->claude_comment);
        $this->assertSame(ReviewStatus::ChangesRequested, $original->fresh()->review_status);
        $this->assertTrue($original->fresh()->open_to_work);
        $this->assertFalse($original->fresh()->completed);

        $this->artisan('griglia:check', ['--agent' => 'codex', '--take' => $original->id])->assertSuccessful();
        $this->artisan('griglia:check', ['--agent' => 'codex', '--done' => $original->id])->assertSuccessful();
        $this->assertSame(2, $original->reviewAttempts()->count());
        $this->assertSame(2, $original->reviewAttempts()->reorder()->latest('review_round')->first()->review_round);
    }

    public function test_review_decisions_require_the_assigned_reviewer_and_are_immutable(): void
    {
        $original = $this->task(['reviewer_agent' => 'claude']);
        $this->artisan('griglia:check', ['--agent' => 'codex', '--done' => $original->id])->assertSuccessful();
        $attempt = $original->reviewAttempts()->sole();
        $this->artisan('griglia:check', ['--agent' => 'claude', '--take' => $attempt->id])->assertSuccessful();

        $this->artisan('griglia:check', ['--agent' => 'codex', '--approve' => $attempt->id])
            ->expectsOutputToContain('refusing to approve')->assertFailed();
        $this->artisan('griglia:check', ['--agent' => 'claude', '--approve' => $attempt->id])->assertSuccessful();
        $this->artisan('griglia:check', ['--agent' => 'claude', '--approve' => $attempt->id])->assertSuccessful();

        $this->expectException(DomainException::class);
        app(ReviewWorkflow::class)->requestChanges($attempt->fresh(), 'claude');
    }

    public function test_review_decision_requires_a_taken_attempt_and_change_request_requires_a_comment(): void
    {
        $original = $this->task(['reviewer_agent' => 'claude']);
        $this->artisan('griglia:check', ['--agent' => 'codex', '--done' => $original->id])->assertSuccessful();
        $attempt = $original->reviewAttempts()->sole();

        try {
            app(ReviewWorkflow::class)->approve($attempt, 'claude');
            $this->fail('An untaken review attempt should not be approvable.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('working review attempt', $e->getMessage());
        }

        $this->artisan('griglia:check', ['--agent' => 'claude', '--take' => $attempt->id])->assertSuccessful();
        $this->artisan('griglia:check', ['--agent' => 'claude', '--request-changes' => $attempt->id])
            ->expectsOutputToContain('requires --comment')->assertFailed();

        $this->assertNull($attempt->fresh()->review_outcome);
        $this->assertSame(ReviewStatus::InReview, $original->fresh()->review_status);
    }

    public function test_executor_cannot_submit_a_task_that_is_not_working_or_submit_twice(): void
    {
        $notWorking = $this->task([
            'working' => false, 'open_to_work' => true, 'reviewer_agent' => 'claude',
        ]);

        try {
            app(ReviewWorkflow::class)->submit($notWorking, 'codex');
            $this->fail('A task that is not working should not be submittable.');
        } catch (DomainException $e) {
            $this->assertStringContainsString('working task', $e->getMessage());
        }
        $this->assertCount(0, $notWorking->reviewAttempts()->get());

        $working = $this->task(['reviewer_agent' => 'claude', 'order' => 2]);
        $workflow = app(ReviewWorkflow::class);
        $workflow->submit($working, 'codex');

        $this->expectException(DomainException::class);
        $workflow->submit($working->fresh(), 'codex');
    }

    private function task(array $attributes = []): Todo
    {
        return Todo::create($attributes + [
            'title' => 'Implement feature', 'order' => 1, 'checklist_id' => $this->list->id,
            'agent' => 'codex', 'working' => true, 'open_to_work' => false,
        ]);
    }
}
