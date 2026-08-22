<?php

namespace Alle80\Griglia\Tests\Feature;

use Alle80\Griglia\Livewire\ContextPage;
use Alle80\Griglia\Models\ContextBlock;
use Alle80\Griglia\Models\ContextGroup;
use Alle80\Griglia\Support\Context;
use Alle80\Griglia\Tests\TestCase;
use Livewire\Livewire;

/** Agent context as switchable groups/blocks: parse/import/export + the /context page. */
class ContextTest extends TestCase
{
    private const MD = <<<'MD'
# my-project

Intro paragraph.

## How to work

- **Artisan**: always in the container.
- **Assets**: run npm via docker.
  second line of the same bullet

Standalone paragraph with `code`.

```bash
echo "fenced - not a bullet"
```

## Skills

### Issue tracker

Issues live on GitHub.
MD;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsUser();
    }

    public function test_parse_splits_groups_and_blocks(): void
    {
        $g = Context::parse(self::MD);
        $this->assertSame(['my-project', 'How to work', 'Skills'], array_column($g, 'title'));
        $this->assertSame(['Intro paragraph.'], array_column($g[0]['blocks'], 'body'));
        $this->assertSame(['Artisan', 'Assets', 'Standalone paragraph with code.', 'bash'], array_column($g[1]['blocks'], 'title'));
        $this->assertStringContainsString("second line of the same bullet", $g[1]['blocks'][1]['body']);
        $this->assertStringContainsString('fenced - not a bullet', $g[1]['blocks'][3]['body'], 'fenced code stays one block');
        $this->assertSame('Issue tracker', $g[2]['blocks'][0]['title']);
        $this->assertStringContainsString("### Issue tracker\n\nIssues live on GitHub.", $g[2]['blocks'][0]['body'], '### heading joined with its paragraph');
    }

    public function test_import_export_and_switches(): void
    {
        $file = sys_get_temp_dir().'/ctx.md';
        file_put_contents($file, self::MD);
        $this->artisan('griglia:context', ['action' => 'import', '--file' => $file])->expectsOutputToContain('3 groups, 6 blocks imported')->assertSuccessful();
        $this->artisan('griglia:context', ['action' => 'import', '--file' => $file])->assertFailed();
        $this->assertSame(4, ContextGroup::count()); // 3 imported + the managed question-level group (task 499)

        // Everything enabled → export reproduces the content
        $out = Context::export();
        $this->assertStringStartsWith("# my-project\n\nIntro paragraph.\n\n## How to work\n\n- **Artisan**", $out);
        $this->assertStringContainsString("### Issue tracker\n\nIssues live on GitHub.", $out);

        // Disable a block and a whole group
        ContextBlock::where('title', 'Assets')->update(['enabled' => false]);
        ContextGroup::where('title', 'Skills')->update(['enabled' => false]);
        $out = Context::export();
        $this->assertStringNotContainsString('Assets', $out);
        $this->assertStringNotContainsString('## Skills', $out);
        $this->assertStringContainsString('Artisan', $out);
        $this->assertStringContainsString('## Skills', Context::export(all: true));
        [$on, $total] = Context::tokens();
        $this->assertLessThan($total, $on);
        $this->artisan('griglia:context', ['action' => 'status'])->expectsOutputToContain('4 groups, 7 blocks')->assertSuccessful();
        $this->artisan('griglia:context', ['action' => 'export'])->expectsOutputToContain('Artisan')->assertSuccessful();
    }

    public function test_page_toggles_selects_and_edits(): void
    {
        Context::import(self::MD);
        $how = ContextGroup::where('title', 'How to work')->first();
        $artisan = ContextBlock::where('title', 'Artisan')->first();
        $assets = ContextBlock::where('title', 'Assets')->first();

        $page = Livewire::test(ContextPage::class)->assertSee('How to work')->assertSee('Artisan');

        $page->call('toggleGroup', $how->id);
        $this->assertFalse($how->fresh()->enabled);
        $page->call('toggleGroup', $how->id);

        // multi-select: select two, disable both, then group select + enable
        $page->call('toggleSelect', $artisan->id)->call('toggleSelect', $assets->id)->assertSee('2 selected');
        $page->call('setSelected', false);
        $this->assertFalse($artisan->fresh()->enabled);
        $this->assertFalse($assets->fresh()->enabled);
        $page->call('selectGroup', $how->id, true)->call('setSelected', true);
        $this->assertTrue($artisan->fresh()->enabled);
        $this->assertTrue($assets->fresh()->enabled);

        $page->call('toggleBlock', $artisan->id);
        $this->assertFalse($artisan->fresh()->enabled);

        // edit, add, delete, reorder
        $page->call('startEdit', $artisan->id)->set('bodyDraft', '- **Artisan**: in the container, always.')->set('titleDraft', '')->call('saveEdit');
        $this->assertSame('Artisan', $artisan->fresh()->title);
        $this->assertStringContainsString('always.', $artisan->fresh()->body);

        $page->call('addBlock', $how->id)->set('bodyDraft', 'New rule')->call('saveEdit');
        $this->assertSame(5, $how->blocks()->count());
        $new = ContextBlock::where('group_id', $how->id)->latest('id')->first();
        $page->call('reorderBlocks', $how->id, [$new->id, $artisan->id, $assets->id]);
        $this->assertSame(1, $new->fresh()->order);
        $page->call('deleteBlock', $new->id);
        $this->assertSame(4, $how->blocks()->count());

        $page->set('newGroup', 'Extra')->call('addGroup');
        $this->assertSame(4, ContextGroup::count());
        $extra = ContextGroup::where('title', 'Extra')->first();
        $page->call('startRenameGroup', $extra->id)->set('groupDraft', 'Extra rules')->call('saveGroup');
        $this->assertSame('Extra rules', $extra->fresh()->title);
        $page->call('deleteGroup', $extra->id);
        $this->assertSame(3, ContextGroup::count());

        $this->get('/context')->assertOk()->assertSee('Agent context');
    }
}
