<?php

namespace Alle80\Griglia\Tests\Feature;

use Alle80\Griglia\Livewire\IngredientModal;
use Alle80\Griglia\Livewire\TodoList;
use Alle80\Griglia\Models\Checklist;
use Alle80\Griglia\Models\Todo;
use Alle80\Griglia\Tests\TestCase;
use Livewire\Livewire;

class ModalCommandsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $user = $this->actingAsUser();
        $this->list = Checklist::create(['name' => 'dev', 'user_id' => $user->id]);
    }

    public function test_create_new_makes_a_blank_todo_and_opens_the_modal(): void
    {
        $modal = Livewire::test(IngredientModal::class)
            ->call('createNew')
            ->assertDispatched('ingredients-updated');

        $todo = Todo::latest('id')->first();
        $this->assertSame('', $todo->title);
        $this->assertSame($this->list->id, $todo->checklist_id);
        $this->assertTrue($modal->instance()->open, 'the modal opens for the new task');
    }

    public function test_create_new_at_a_position_makes_room_like_the_plus_between_rows(): void
    {
        $a = Todo::create(['title' => 'A', 'order' => 1, 'checklist_id' => $this->list->id]);
        $b = Todo::create(['title' => 'B', 'order' => 2, 'checklist_id' => $this->list->id]);

        $modal = Livewire::test(IngredientModal::class)->call('createNew', 2);
        $new = Todo::latest('id')->first();
        $this->assertSame('', $new->title);
        $this->assertSame(2, $new->order, 'inserted before B');
        $this->assertSame(1, $a->fresh()->order);
        $this->assertSame(3, $b->fresh()->order, 'B moved down');
        $this->assertTrue($modal->instance()->open);

        // Out-of-range position → appended at the end
        Livewire::test(IngredientModal::class)->call('createNew', 99);
        $this->assertSame(4, Todo::latest('id')->first()->order);
    }

    public function test_set_state_from_the_badge_menu(): void
    {
        $todo = Todo::create(['title' => 'S', 'order' => 1, 'checklist_id' => $this->list->id, 'working' => true, 'progress' => 40]);
        $modal = Livewire::test(IngredientModal::class)->call('openFor', $todo->id);

        $modal->call('setState', 'done')->assertDispatched('toast');
        $todo->refresh();
        $this->assertTrue($todo->completed);
        $this->assertFalse($todo->working);
        $this->assertNotNull($todo->stopped_at, 'choosing a state while the agent works = stop');
        $this->assertNull($todo->progress);
        $this->assertSame('done', $modal->instance()->stateKey());

        // Done is done: the badge cannot take a closed task back to work (task 348).
        $modal->call('setState', 'open');
        $todo->refresh();
        $this->assertTrue($todo->completed);
        $this->assertFalse($todo->open_to_work);

        // On a task that is not closed, the badge still moves between waiting and open to work.
        $other = Todo::create(['title' => 'T', 'order' => 2, 'checklist_id' => $this->list->id]);
        $m2 = Livewire::test(IngredientModal::class)->call('openFor', $other->id);

        $m2->call('setState', 'open');
        $other->refresh();
        $this->assertTrue($other->open_to_work);
        $this->assertNull($other->stopped_at);

        $m2->call('setState', 'waiting');
        $other->refresh();
        $this->assertFalse($other->open_to_work);
        $this->assertFalse($other->completed);

        $modal->call('setState', 'working'); // not settable by the user
        $this->assertFalse($todo->fresh()->working);
    }

    public function test_move_to_another_list(): void
    {
        $a = Todo::create(['title' => 'A', 'order' => 1, 'checklist_id' => $this->list->id]);
        $b = Todo::create(['title' => 'B', 'order' => 2, 'checklist_id' => $this->list->id]);
        $other = Checklist::create(['name' => 'other', 'user_id' => $this->list->user_id]);
        Todo::create(['title' => 'X', 'order' => 1, 'checklist_id' => $other->id]);
        $foreign = Checklist::create(['name' => 'foreign', 'user_id' => \Alle80\Griglia\Tests\Support\User::create(['name' => 'B', 'email' => 'b@x.it', 'password' => bcrypt('s')])->id]);

        $modal = Livewire::test(IngredientModal::class)->call('openFor', $a->id)->assertSee('other')->assertDontSee('foreign');
        $modal->call('moveTo', $foreign->id);
        $this->assertSame($this->list->id, $a->fresh()->checklist_id, 'not my list → ignored');

        $modal->call('moveTo', $other->id)->assertDispatched('toast');
        $a->refresh();
        $this->assertSame($other->id, $a->checklist_id);
        $this->assertSame(2, $a->order, 'appended after X');
        $this->assertSame(1, $b->fresh()->order, 'gap closed in the source list');
        $this->assertFalse($modal->instance()->open);
    }

    public function test_state_key_reflects_the_todo(): void
    {
        $todo = Todo::create(['title' => 'X', 'order' => 1, 'checklist_id' => $this->list->id, 'open_to_work' => true]);

        $modal = Livewire::test(IngredientModal::class)->call('openFor', $todo->id);
        $this->assertSame('open', $modal->instance()->stateKey());
    }

    public function test_modal_toggle_open_to_work(): void
    {
        $todo = Todo::create(['title' => 'X', 'order' => 1, 'checklist_id' => $this->list->id]);

        Livewire::test(IngredientModal::class)
            ->call('openFor', $todo->id)
            ->call('toggleOpenToWork')
            ->assertDispatched('ingredients-updated');

        $this->assertTrue($todo->fresh()->open_to_work);
    }

    public function test_closing_a_blank_untouched_new_task_deletes_it(): void
    {
        $todo = Todo::create(['title' => '', 'order' => 1, 'checklist_id' => $this->list->id]);

        Livewire::test(IngredientModal::class)
            ->call('openFor', $todo->id)
            ->call('close');

        $this->assertNull($todo->fresh());
    }

    public function test_a_task_with_questions_can_be_taken_back_without_answering(): void
    {
        $todo = Todo::create(['title' => 'Ambiguous', 'order' => 1, 'checklist_id' => $this->list->id, 'question' => true]);
        $todo->questions()->create(['question' => 'Which of the two?']);

        Livewire::test(IngredientModal::class)
            ->call('openFor', $todo->id)
            ->call('setState', 'waiting');

        $todo->refresh();
        $this->assertFalse($todo->question, 'the task is free again');
        $this->assertFalse($todo->open_to_work);
        $this->assertSame(1, $todo->questions()->count(), 'the questions stay recorded');
    }

    public function test_the_modal_walks_to_the_previous_and_next_task(): void
    {
        // Following a plan from one task to the next without closing the modal (task 365).
        $a = Todo::create(['title' => 'A', 'order' => 1, 'checklist_id' => $this->list->id]);
        $b = Todo::create(['title' => 'B', 'order' => 2, 'checklist_id' => $this->list->id]);
        $c = Todo::create(['title' => 'C', 'order' => 3, 'checklist_id' => $this->list->id]);

        $m = Livewire::test(IngredientModal::class)->call('openFor', $b->id);
        $this->assertSame(2, $m->instance()->position());
        $this->assertSame($a->id, $m->instance()->siblingId(-1));
        $this->assertSame($c->id, $m->instance()->siblingId(1));

        $m->call('goSibling', 1);
        $this->assertSame($c->id, $m->instance()->todoId);
        $this->assertNull($m->instance()->siblingId(1), 'the last task has no next');

        $m->call('goSibling', -1);
        $this->assertSame($b->id, $m->instance()->todoId);

        // An archived task is not walked through.
        $b->update(['archived_at' => now()]);
        $m2 = Livewire::test(IngredientModal::class)->call('openFor', $a->id);
        $this->assertSame($c->id, $m2->instance()->siblingId(1));
    }

    public function test_the_modal_header_keeps_every_command_on_screen_on_a_phone(): void
    {
        // Task 399: at 360px the command row was clipped — the close button included. The header is now
        // two groups: nav (state + ‹ 3/7 ›) stays beside the close button, the tools drop to a second line.
        $a = Todo::create(['title' => 'A', 'order' => 1, 'checklist_id' => $this->list->id]);
        Todo::create(['title' => 'B', 'order' => 2, 'checklist_id' => $this->list->id]);

        $html = Livewire::test(IngredientModal::class)->call('openFor', $a->id)->html();

        $nav = strpos($html, 'modal-cmds-nav');
        $tools = strpos($html, 'modal-cmds-tools');
        $close = strpos($html, 'modal-close');
        $this->assertNotFalse($nav, 'the state badge and the prev/next arrows are their own group');
        $this->assertNotFalse($tools, 'agent, move, archive and delete are their own group');
        $this->assertNotFalse($close, 'the close button carries the hook the mobile layout orders');
        $this->assertTrue($nav < $tools && $tools < $close, 'DOM order: nav, tools, close');
        $this->assertStringContainsString('aria-label="Close"', $html);

        $css = file_get_contents(__DIR__.'/../../resources/css/griglia.css');
        $this->assertStringContainsString('.modal-cmds { display: contents; }', $css);
        $this->assertStringContainsString('.modal-cmds-tools { order: 3; flex-basis: 100%;', $css);
        $this->assertStringContainsString('.modal-head > .modal-close { order: 2; }', $css);
        $this->assertStringContainsString('.modal-head .db-cmd { min-width: 2.25rem; min-height: 2.25rem;', $css);
    }

    public function test_the_modal_header_uses_the_whole_bar(): void
    {
        // Task 421: everything used to be pushed against the right edge (ml-auto), half the bar empty and
        // the agent label cut mid-word. The two groups now sit on the two edges and the state says its name.
        $a = Todo::create(['title' => 'A', 'order' => 1, 'checklist_id' => $this->list->id]);

        $html = Livewire::test(IngredientModal::class)->call('openFor', $a->id)->html();

        $this->assertStringNotContainsString('modal-cmds ml-auto', $html, 'the command bar no longer hugs the right edge');
        $this->assertStringContainsString('db-state-name', $html);
        $this->assertStringContainsString('Waiting', $html, 'the state badge carries its own label');

        $css = file_get_contents(__DIR__.'/../../resources/css/griglia.css');
        $this->assertStringContainsString('.modal-cmds { flex: 1 1 auto; justify-content: space-between; }', $css);
        $this->assertStringContainsString('.db-state-name { display: none;', $css, 'the label steps aside on a narrow panel');
        $this->assertStringContainsString('.db-state-name { display: inline; }', $css, 'and comes back from md, where there is room');
        $this->assertStringContainsString('.db-agent-select { max-width: 16rem; }', $css, 'the agent label has room from md');
    }
}
