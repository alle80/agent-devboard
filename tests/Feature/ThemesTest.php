<?php

namespace Alle80\Devboard\Tests\Feature;

use Alle80\Devboard\Livewire\SettingsPage;
use Alle80\Devboard\Livewire\ThemedTodoList;
use Alle80\Devboard\Themes;
use Alle80\Devboard\ThemeStore;
use Alle80\Devboard\Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use ZipArchive;

class ThemesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        File::deleteDirectory(ThemeStore::root());
        $this->actingAsUser();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(ThemeStore::root());
        parent::tearDown();
    }

    protected function makePack(string $slug = 'ocean', array $extra = [], bool $folder = false): string
    {
        $file = storage_path("framework/testing/{$slug}.zip");
        File::ensureDirectoryExists(dirname($file));
        $zip = new ZipArchive;
        $zip->open($file, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $p = $folder ? "{$slug}/" : '';
        $zip->addFromString($p.'theme.json', json_encode(['slug' => $slug, 'label' => 'Ocean', 'icon' => '🌊', 'claim' => 'deep blue', 'version' => '1.0'] + $extra));
        $zip->addFromString($p.'theme.css', ".theme-{$slug} { --tl-bg: #012; --tl-fg: #cde; }");
        $zip->addFromString($p.'images/wave.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>');
        $zip->addFromString($p.'evil.php', '<?php echo 1;');
        $zip->close();

        return $file;
    }

    public function test_registry_builtin_config_and_runtime(): void
    {
        $this->assertArrayHasKey('slate', Themes::all());
        $this->assertSame('slate', Themes::default());

        config(['devboard.themes' => ['candy' => ['label' => 'Candy', 'icon' => '🍬']]]);
        Themes::registerTheme('night', ['label' => 'Night', 'icon' => '🌙']);
        Themes::registerStyle('manga', ['label' => 'Manga', 'icon' => '💥', 'route' => '/?stay=1']);

        $switcher = Themes::switcher();
        $this->assertSame('/?stay=1', $switcher['manga']['url']);
        $this->assertSame('/candy', $switcher['candy']['url']);
        $this->assertTrue(Themes::known('night'));
        $this->assertFalse(Themes::known('nope'));

        $this->assertSame('devboard::layouts.themed', Themes::settingsSkin('night')['layout']);
        Themes::registerSkin('manga', ['layout' => 'x']);
        $this->assertSame('x', Themes::settingsSkin('manga')['layout']);
    }

    public function test_install_uninstall_and_asset_route(): void
    {
        $def = ThemeStore::install($this->makePack());
        $this->assertSame('Ocean', $def['label']);
        $this->assertTrue(Themes::has('ocean'));
        $this->assertFileExists(ThemeStore::path('ocean', 'images/wave.svg'));
        $this->assertFileDoesNotExist(ThemeStore::path('ocean', 'evil.php'), 'unknown extensions are dropped');
        $this->assertStringContainsString('/devboard-themes/ocean/theme.css', Themes::get('ocean')['css_url']);

        $this->get('/devboard-themes/ocean/theme.css')->assertOk()->assertHeader('Content-Type', 'text/css; charset=UTF-8');
        $this->get('/devboard-themes/ocean/theme.json')->assertNotFound();
        $this->get('/devboard-themes/ocean/images/wave.svg')->assertOk();

        // The themed page works with the pack (css link + texts)
        $this->get('/ocean')->assertOk()->assertSee('deep blue')->assertSee('devboard-themes/ocean/theme.css', false);
        Livewire::test(ThemedTodoList::class, ['theme' => 'ocean'])->assertSee('deep blue');

        $this->assertTrue(ThemeStore::uninstall('ocean'));
        $this->assertFalse(Themes::has('ocean'));
        $this->get('/ocean')->assertNotFound();
    }

    public function test_pack_with_top_level_folder_and_reserved_slug(): void
    {
        ThemeStore::install($this->makePack('ocean', [], folder: true));
        $this->assertTrue(Themes::has('ocean'));

        $this->expectException(\RuntimeException::class);
        ThemeStore::install($this->makePack('slate'));
    }

    public function test_settings_page_upload_and_uninstall(): void
    {
        $file = UploadedFile::fake()->createWithContent('ocean.zip', file_get_contents($this->makePack()));
        Livewire::test(SettingsPage::class)->set('themeZip', $file)->assertDispatched('toast');
        $this->assertTrue(Themes::has('ocean'));

        Livewire::test(SettingsPage::class)->assertSee('Ocean')->call('uninstallTheme', 'ocean');
        $this->assertFalse(Themes::has('ocean'));

        $bad = UploadedFile::fake()->createWithContent('bad.zip', 'nope');
        Livewire::test(SettingsPage::class)->set('themeZip', $bad)->assertDispatched('toast');
    }

    public function test_export_and_import_roundtrip(): void
    {
        $out = storage_path('framework/testing/slate-export.zip');
        $css = storage_path('framework/testing/app.css');
        File::put($css, ".theme-slate { --tl-bg: #000; }\n.other { color: red; }\n.theme-slate .tl-card { border: 0; }\n");
        $this->artisan('devboard:theme-export', ['slug' => 'slate', '--out' => $out, '--css-from' => $css])->assertSuccessful();

        $zip = new ZipArchive;
        $zip->open($out);
        $this->assertStringContainsString('.theme-slate .tl-card', $zip->getFromName('theme.css'));
        $this->assertStringNotContainsString('.other', $zip->getFromName('theme.css'));
        $def = json_decode($zip->getFromName('theme.json'), true);
        $zip->close();
        $this->assertSame('slate', $def['slug']);

        // slate is built-in → import must be refused; a renamed copy installs fine
        $this->artisan('devboard:theme-import', ['zip' => $out])->assertFailed();
        $def['slug'] = 'slate-copy';
        $zip = new ZipArchive;
        $zip->open($out, ZipArchive::OVERWRITE);
        $zip->addFromString('theme.json', json_encode($def));
        $zip->close();
        $this->artisan('devboard:theme-import', ['zip' => $out])->assertSuccessful();
        $this->assertTrue(Themes::has('slate-copy'));
    }
}
