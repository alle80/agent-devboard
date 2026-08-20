<?php

namespace Alle80\Griglia\Tests\Feature;

use Alle80\Griglia\Support\AgentStatus;
use Alle80\Griglia\Tests\TestCase;
use Carbon\CarbonImmutable;

/** Agents status: derived values (used/remaining/level/reset), import command, page states. */
class AgentStatusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['griglia.agent_status_file' => sys_get_temp_dir().'/griglia-agent-status-'.getmypid().'.json']);
        @unlink(AgentStatus::path());
        $this->actingAsUser();
    }

    protected function tearDown(): void
    {
        @unlink(AgentStatus::path());
        parent::tearDown();
    }

    public function test_window_maths_and_levels(): void
    {
        $now = CarbonImmutable::parse('2026-08-19 10:00:00');
        $w = AgentStatus::computeWindow(['utilization' => 47.0, 'resets_at' => '2026-08-21T10:00:00+00:00'], $now);
        $this->assertSame(47.0, $w['used']);
        $this->assertSame(53.0, $w['remaining']);
        $this->assertSame(47, $w['bar']);
        $this->assertSame('ok', $w['level']);
        $this->assertSame(2 * 86400, $w['resets_in']);
        $this->assertSame('2g 0h', AgentStatus::countdown($w['resets_in']));

        $this->assertSame('warn', AgentStatus::computeWindow(['utilization' => 70], $now)['level']);
        $this->assertSame('critical', AgentStatus::computeWindow(['utilization' => 90], $now)['level']);
        $over = AgentStatus::computeWindow(['utilization' => 120.4], $now);
        $this->assertSame('over', $over['level']);
        $this->assertSame(0.0, $over['remaining'], 'remaining never below zero');
        $this->assertSame(100, $over['bar'], 'bar capped at 100');
        $fresh = AgentStatus::computeWindow(['utilization' => 0], $now);
        $this->assertSame(0.0, $fresh['used']);
        $this->assertSame('ok', $fresh['level']);
        $na = AgentStatus::computeWindow(['utilization' => null, 'resets_at' => 'garbage'], $now);
        $this->assertNull($na['used']);
        $this->assertSame('na', $na['level']);
        $this->assertNull($na['resets_in']);
        $this->assertSame('1h 05m', AgentStatus::countdown(3900));
        $this->assertSame('—', AgentStatus::countdown(null));
    }

    public function test_import_command_page_and_states(): void
    {
        $this->get('/agents')->assertOk()->assertSee('No agent data yet');

        $file = sys_get_temp_dir().'/agents-in.json';
        file_put_contents($file, json_encode(['updated_at' => now()->toIso8601String(), 'agents' => [
            ['key' => 'claude', 'name' => 'Claude Code', 'plan' => 'Max 5x', 'plan_kind' => 'flat', 'windows' => [
                ['key' => 'five_hour', 'label' => '5h', 'utilization' => 4, 'resets_at' => now()->addHours(2)->toIso8601String()],
                ['key' => 'seven_day', 'label' => '7d', 'utilization' => 95, 'resets_at' => now()->addDays(2)->toIso8601String()],
            ]],
            ['key' => 'codex', 'name' => 'Codex CLI', 'windows' => []],
            ['key' => 'broken', 'name' => 'Broken', 'error' => 'usage endpoint: 401'],
            ['name' => 'no key → ignored'],
        ]]));
        $this->artisan('griglia:agent-status-import', ['--file' => $file])->expectsOutputToContain('3 agents imported')->assertSuccessful();
        $this->artisan('griglia:agent-status-import', ['--file' => '/nope.json'])->assertFailed();

        $status = AgentStatus::agents();
        $this->assertFalse($status['stale']);
        $this->assertSame('critical', $status['agents'][0]['level'], 'worst window wins');
        $this->assertSame('na', $status['agents'][1]['level']);
        $this->assertSame('error', $status['agents'][2]['level']);

        $this->get('/agents')->assertOk()
            ->assertSee('Claude Code')->assertSee('Max 5x')->assertSee('95% used')->assertSee('5% left')->assertSee('almost exhausted')
            ->assertSee('Not configured')->assertSee('usage endpoint: 401')->assertDontSee('Stale data');

        AgentStatus::import(['updated_at' => now()->subHour()->toIso8601String(), 'agents' => [['key' => 'claude', 'name' => 'Claude Code', 'windows' => []]]]);
        $this->get('/agents')->assertOk()->assertSee('Stale data');
    }
}
