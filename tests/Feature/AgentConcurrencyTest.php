<?php

namespace Alle80\Griglia\Tests\Feature;

use Alle80\Griglia\Models\Checklist;
use Alle80\Griglia\Models\Todo;
use Alle80\Griglia\Tests\TestCase;

/** Two agents on the same board must not step on each other: ownership guard, per-agent 🆕 baseline, busy line. */
class AgentConcurrencyTest extends TestCase
{
    private Checklist $list;

    protected function setUp(): void
    {
        parent::setUp();
        config(['griglia.agents' => 'claude:Claude Code,codex:Codex CLI']);
        $user = $this->actingAsUser();
        $this->list = Checklist::create(['name' => 'dev', 'user_id' => $user->id]);
    }

    private function todo(string $title, array $attrs = []): Todo
    {
        return Todo::create(['title' => $title, 'order' => 1, 'checklist_id' => $this->list->id, 'open_to_work' => true] + $attrs);
    }

    public function test_taking_the_task_of_another_agent_is_refused(): void
    {
        $t = $this->todo('For codex', ['agent' => 'codex']);

        $this->artisan('griglia:check', ['--take' => $t->id, '--agent' => 'claude'])
            ->expectsOutputToContain('belongs to agent «Codex CLI»')
            ->assertFailed();
        $this->assertFalse($t->fresh()->working, 'the task must stay untouched');

        $this->artisan('griglia:check', ['--take' => $t->id, '--agent' => 'codex'])
            ->expectsOutputToContain('taken in charge')
            ->assertSuccessful();
        $this->assertTrue($t->fresh()->working);
    }

    public function test_closing_or_asking_on_another_agent_task_is_refused(): void
    {
        $t = $this->todo('For codex', ['agent' => 'codex', 'working' => true]);

        $this->artisan('griglia:check', ['--done' => $t->id, '--agent' => 'claude'])
            ->expectsOutputToContain('being worked on right now')
            ->assertFailed();
        $this->artisan('griglia:check', ['--ask' => $t->id, '--q' => ['?'], '--agent' => 'claude'])
            ->expectsOutputToContain('refusing to ask questions on')
            ->assertFailed();

        $t->refresh();
        $this->assertFalse($t->completed);
        $this->assertFalse($t->question);
        $this->assertSame(0, $t->questions()->count());
    }

    public function test_force_takes_the_task_anyway(): void
    {
        $t = $this->todo('For codex', ['agent' => 'codex']);

        $this->artisan('griglia:check', ['--take' => $t->id, '--agent' => 'claude', '--force' => true])
            ->expectsOutputToContain('taken in charge')
            ->assertSuccessful();
        $this->assertTrue($t->fresh()->working);
    }

    public function test_the_new_marker_baseline_is_per_agent(): void
    {
        $mine = $this->todo('For claude');

        // the other agent checking the board must not consume my 🆕 markers
        $this->artisan('griglia:check', ['--agent' => 'codex'])->assertSuccessful();
        $this->artisan('griglia:check', ['--agent' => 'claude'])
            ->expectsOutputToContain('🆕')
            ->assertSuccessful();
        // …and once I have seen it, it is not new any more
        $this->artisan('griglia:check', ['--agent' => 'claude'])
            ->doesntExpectOutputToContain('🆕 [ ] 🟢 #'.$mine->order)
            ->assertSuccessful();
    }

    public function test_check_shows_what_the_other_agents_are_working_on(): void
    {
        $this->todo('Release the package', ['agent' => 'codex', 'working' => true, 'open_to_work' => false]);

        $this->artisan('griglia:check', ['--agent' => 'claude'])
            ->expectsOutputToContain('🔒 busy elsewhere: Codex CLI on «Release the package»')
            ->assertSuccessful();
        // the agent doing the work does not need the warning about itself
        $this->artisan('griglia:check', ['--agent' => 'codex'])
            ->doesntExpectOutputToContain('🔒 busy elsewhere')
            ->assertSuccessful();
    }
}
