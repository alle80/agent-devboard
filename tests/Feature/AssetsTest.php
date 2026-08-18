<?php

namespace Alle80\Devboard\Tests\Feature;

use Alle80\Devboard\Tests\TestCase;
use Illuminate\Support\Facades\Blade;

class AssetsTest extends TestCase
{
    public function test_precompiled_mode_links_the_published_build(): void
    {
        config(['devboard.assets' => 'precompiled', 'devboard.echo' => ['key' => 'abc', 'host' => 'example.test', 'port' => 443, 'scheme' => 'https']]);
        $html = Blade::render('<x-devboard::assets />');

        $this->assertStringContainsString('/vendor/devboard/build/devboard.css', $html);
        $this->assertStringContainsString('/vendor/devboard/build/devboard.js', $html);
        $this->assertStringContainsString('window.DEVBOARD_ECHO = {"key":"abc"', $html);
    }

    public function test_vite_mode_uses_the_host_bundle_and_no_echo_without_key(): void
    {
        config(['devboard.assets' => 'vite', 'devboard.echo' => ['key' => '']]);
        $html = Blade::render('<x-devboard::assets />');

        $this->assertStringNotContainsString('DEVBOARD_ECHO', $html);
        $this->assertStringNotContainsString('/vendor/devboard/build', $html);
    }

    public function test_the_standalone_build_is_shipped(): void
    {
        $this->assertFileExists(__DIR__.'/../../public/build/devboard.css');
        $this->assertFileExists(__DIR__.'/../../public/build/devboard.js');
        $css = file_get_contents(__DIR__.'/../../public/build/devboard.css');
        $this->assertStringContainsString('.tl-card', $css);
        $this->assertStringContainsString('.setting-switch', $css);
    }
}
