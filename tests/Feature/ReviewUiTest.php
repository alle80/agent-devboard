<?php

namespace Alle80\Griglia\Tests\Feature;

use Alle80\Griglia\Domain\ReviewStatus;
use Alle80\Griglia\Livewire\IngredientModal;
use Alle80\Griglia\Models\Checklist;
use Alle80\Griglia\Models\Todo;
use Alle80\Griglia\Tests\TestCase;
use Livewire\Livewire;

class ReviewUiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config()->set('griglia.agents', ['codex' => 'Codex CLI', 'claude' => 'Claude Code']);
        config()->set('griglia.agent_key', 'codex');
        $user = $this->actingAsUser();
        $this->list = Checklist::create(['name' => 'dev', 'user_id' => $user->id, 'agent' => 'codex']);
    }
    protected function tearDown(): void
    {
        Todo::query()->whereNotNull("review_of_id")->forceDelete();
        parent::tearDown();
    }


    public function test_reviewer_can_be_selected_and_cleared_in_the_task_modal(): void
    {
        $todo = Todo::create(['title' => 'Build it', 'order' => 1, 'checklist_id' => $this->list->id]);
        $modal = Livewire::test(IngredientModal::class)->call('openFor', $todo->id)
            ->assertSee('Optional reviewer')->assertSee('Claude Code');
        $modal->call('setReviewer', 'claude');
        $this->assertSame('claude', $todo->fresh()->reviewer_agent);
        $modal->call('setReviewer', '');
        $this->assertNull($todo->fresh()->reviewer_agent);
    }

    public function test_modal_shows_review_status_and_links_both_tasks(): void
    {
        $original = Todo::create(['title' => 'Original', 'order' => 1, 'checklist_id' => $this->list->id, 'agent' => 'codex', 'reviewer_agent' => 'claude', 'review_status' => ReviewStatus::InReview]);
        $attempt = Todo::create(['title' => 'Review Original', 'order' => 2, 'checklist_id' => $this->list->id, 'agent' => 'claude', 'review_of_id' => $original->id, 'review_round' => 1, 'open_to_work' => true]);
        Livewire::test(IngredientModal::class)->call('openFor', $original->id)->assertSee('In review')->assertSee('Open review #1');
        Livewire::test(IngredientModal::class)->call('openFor', $attempt->id)->assertSee('Review assigned to Claude Code')->assertSee('Original task: Original');
    }
}
