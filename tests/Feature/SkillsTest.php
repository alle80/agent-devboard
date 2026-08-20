<?php

namespace Alle80\Griglia\Tests\Feature;

use Alle80\Griglia\Livewire\IngredientModal;
use Alle80\Griglia\Models\Checklist;
use Alle80\Griglia\Models\Todo;
use Alle80\Griglia\Support\Skills;
use Alle80\Griglia\Tests\TestCase;
use Livewire\Livewire;

/** Agent skills catalogue (griglia:skills-import) + per-task choice in the modal + griglia:check output. */
class SkillsTest extends TestCase
{
    protected Todo $todo;

    protected function setUp(): void
    {
        parent::setUp();
        config(['griglia.skills_file' => sys_get_temp_dir().'/griglia-skills-test-'.getmypid().'.json']);
        @unlink(Skills::path());
        $user = $this->actingAsUser();
        $list = Checklist::create(['name' => 'dev', 'user_id' => $user->id]);
        session(['checklist_id' => $list->id]);
        $this->todo = Todo::create(['title' => 'Restyle', 'order' => 1, 'checklist_id' => $list->id, 'open_to_work' => true]);
    }

    protected function tearDown(): void
    {
        @unlink(Skills::path());
        parent::tearDown();
    }

    public function test_import_from_file_and_stdin_like_json(): void
    {
        $this->assertSame([], Skills::all());
        $file = sys_get_temp_dir().'/skills-in.json';
        file_put_contents($file, json_encode([
            ['name' => 'tdd', 'description' => 'Test-driven development', 'source' => 'plugin:mattpocock-skills'],
            'frontend-design',
            ['name' => ' '],
        ]));
        $this->artisan('griglia:skills-import', ['--file' => $file])->expectsOutputToContain('2 skills imported')->assertSuccessful();
        $all = Skills::all();
        $this->assertSame(['frontend-design', 'tdd'], array_keys($all));
        $this->assertSame('Test-driven development', $all['tdd']['description']);

        $this->artisan('griglia:skills-import', ['--file' => '/nonexistent.json'])->assertFailed();
    }

    public function test_modal_toggles_skills_and_check_prints_them(): void
    {
        Skills::import([['name' => 'tdd', 'description' => 'TDD'], ['name' => 'frontend-design']]);

        $modal = Livewire::test(IngredientModal::class)->call('openFor', $this->todo->id);
        $modal->assertSee('tdd')->assertSee('frontend-design');

        $modal->call('toggleSkill', 'tdd');
        $this->assertSame(['tdd'], $this->todo->fresh()->skills);
        $modal->call('toggleSkill', 'frontend-design');
        $this->assertSame(['tdd', 'frontend-design'], $this->todo->fresh()->skills);
        $modal->call('toggleSkill', 'bogus');
        $this->assertSame(['tdd', 'frontend-design'], $this->todo->fresh()->skills, 'unknown skills are ignored');

        $this->artisan('griglia:check')->expectsOutputToContain('🧩 skills to activate for this task (Skill tool): tdd, frontend-design')->assertSuccessful();

        $modal->call('toggleSkill', 'tdd');
        $this->assertSame(['frontend-design'], $this->todo->fresh()->skills);
        $modal->call('toggleSkill', 'frontend-design');
        $this->assertNull($this->todo->fresh()->skills);

        // Completed = read-only
        $this->todo->update(['completed' => true, 'skills' => ['tdd']]);
        Livewire::test(IngredientModal::class)->call('openFor', $this->todo->id)->call('toggleSkill', 'tdd');
        $this->assertSame(['tdd'], $this->todo->fresh()->skills);
    }

    public function test_catalogue_is_filtered_by_the_agent_of_the_task(): void
    {
        // The SKILL.md format is portable, but a skill only exists for the agent that has it installed:
        // the modal only offers the ones tagged with that agent (plus the untagged, shared ones) — task 375
        config(['griglia.agents' => 'claude:Claude Code,codex:Codex CLI']);
        Skills::import([
            ['name' => 'code-review', 'description' => 'built-in', 'agents' => ['claude']],
            ['name' => 'claude-only', 'description' => 'built-in', 'agents' => ['claude']],
            ['name' => 'codex-only', 'description' => 'codex', 'agents' => 'codex'],
            ['name' => 'shared', 'description' => 'in ~/.agents/skills'],
        ]);
        $this->assertSame(['claude'], Skills::all()['code-review']['agents']);
        $this->assertSame(['codex'], Skills::all()['codex-only']['agents'], 'a string is accepted too');
        $this->assertSame(['claude-only', 'code-review', 'shared'], array_keys(Skills::forAgent('claude')));
        $this->assertSame(['codex-only', 'shared'], array_keys(Skills::forAgent('codex')));
        $this->assertCount(4, Skills::forAgent(null), 'no agent known: the whole catalogue');

        $this->todo->update(['agent' => 'codex', 'skills' => ['code-review']]);
        $modal = Livewire::test(IngredientModal::class)->call('openFor', $this->todo->id);
        $modal->assertSee('codex-only')->assertSee('shared')
            ->assertDontSee('claude-only')          // Claude Code only: this task is Codex's
            ->assertSee('code-review');             // already chosen: still there, so it can be removed

        $modal->call('toggleSkill', 'shared');
        $this->assertSame(['code-review', 'shared'], $this->todo->fresh()->skills);
        $modal->call('toggleSkill', 'artifact-design');
        $this->assertSame(['code-review', 'shared'], $this->todo->fresh()->skills, 'unknown skill');
        // A skill of another agent cannot be added…
        $this->todo->update(['skills' => null]);
        $modal->call('toggleSkill', 'code-review');
        $this->assertNull($this->todo->fresh()->skills);
        // …but one already chosen (e.g. before the task changed agent) can still be removed
        $this->todo->update(['skills' => ['code-review']]);
        $modal->call('toggleSkill', 'code-review');
        $this->assertNull($this->todo->fresh()->skills);
    }
}
