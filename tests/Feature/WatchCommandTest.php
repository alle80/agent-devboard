<?php

namespace Alle80\Griglia\Tests\Feature;

use Alle80\Griglia\Console\Watch;
use Alle80\Griglia\Models\Checklist;
use Alle80\Griglia\Tests\TestCase;

class WatchCommandTest extends TestCase
{
    public function test_warns_on_unknown_list(): void
    {
        $this->artisan('griglia:watch', ['--once' => true, '--list' => 'nope'])
            ->expectsOutputToContain('No list named')
            ->assertFailed();
    }

    public function test_runs_once_on_a_known_list(): void
    {
        $user = $this->actingAsUser();
        Checklist::create(['name' => 'dev', 'user_id' => $user->id]);

        $this->artisan('griglia:watch', ['--once' => true])->assertSuccessful();
    }

    public function test_detects_open_to_work(): void
    {
        $prev = [7 => ['title' => 'X', 'otw' => false, 'working' => false, 'question' => false, 'stopped' => null, 'answered' => 0]];
        $now = [7 => ['title' => 'X', 'otw' => true, 'working' => false, 'question' => false, 'stopped' => null, 'answered' => 0]];

        $lines = Watch::changes($prev, $now, '12:00:00');
        $this->assertCount(1, $lines);
        $this->assertStringContainsString('OPEN TO WORK', $lines[0]);
        $this->assertStringContainsString('id:7', $lines[0]);
    }

    public function test_detects_answers_received(): void
    {
        $prev = [7 => ['title' => 'X', 'otw' => false, 'working' => false, 'question' => true, 'stopped' => null, 'answered' => 0]];
        $now = [7 => ['title' => 'X', 'otw' => true, 'working' => false, 'question' => false, 'stopped' => null, 'answered' => 2]];

        $lines = Watch::changes($prev, $now, '12:00:00');
        $this->assertStringContainsString('ANSWERS RECEIVED', $lines[0]);
    }

    public function test_detects_stop(): void
    {
        $prev = [7 => ['title' => 'X', 'otw' => false, 'working' => true, 'question' => false, 'stopped' => null, 'answered' => 0]];
        $now = [7 => ['title' => 'X', 'otw' => false, 'working' => false, 'question' => false, 'stopped' => 1700000000, 'answered' => 0]];

        $lines = Watch::changes($prev, $now, '12:00:00');
        $this->assertStringContainsString('STOP REQUESTED', $lines[0]);
    }

    public function test_no_change_no_output(): void
    {
        $snap = [7 => ['title' => 'X', 'otw' => true, 'working' => false, 'question' => false, 'stopped' => null, 'answered' => 0]];
        $this->assertSame([], Watch::changes($snap, $snap, '12:00:00'));
    }
}
