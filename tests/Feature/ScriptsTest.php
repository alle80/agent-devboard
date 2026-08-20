<?php

namespace Alle80\Griglia\Tests\Feature;

use Alle80\Griglia\GrigliaServiceProvider;
use Alle80\Griglia\Tests\TestCase;
use Illuminate\Support\ServiceProvider;

/**
 * The host-side helpers (scripts/) ship with the package: they run on the machine where the agent runs
 * — outside the container — and fill the board (skills, context, tokens, agent status). Task 376.
 */
class ScriptsTest extends TestCase
{
    public function test_scripts_are_shipped_and_publishable(): void
    {
        $dir = dirname(__DIR__, 2).'/scripts';
        foreach (['sync-skills.py', 'sync-context.py', 'claude-tokens.py', 'agent-status.py', 'builtin-skills.json', 'griglia-agent-worker.py', 'systemd/griglia-agent-worker@.service.example'] as $file) {
            $this->assertFileExists($dir.'/'.$file);
        }
        foreach (['sync-skills.py', 'sync-context.py', 'claude-tokens.py', 'agent-status.py', 'griglia-agent-worker.py'] as $script) {
            $this->assertTrue(is_executable($dir.'/'.$script), "$script must be executable");
            $this->assertStringStartsWith('#!/usr/bin/env python3', (string) file_get_contents($dir.'/'.$script));
        }

        // The ones reading/writing project files must find its root even when run from vendor/alle80/griglia/scripts
        foreach (['sync-skills.py', 'sync-context.py', 'claude-tokens.py'] as $script) {
            $this->assertStringContainsString('def project_root()', (string) file_get_contents($dir.'/'.$script));
        }

        $worker = (string) file_get_contents($dir.'/griglia-agent-worker.py');
        $this->assertStringContainsString('choices=("codex", "claude", "custom")', $worker);
        $unit = (string) file_get_contents($dir.'/systemd/griglia-agent-worker@.service.example');
        $this->assertStringContainsString('%h/.local/bin', $unit);
        $this->assertStringContainsString('/absolute/path/to/project', $unit);

        $paths = ServiceProvider::pathsToPublish(GrigliaServiceProvider::class, 'griglia-scripts');
        $this->assertCount(1, $paths);
        $this->assertSame(realpath($dir), realpath((string) array_key_first($paths)));
        $this->assertSame(base_path('scripts'), reset($paths));
    }
}
