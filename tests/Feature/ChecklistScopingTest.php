<?php

namespace Alle80\Devboard\Tests\Feature;

use Alle80\Devboard\Models\Checklist;
use Alle80\Devboard\Models\Todo;
use Alle80\Devboard\Tests\Support\User;
use Alle80\Devboard\Tests\TestCase;

class ChecklistScopingTest extends TestCase
{
    public function test_lists_are_scoped_to_the_authenticated_user(): void
    {
        $other = User::create(['name' => 'Other', 'email' => 'other@example.com', 'password' => 'x']);
        Checklist::create(['name' => 'Theirs', 'user_id' => $other->id]);

        $this->actingAsUser();
        $id = Checklist::currentId(); // creates the default list

        $this->assertSame(1, Checklist::mine()->count());
        $this->assertSame('My list', Checklist::find($id)->name);
        $this->assertFalse(Checklist::mine()->where('name', 'Theirs')->exists());
    }

    public function test_default_list_name_is_translated(): void
    {
        app()->setLocale('it');
        $this->actingAsUser();
        $this->assertSame('La mia lista', Checklist::find(Checklist::currentId())->name);
    }

    public function test_deleting_a_todo_removes_children(): void
    {
        $this->actingAsUser();
        $todo = Todo::create(['title' => 'T', 'order' => 1, 'checklist_id' => Checklist::currentId()]);
        $todo->ingredients()->create(['name' => 'sub', 'order' => 1]);
        $todo->questions()->create(['question' => 'q?', 'order' => 1]);
        $todo->delete();

        $this->assertDatabaseCount('ingredients', 0);
        $this->assertDatabaseCount('questions', 0);
    }
}
