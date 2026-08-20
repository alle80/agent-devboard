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
}
