<?php

namespace Alle80\Griglia\Tests\Feature;

use Alle80\Griglia\Livewire\ChecklistSwitcher;
use Alle80\Griglia\Models\Checklist;
use Alle80\Griglia\Models\Todo;
use Alle80\Griglia\Tests\TestCase;
use Livewire\Livewire;

class ChecklistArchiveTest extends TestCase
{
    public function test_archiving_a_list_hides_it_from_the_menu_and_keeps_its_tasks(): void
    {
        $this->actingAsUser();
        $keep = Checklist::currentId();
        $list = Checklist::create(['name' => 'Old stuff', 'user_id' => auth()->id()]);
        Todo::create(['title' => 'T', 'order' => 1, 'checklist_id' => $list->id]);

        Livewire::test(ChecklistSwitcher::class)->call('archiveList', $list->id);

        $this->assertNotNull($list->fresh()->archived_at);
        $this->assertFalse(Checklist::mine()->whereKey($list->id)->exists());
        $this->assertTrue(Checklist::mineArchived()->whereKey($list->id)->exists());
        $this->assertDatabaseHas('todos', ['checklist_id' => $list->id, 'title' => 'T']);
        $this->assertSame($keep, Checklist::currentId());
    }

    public function test_archived_lists_are_listed_and_restored(): void
    {
        $this->actingAsUser();
        Checklist::currentId();
        $list = Checklist::create(['name' => 'Old stuff', 'user_id' => auth()->id(), 'archived_at' => now()]);

        Livewire::test(ChecklistSwitcher::class)
            ->assertDontSee('Old stuff')
            ->call('toggleArchived')
            ->assertSee('Old stuff')
            ->call('restoreList', $list->id);

        $this->assertNull($list->fresh()->archived_at);
        $this->assertTrue(Checklist::mine()->whereKey($list->id)->exists());
    }

    public function test_the_only_active_list_cannot_be_archived(): void
    {
        $this->actingAsUser();
        $only = Checklist::currentId();

        Livewire::test(ChecklistSwitcher::class)->call('archiveList', $only);

        $this->assertNull(Checklist::find($only)->archived_at);
    }

    public function test_archiving_the_current_list_moves_the_session_to_another_one(): void
    {
        $this->actingAsUser();
        $current = Checklist::currentId();
        $other = Checklist::create(['name' => 'Other', 'user_id' => auth()->id()]);

        Livewire::test(ChecklistSwitcher::class)->call('archiveList', $current);

        $this->assertSame($other->id, Checklist::currentId());
    }
}
