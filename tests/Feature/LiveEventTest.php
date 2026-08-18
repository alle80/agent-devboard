<?php

namespace Alle80\Devboard\Tests\Feature;

use Alle80\Devboard\Events\TodoChanged;
use Alle80\Devboard\Models\Checklist;
use Alle80\Devboard\Models\Todo;
use Alle80\Devboard\Tests\TestCase;
use Illuminate\Support\Facades\Event;

class LiveEventTest extends TestCase
{
    public function test_todo_changes_broadcast_to_the_owner_channel(): void
    {
        $user = $this->actingAsUser();
        config(['broadcasting.default' => 'reverb', 'devboard.broadcast_channel' => 'App.Models.User.{id}']);
        Event::fake([TodoChanged::class]);

        $todo = Todo::create(['title' => 'T', 'order' => 1, 'checklist_id' => Checklist::currentId()]);
        $todo->update(['working' => true]);
        $todo->ingredients()->create(['name' => 'sub', 'order' => 1]);

        Event::assertDispatched(TodoChanged::class, function (TodoChanged $e) use ($user, $todo) {
            return $e->userId === $user->id && $e->todoId === $todo->id
                && $e->broadcastOn()->name === 'private-App.Models.User.'.$user->id
                && $e->broadcastAs() === 'TodoChanged';
        });
        Event::assertDispatched(TodoChanged::class, fn (TodoChanged $e) => $e->stateChanged && $e->state === 'working');
        Event::assertDispatchedTimes(TodoChanged::class, 3);
    }

    public function test_nothing_is_broadcast_without_a_broadcaster(): void
    {
        $this->actingAsUser();
        config(['broadcasting.default' => 'null']);
        Event::fake([TodoChanged::class]);
        Todo::create(['title' => 'T', 'order' => 1, 'checklist_id' => Checklist::currentId()]);
        Event::assertNotDispatched(TodoChanged::class);
    }
}
