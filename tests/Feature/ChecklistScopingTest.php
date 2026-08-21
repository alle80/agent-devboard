<?php

namespace Alle80\Griglia\Tests\Feature;

use Alle80\Griglia\Models\Checklist;
use Alle80\Griglia\Models\Todo;
use Alle80\Griglia\Tests\Support\User;
use Alle80\Griglia\Tests\TestCase;

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

    public function test_default_list_name_is_translated_when_the_config_is_empty(): void
    {
        config(['griglia.default_list_name' => '']);
        app()->setLocale('it');
        $this->actingAsUser();
        $this->assertSame('La mia lista', Checklist::find(Checklist::currentId())->name);
    }

    public function test_default_list_name_config_wins_over_the_translation(): void
    {
        config(['griglia.default_list_name' => 'Cose da fare']);
        app()->setLocale('it');
        $this->actingAsUser();
        $this->assertSame('Cose da fare', Checklist::find(Checklist::currentId())->name);
    }

    public function test_deleting_a_todo_is_soft_and_force_delete_removes_children(): void
    {
        $this->actingAsUser();
        $todo = Todo::create(['title' => 'T', 'order' => 1, 'checklist_id' => Checklist::currentId()]);
        $todo->ingredients()->create(['name' => 'sub', 'order' => 1]);
        $todo->questions()->create(['question' => 'q?', 'order' => 1]);

        $todo->delete(); // soft: the row (and its children) survive so the statistics keep reading it
        $this->assertSoftDeleted('todos', ['id' => $todo->id]);
        $this->assertDatabaseCount('ingredients', 1);

        $todo->forceDelete();
        $this->assertDatabaseCount('todos', 0);
        $this->assertDatabaseCount('ingredients', 0);
        $this->assertDatabaseCount('questions', 0);
    }
}
