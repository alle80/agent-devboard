<?php

namespace Alle80\Devboard\Console;

use Alle80\Devboard\Models\Checklist;
use Alle80\Devboard\Models\Todo;
use Illuminate\Console\Command;

/**
 * Deleting a list/task is a soft delete (the statistics keep reading the rows — task 298).
 * This command frees the data for real: it force-deletes what has been trashed (attachment files included).
 * NOTE: the statistics of the purged items are lost — that is the point of emptying the trash.
 */
class EmptyTrash extends Command
{
    protected $signature = 'devboard:empty-trash
        {--days=0 : Only purge items deleted more than N days ago (0 = everything)}
        {--dry-run : Show what would be purged without deleting}';

    protected $description = 'Permanently delete soft-deleted lists and tasks (their statistics disappear)';

    public function handle(): int
    {
        $before = now()->subDays(max(0, (int) $this->option('days')));
        $lists = Checklist::onlyTrashed()->where('deleted_at', '<=', $before)->get();
        $todos = Todo::onlyTrashed()->where('deleted_at', '<=', $before)
            ->whereNotIn('checklist_id', $lists->pluck('id'))->get();

        $this->line(sprintf('%d trashed lists, %d trashed tasks%s', $lists->count(), $todos->count(),
            $this->option('dry-run') ? ' (dry run, nothing deleted)' : ''));

        if ($this->option('dry-run')) {
            $lists->each(fn (Checklist $l) => $this->line('  list  «'.$l->name.'» (id:'.$l->id.')'));
            $todos->each(fn (Todo $t) => $this->line('  task  «'.$t->title.'» (id:'.$t->id.')'));

            return self::SUCCESS;
        }

        $lists->each->forceDelete();
        $todos->each->forceDelete();
        $this->info('Trash emptied.');

        return self::SUCCESS;
    }
}
