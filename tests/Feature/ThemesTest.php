<?php

namespace Alle80\Griglia\Tests\Feature;

use Alle80\Griglia\Livewire\SettingsPage;
use Alle80\Griglia\Livewire\ThemedTodoList;
use Alle80\Griglia\Themes;
use Alle80\Griglia\ThemeStore;
use Alle80\Griglia\Tests\TestCase;
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
        $zip->addFromString($p.'images/wave.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='));
        $zip->addFromString($p.'images/bad.svg', '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"/>');
        $zip->addFromString($p.'evil.php', '<?php echo 1;');
        $zip->close();

        return $file;
    }

    public function test_registry_builtin_config_and_runtime(): void
    {
        $this->assertArrayHasKey('slate', Themes::all());
        $this->assertSame('slate', Themes::default());

        config(['griglia.themes' => ['candy' => ['label' => 'Candy', 'icon' => '🍬']]]);
        Themes::registerTheme('night', ['label' => 'Night', 'icon' => '🌙']);
        Themes::registerStyle('manga', ['label' => 'Manga', 'icon' => '💥', 'route' => '/?stay=1']);

        $switcher = Themes::switcher();
        $this->assertSame('/?stay=1', $switcher['manga']['url']);
        $this->assertSame('/candy', $switcher['candy']['url']);
        $this->assertTrue(Themes::known('night'));
        $this->assertFalse(Themes::known('nope'));

        $this->assertSame('griglia::layouts.themed', Themes::settingsSkin('night')['layout']);
        Themes::registerSkin('manga', ['layout' => 'x']);
        $this->assertSame('x', Themes::settingsSkin('manga')['layout']);
    }

    public function test_install_uninstall_and_asset_route(): void
    {
        $def = ThemeStore::install($this->makePack());
        $this->assertSame('Ocean', $def['label']);
        $this->assertTrue(Themes::has('ocean'));
        $this->assertFileExists(ThemeStore::path('ocean', 'images/wave.png'));
        $this->assertFileDoesNotExist(ThemeStore::path('ocean', 'images/bad.svg'), 'svg is not accepted (scriptable)');
        $this->assertFileDoesNotExist(ThemeStore::path('ocean', 'evil.php'), 'unknown extensions are dropped');
        $this->assertStringContainsString('/griglia-themes/ocean/theme.css', Themes::get('ocean')['css_url']);

        $this->get('/griglia-themes/ocean/theme.css')->assertOk()->assertHeader('Content-Type', 'text/css; charset=UTF-8');
        $this->get('/griglia-themes/ocean/theme.json')->assertNotFound();
        $this->get('/griglia-themes/ocean/images/wave.png')->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'; img-src 'self' data:; font-src 'self'; sandbox");
        $this->get('/griglia-themes/ocean/images/bad.svg')->assertNotFound();

        // The themed page works with the pack (css link + texts)
        $this->get('/ocean')->assertOk()->assertSee('deep blue')->assertSee('griglia-themes/ocean/theme.css', false);
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
        $this->artisan('griglia:theme-export', ['slug' => 'slate', '--out' => $out, '--css-from' => $css])->assertSuccessful();

        $zip = new ZipArchive;
        $zip->open($out);
        $this->assertStringContainsString('.theme-slate .tl-card', $zip->getFromName('theme.css'));
        $this->assertStringNotContainsString('.other', $zip->getFromName('theme.css'));
        $def = json_decode($zip->getFromName('theme.json'), true);
        $zip->close();
        $this->assertSame('slate', $def['slug']);

        // slate is built-in → import must be refused; a renamed copy installs fine
        $this->artisan('griglia:theme-import', ['zip' => $out])->assertFailed();
        $def['slug'] = 'slate-copy';
        $zip = new ZipArchive;
        $zip->open($out, ZipArchive::OVERWRITE);
        $zip->addFromString('theme.json', json_encode($def));
        $zip->close();
        $this->artisan('griglia:theme-import', ['zip' => $out])->assertSuccessful();
        $this->assertTrue(Themes::has('slate-copy'));
    }

    public function test_css_is_sanitised_and_packs_have_limits(): void
    {
        $css = "@import url('https://evil.test/x.css');\n.a { background: url(https://evil.test/beacon.png); }\n.b { background: url('images/ok.png'); color: red; }\n.c { behavior: url(x.htc); width: expression(alert(1)); }\n.d { background: url(data:image/png;base64,AAAA); } .e { background: url(../../etc/passwd); }";
        $out = ThemeStore::sanitizeCss($css);
        $this->assertStringNotContainsString('@import url', $out);
        $this->assertStringNotContainsString('evil.test', $out);
        $this->assertStringContainsString("url('images/ok.png')", $out, 'relative urls are kept');
        $this->assertStringContainsString('url(data:image/png;base64,AAAA)', $out, 'inline images are kept');
        $this->assertStringNotContainsString('expression(', $out);
        $this->assertStringNotContainsString('behavior: url', $out);
        $this->assertStringNotContainsString('passwd', $out);

        // installed pack: the css on disk is the sanitised one, external icon_img dropped
        $file = $this->makePack('sky', ['icon_img' => 'https://evil.test/i.png']);
        $zip = new ZipArchive; $zip->open($file); $zip->addFromString('theme.css', "@import 'x'; .t{color:#fff}"); $zip->close();
        $def = ThemeStore::install($file);
        $this->assertNull($def['icon_img'] ?? null);
        $this->assertStringNotContainsString('@import', file_get_contents(ThemeStore::path('sky', 'theme.css')));

        // too many entries
        $big = storage_path('framework/testing/big.zip');
        $zip = new ZipArchive; $zip->open($big, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('theme.json', json_encode(['slug' => 'big', 'label' => 'Big']));
        for ($i = 0; $i < ThemeStore::MAX_ENTRIES + 1; $i++) { $zip->addFromString("images/f$i.txt", 'x'); }
        $zip->close();
        $this->expectException(\RuntimeException::class);
        ThemeStore::install($big);
    }
}
