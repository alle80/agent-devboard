<?php

namespace Alle80\Griglia\Tests\Feature;

use Alle80\Griglia\Tests\TestCase;
use Illuminate\Support\Facades\Blade;

class AssetsTest extends TestCase
{
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
    }
}
