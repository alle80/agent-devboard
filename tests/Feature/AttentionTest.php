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

        Livewire::test(TodoList::class)->assertSee('db-att-blocked');

        $todo->update(['result_seen' => true]);
        Livewire::test(TodoList::class)->assertDontSee('db-att-blocked');
    }

    /**
     * The colour is written on the row itself, not only in the stylesheet: an app that runs these views
     * from `vendor/` while its CSS is built from another copy of the package showed no border at all
     * (tasks 397, 402, 406). Inline also beats the grey filter `.tl-done` puts on completed rows.
     */
    public function test_the_row_paints_the_border_inline(): void
    {
        foreach (Todo::ATTENTION_COLORS as $level => $colour) {
            $todo = $level === 'question'
                ? $this->todo(['question' => true])
                : $this->todo(['completed' => true, 'result_seen' => false, 'outcome' => $level === 'ok' ? null : $level]);

            $this->assertSame($level, $todo->attention());
            $this->assertSame($colour, $todo->attentionColor());

            Livewire::test(TodoList::class)
                ->assertSee('border-color: '.$colour, false)
                ->assertSee('filter: none', false);

            $todo->delete();
        }
    }

    public function test_a_row_that_asks_for_nothing_has_no_inline_border(): void
    {
        $this->todo(['completed' => true, 'result_seen' => true, 'outcome' => 'alert']);

        Livewire::test(TodoList::class)->assertDontSee('border-color: #eab308', false);
    }

    /** The user asked for the coloured border and nothing else: no badge in the row, no chip in the modal. */
    public function test_the_highlight_shows_no_badge_and_no_chip(): void
    {
        $todo = $this->todo(['completed' => true, 'result_seen' => false, 'outcome' => 'alert', 'claude_comment' => 'done']);

        Livewire::test(TodoList::class)->assertDontSee('db-attention-badge');

        Livewire::test(IngredientModal::class)->call('openFor', $todo->id)->assertDontSee('db-outcome');

        $css = file_get_contents(__DIR__.'/../../resources/css/griglia.css');
        $this->assertStringNotContainsString('.db-attention-badge', $css);
        $this->assertStringNotContainsString('.db-outcome', $css);
    }

    public function test_plain_ok_uses_green_instead_of_the_theme_accent(): void
    {
        $css = file_get_contents(__DIR__.'/../../resources/css/griglia.css');

        $this->assertStringContainsString('.todo-row.db-att-ok', $css);
        $this->assertMatchesRegularExpression('/\.todo-row\.db-att-ok\s*\{[^}]*--db-att:\s*#22c55e/s', $css);
    }

    /**
     * The highlight paints the card's own border, and it survives the look a theme gives to completed
     * rows: `.tl-done` fades them and may greyscale them (`--tl-done-filter`), which used to turn the
     * green/yellow/red border grey — the row looked exactly like every other done row (task 406).
     */
    public function test_the_highlight_colours_the_card_border_and_escapes_the_done_greying(): void
    {
        $css = file_get_contents(__DIR__.'/../../resources/css/griglia.css');

        preg_match('/\.todo-row\.db-attention[^{]*\{(.*?)\}/s', $css, $m);
        $rule = $m[1] ?? '';

        $this->assertStringContainsString('border-color: var(--db-att)', $rule);
        $this->assertMatchesRegularExpression('/border-width:\s*max\(/', $rule);
        $this->assertMatchesRegularExpression('/filter:\s*none\s*!important/', $rule);
        $this->assertMatchesRegularExpression('/opacity:\s*1\s*!important/', $rule);
    }
}
