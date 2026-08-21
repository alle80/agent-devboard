<?php

namespace Alle80\Griglia\Tests\Feature;

use Alle80\Griglia\Tests\TestCase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Lang;

class AssetsTest extends TestCase
{
    public function test_the_default_mode_is_precompiled(): void
    {
        // Installing the package must not require a build step in the host app.
        $this->assertSame('precompiled', config('griglia.assets'));
    }

    public function test_precompiled_mode_links_the_published_build(): void
    {
        config(['griglia.assets' => 'precompiled', 'griglia.echo' => ['key' => 'abc', 'host' => 'example.test', 'port' => 443, 'scheme' => 'https']]);
        $html = Blade::render('<x-griglia::assets />');

        $this->assertStringContainsString('/vendor/griglia/build/griglia.css', $html);
        $this->assertStringContainsString('/vendor/griglia/build/griglia.js', $html);
        $this->assertStringContainsString('window.GRIGLIA_ECHO = {"key":"abc"', $html);
    }

    public function test_vite_mode_uses_the_host_bundle_and_no_echo_without_key(): void
    {
        config(['griglia.assets' => 'vite', 'griglia.echo' => ['key' => '']]);
        $html = Blade::render('<x-griglia::assets />');

        $this->assertStringNotContainsString('GRIGLIA_ECHO', $html);
        $this->assertStringNotContainsString('/vendor/griglia/build', $html);
    }

    public function test_the_standalone_build_is_shipped(): void
    {
        $this->assertFileExists(__DIR__.'/../../public/build/griglia.css');
        $this->assertFileExists(__DIR__.'/../../public/build/griglia.js');
        $css = file_get_contents(__DIR__.'/../../public/build/griglia.css');
        $this->assertStringContainsString('.tl-card', $css);
        $this->assertStringContainsString('.setting-switch', $css);
        $this->assertStringContainsString('.tl-done>.todo-action', $css);
        $this->assertStringContainsString('var(--tl-done-action,#e5e7eb)', $css);
    }

    public function test_the_labels_of_the_copy_button_reach_the_browser(): void
    {
        // The copy button on code blocks is drawn by JS: its labels come from the translations (task 367).
        $this->actingAsUser();
        $this->get('/')->assertOk()->assertSee('GRIGLIA_I18N', false)->assertSee('"copied"', false);
    }

    public function test_inline_runtime_objects_are_encoded_for_a_script_context(): void
    {
        $payload = '</script>"'."\u{2028}\u{2029}";
        config([
            'griglia.assets' => 'precompiled',
            'griglia.echo' => ['key' => 'abc', 'host' => $payload],
            'webpush.vapid.public_key' => $payload,
        ]);
        app()->setLocale('x-test');
        Lang::addLines([
            't.copy' => $payload,
            't.mic_busy' => $payload,
        ], 'x-test', 'griglia');
        $this->actingAsUser();

        $html = Blade::render('<x-griglia::assets />');

        $this->assertStringNotContainsString($payload, $html);
        $this->assertStringContainsString('\\u003C\\/script\\u003E\\u0022\\u2028\\u2029', $html);
        $this->assertSame(4, preg_match_all(
            '/window\.(GRIGLIA_(?:ECHO|I18N|SPEECH|PUSH)) = (\{.*?\});<\\/script>/',
            $html,
            $objects,
        ));

        $decoded = array_combine($objects[1], array_map(
            static fn (string $json): array => json_decode($json, true, flags: JSON_THROW_ON_ERROR),
            $objects[2],
        ));
        $this->assertSame($payload, $decoded['GRIGLIA_ECHO']['host']);
        $this->assertSame($payload, $decoded['GRIGLIA_I18N']['copy']);
        $this->assertSame($payload, $decoded['GRIGLIA_SPEECH']['busy']);
        $this->assertSame($payload, $decoded['GRIGLIA_PUSH']['key']);
    }
}
