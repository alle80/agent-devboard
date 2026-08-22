<?php

namespace Alle80\Griglia\Tests\Feature;

use Alle80\Griglia\Domain\ReviewStatus;
use Alle80\Griglia\Models\Checklist;
use Alle80\Griglia\Models\Todo;
use Alle80\Griglia\Tests\TestCase;
use DomainException;

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

        $this->expectException(\Illuminate\Database\QueryException::class);
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

    private function task(array $attributes = []): Todo
    {
        return Todo::create($attributes + [
            'title' => 'Implement feature', 'order' => 1, 'checklist_id' => $this->list->id,
            'agent' => 'codex', 'working' => true, 'open_to_work' => false,
        ]);
    }
}
