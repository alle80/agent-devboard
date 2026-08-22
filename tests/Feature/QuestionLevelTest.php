<?php

namespace Alle80\Griglia\Tests\Feature;

use Alle80\Griglia\Livewire\ContextPage;
use Alle80\Griglia\Livewire\SettingsPage;
use Alle80\Griglia\Models\Checklist;
use Alle80\Griglia\Models\ContextBlock;
use Alle80\Griglia\Models\ContextGroup;
use Alle80\Griglia\Settings\AgentSettings;
use Alle80\Griglia\Support\Context;
use Alle80\Griglia\Support\QuestionLevel;
use Alle80\Griglia\Tests\TestCase;
use Livewire\Livewire;

/** Task 499: the question level — five steps, previewed in /settings, written into the agent context, printed by griglia:check. */
class QuestionLevelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsUser();
    }

    public function test_saving_the_level_writes_the_context_block_and_updates_it_in_place(): void
    {
        $this->assertSame(0, ContextBlock::count());

        $page = Livewire::test(SettingsPage::class)->assertSee('Question level')->assertSee('Preview of the context block');
        $page->set('values.agent.autonomy', 'paranoid')->assertDispatched('toast');
        $this->assertSame('paranoid', app(AgentSettings::class)->refresh()->autonomy);

        $block = ContextBlock::where('key', QuestionLevel::BLOCK_KEY)->first();
        $this->assertNotNull($block, 'the managed block is created on save');
        $this->assertSame('Questions to the user', $block->group->title);
        $this->assertStringStartsWith('**Question level: Paranoid** — ', $block->body);
        $this->assertStringContainsString('second `--ask`', $block->body);
        $this->assertStringContainsString($block->body, Context::export());

        // Changed again: the same block is rewritten where it is, and its switch is respected
        $block->update(['enabled' => false]);
        $page->set('values.agent.autonomy', 'autonomous');
        $this->assertSame(1, ContextBlock::where('key', QuestionLevel::BLOCK_KEY)->count());
        $this->assertSame(1, ContextGroup::count());
        $block->refresh();
        $this->assertStringStartsWith('**Question level: Autonomous agent** — ', $block->body);
        $this->assertFalse($block->enabled);
        $this->assertStringNotContainsString('Question level', Context::export());
    }

    public function test_every_level_has_its_own_rules_and_the_old_values_are_mapped(): void
    {
        $this->assertSame('ask', QuestionLevel::current(), 'default level');
        $previews = QuestionLevel::previews();
        $this->assertSame(QuestionLevel::LEVELS, array_keys($previews));
        $this->assertCount(5, array_unique($previews));
        foreach (QuestionLevel::LEVELS as $level) {
            $this->assertStringContainsString('--ask', $previews[$level], $level);
        }
        $this->assertSame('Paranoid', QuestionLevel::name('paranoid'));

        // The pre-499 values: an unknown stored value reads as `ask`; the settings migration maps `decide` to `autonomous`
        $s = app(AgentSettings::class);
        $s->autonomy = 'decide';
        $s->save();
        $this->assertSame('ask', QuestionLevel::current());
        (require __DIR__.'/../../database/settings/2026_08_22_130000_question_level_scale.php')->up();
        $this->assertSame('autonomous', app(AgentSettings::class)->refresh()->autonomy);
    }

    public function test_check_prints_the_rules_of_the_level(): void
    {
        Checklist::create(['name' => 'dev', 'user_id' => auth()->id()]);
        $this->artisan('griglia:check')
            ->expectsOutputToContain('❓ question level — FOLLOW IT: **Question level: Ask questions** — When the request is ambiguous')
            ->assertSuccessful();

        $s = app(AgentSettings::class);
        $s->autonomy = 'many';
        $s->save();
        $this->artisan('griglia:check')->expectsOutputToContain('**Question level: Ask many questions** — Before writing code')->assertSuccessful();
    }

    public function test_import_keeps_one_managed_block(): void
    {
        QuestionLevel::sync();
        $file = sys_get_temp_dir().'/ctx-499.md';

        // A re-import of the generated file: the block is there as plain markdown → adopted where it is, not duplicated
        file_put_contents($file, "# proj\n\nIntro.\n\n## Rules\n\n".QuestionLevel::body()."\n");
        $this->artisan('griglia:context', ['action' => 'import', '--file' => $file, '--replace' => true])->assertSuccessful();
        $this->assertSame(1, ContextBlock::where('body', 'like', '**Question level:%')->count());
        $managed = ContextBlock::where('key', QuestionLevel::BLOCK_KEY)->first();
        $this->assertSame('Rules', $managed->group->title);
        $this->assertSame(2, ContextGroup::count());

        // An import without the block: the managed one is appended again in its own group
        file_put_contents($file, "# proj\n\nIntro.\n");
        $this->artisan('griglia:context', ['action' => 'import', '--file' => $file, '--replace' => true])->assertSuccessful();
        $this->assertSame(1, ContextBlock::where('key', QuestionLevel::BLOCK_KEY)->count());
        $this->assertSame(['proj', 'Questions to the user'], ContextGroup::orderBy('order')->pluck('title')->all());
    }

    public function test_the_managed_block_is_read_only_on_the_context_page(): void
    {
        $block = QuestionLevel::sync();
        $page = Livewire::test(ContextPage::class)->assertSee('generated from Settings')->assertSee('Question level');
        $page->call('startEdit', $block->id)->assertSet('editingId', null);
        $page->call('toggleBlock', $block->id);
        $this->assertFalse($block->fresh()->enabled);
    }
}
