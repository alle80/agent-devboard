<?php

namespace Alle80\Griglia\Tests\Feature;

use Alle80\Griglia\Models\Checklist;
use Alle80\Griglia\Models\Todo;
use Alle80\Griglia\Settings\AppSettings;
use Alle80\Griglia\Tests\TestCase;
use Illuminate\Console\Scheduling\Schedule;

class AutoArchiveTest extends TestCase
{
    public function test_command_archives_only_completed_items_older_than_the_setting(): void
    {
        $this->actingAsUser();
        $list = Checklist::findOrFail(Checklist::currentId());
        $settings = app(AppSettings::class);
        $settings->auto_archive_days = 30;
        $settings->save();

        $old = Todo::create(['title' => 'old', 'order' => 1, 'checklist_id' => $list->id, 'completed' => true]);
        $fresh = Todo::create(['title' => 'fresh', 'order' => 2, 'checklist_id' => $list->id, 'completed' => true]);
        $old->timestamps = false;
        $old->updated_at = now()->subDays(31);
        $old->save();

        $this->artisan('griglia:auto-archive')->assertSuccessful();

        $this->assertNotNull($old->fresh()->archived_at);
        $this->assertNull($fresh->fresh()->archived_at);
    }

    public function test_nightly_schedule_prevents_overlapping_runs(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => str_contains($event->command ?? '', 'griglia:auto-archive'));

        $this->assertNotNull($event);
        $this->assertTrue($event->withoutOverlapping);
        $this->assertSame('30 3 * * *', $event->expression);
    }
}
