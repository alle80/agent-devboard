<?php

namespace Alle80\Griglia\Tests\Feature;

use Alle80\Griglia\Livewire\IngredientModal;
use Alle80\Griglia\Livewire\TodoList;
use Alle80\Griglia\Models\Checklist;
use Alle80\Griglia\Models\Todo;
use Alle80\Griglia\Tests\TestCase;
use Livewire\Livewire;

/**
 * The row highlight of a task that asks for attention: colour of the border = outcome reported by the
 * agent (ok/alert/blocked), violet while there are open questions, nothing once the result is opened.
 */
class AttentionTest extends TestCase
{
    protected Checklist $list;

    protected function setUp(): void
    {
        parent::setUp();
        $user = $this->actingAsUser();
        $this->list = Checklist::create(['name' => 'dev', 'user_id' => $user->id]);
        session(['checklist_id' => $this->list->id]);
    }


    protected function todo(array $attrs = []): Todo
    {
        return Todo::create($attrs + ['title' => 'X', 'order' => 1, 'checklist_id' => $this->list->id]);
    }


    public function test_done_without_outcome_is_a_plain_ok_result(): void
    {
        $todo = $this->todo(['open_to_work' => true]);

        $this->artisan('griglia:check', ['--done' => $todo->id, '--comment' => 'Shipped'])->assertSuccessful();

        $todo->refresh();
        $this->assertSame('ok', $todo->outcome);
        $this->assertSame('ok', $todo->attention());
    }


    public function test_done_with_alert_or_blocked_is_kept(): void
    {
        foreach (['alert', 'blocked'] as $outcome) {
            $todo = $this->todo(['open_to_work' => true]);

            $this->artisan('griglia:check', ['--done' => $todo->id, '--outcome' => $outcome])->assertSuccessful();

            $this->assertSame($outcome, $todo->fresh()->outcome);
            $this->assertSame($outcome, $todo->fresh()->attention());
        }
    }


    public function test_an_unknown_outcome_is_refused_and_changes_nothing(): void
    {
        $todo = $this->todo(['open_to_work' => true]);

        $this->artisan('griglia:check', ['--done' => $todo->id, '--outcome' => 'panic'])->assertFailed();

        $this->assertFalse($todo->fresh()->completed);
    }


    public function test_taking_the_task_again_clears_the_previous_outcome(): void
    {
        $todo = $this->todo(['completed' => true, 'outcome' => 'blocked', 'result_seen' => true]);
        $todo->update(['completed' => false]); // resumed by hand: the old result no longer applies

        $this->artisan('griglia:check', ['--take' => $todo->id])->assertSuccessful();

        $this->assertNull($todo->fresh()->outcome);
        $this->assertNull($todo->fresh()->attention());
    }


    public function test_open_questions_win_and_are_violet(): void
    {
        $todo = $this->todo(['open_to_work' => true, 'outcome' => 'ok']);

        $this->artisan('griglia:check', ['--ask' => $todo->id, '--q' => ['Which one?']])->assertSuccessful();

        $this->assertSame('question', $todo->fresh()->attention());
    }


    public function test_opening_the_result_clears_the_highlight(): void
    {
        $todo = $this->todo(['completed' => true, 'result_seen' => false, 'outcome' => 'alert']);
        $this->assertSame('alert', $todo->attention());

        Livewire::test(IngredientModal::class)->call('openFor', $todo->id);

        $this->assertNull($todo->fresh()->attention());
    }


    public function test_a_task_closed_by_the_user_has_no_outcome(): void
    {
        $todo = $this->todo(['outcome' => 'blocked']);

        Livewire::test(IngredientModal::class)->call('openFor', $todo->id)->call('setState', 'done');

        $this->assertNull($todo->fresh()->outcome);
        $this->assertNull($todo->fresh()->attention());
    }


    public function test_the_row_carries_the_outcome_class(): void
    {
        $todo = $this->todo(['completed' => true, 'result_seen' => false, 'outcome' => 'blocked']);

        Livewire::test(TodoList::class)->assertSee('db-att-blocked')->assertSee(__('griglia::t.result_blocked'));

        $todo->update(['result_seen' => true]);
        Livewire::test(TodoList::class)->assertDontSee('db-att-blocked');
    }

    public function test_plain_ok_uses_green_instead_of_the_theme_accent(): void
    {
        $css = file_get_contents(__DIR__.'/../../resources/css/griglia.css');

        $this->assertStringContainsString('.todo-row.db-att-ok', $css);
        $this->assertMatchesRegularExpression('/\.todo-row\.db-att-ok\s*\{[^}]*--db-att:\s*#22c55e/s', $css);
    }


}
