<?php

namespace Alle80\Devboard\Tests\Feature;

use Alle80\Devboard\Models\Checklist;
use Alle80\Devboard\Models\Todo;
use Alle80\Devboard\Support\Stats;
use Alle80\Devboard\Tests\TestCase;

/** Task 298: deleting a list/task is a soft delete — the statistics survive; the trash can be emptied. */
class SoftDeleteTest extends TestCase
{
    protected Checklist $list;

    protected Todo $todo;

    protected function setUp(): void
    {
        parent::setUp();
        $user = $this->actingAsUser();
        $this->list = Checklist::create(['name' => 'proj', 'user_id' => $user->id]);
        $this->todo = Todo::create([
            'title' => 'done work', 'order' => 1, 'checklist_id' => $this->list->id,
            'completed' => true, 'work_seconds' => 120, 'tokens_in' => 1000, 'tokens_out' => 50,
        ]);
    }

    public function test_deleting_a_task_keeps_its_statistics(): void
    {
        $this->todo->delete();

        $this->assertSoftDeleted('todos', ['id' => $this->todo->id]);
        $this->assertSame(0, $this->list->todos()->count(), 'gone from the board scope');

        $rows = Stats::history($this->list);
        $this->assertCount(1, $rows, 'stats still read the trashed row');
        $this->assertSame(120, $rows->first()['work_seconds']);
        $this->assertSame(1000, Stats::aggregate($rows)['tokens_in']);
    }

    public function test_deleting_a_list_soft_deletes_its_tasks_and_keeps_stats(): void
    {
        $this->list->delete();

        $this->assertSoftDeleted('checklists', ['id' => $this->list->id]);
        $this->assertSoftDeleted('todos', ['id' => $this->todo->id]);
        $this->assertSame(0, Checklist::mine()->count(), 'gone from the menus');

        $trashed = Checklist::withTrashed()->find($this->list->id);
        $this->assertSame(1, Stats::aggregate(Stats::history($trashed))['count'], 'list stats survive');
    }

    public function test_empty_trash_purges_and_respects_days_and_dry_run(): void
    {
        $keep = Todo::create(['title' => 'fresh', 'order' => 2, 'checklist_id' => $this->list->id]);
        $keep->delete();
        $this->todo->delete();
        Todo::withTrashed()->whereKey($this->todo->id)->update(['deleted_at' => now()->subDays(10)]);

        $this->artisan('devboard:empty-trash', ['--days' => 7, '--dry-run' => true])
            ->expectsOutputToContain('1 trashed tasks (dry run, nothing deleted)')->assertSuccessful();
        $this->assertSame(2, Todo::onlyTrashed()->count());

        $this->artisan('devboard:empty-trash', ['--days' => 7])->assertSuccessful();
        $this->assertNull(Todo::withTrashed()->find($this->todo->id), 'old one purged');
        $this->assertNotNull(Todo::withTrashed()->find($keep->id), 'recent one kept');

        $this->list->delete();
        $this->artisan('devboard:empty-trash')->assertSuccessful();
        $this->assertSame(0, Checklist::withTrashed()->count());
        $this->assertSame(0, Todo::withTrashed()->count(), 'list purge carries its tasks');
    }
}
