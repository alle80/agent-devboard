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

        // No Docker required: every host script can reach Artisan through local PHP (task 389)
        foreach (['sync-skills.py', 'sync-context.py', 'claude-tokens.py', 'agent-status.py'] as $script) {
            $source = (string) file_get_contents($dir.'/'.$script);
            $this->assertStringContainsString("os.environ.get('GRIGLIA_TRANSPORT', 'docker')", $source, "$script must support the local transport");
            $this->assertStringContainsString("os.environ.get('GRIGLIA_PHP', 'php')", $source, "$script must honour GRIGLIA_PHP");
            $this->assertStringNotContainsString("'laravel-dev-app'", str_replace("os.environ.get('GRIGLIA_CONTAINER', 'laravel-dev-app')", '', $source), "$script must not hardcode the container name");
        }

        $worker = (string) file_get_contents($dir.'/griglia-agent-worker.py');
        $this->assertStringContainsString('choices=("codex", "claude", "custom")', $worker);
        $this->assertStringContainsString('GRIGLIA_WORKER_TRANSPORT', $worker);
        $this->assertStringContainsString('GRIGLIA_WORKER_PHP', $worker);
        $this->assertStringContainsString('["docker", "exec", args.container, "php", *artisan]', $worker);
        $this->assertStringContainsString('[args.php, *artisan]', $worker);
        $this->assertStringContainsString('hashlib.sha256(str(repo).encode()).hexdigest()[:12]', $worker);
        $this->assertStringContainsString('lock_path(args.repo, args.agent)', $worker);
        // Per-instance variables win, but the shared ones configure the whole machine at once
        foreach (['GRIGLIA_TRANSPORT', 'GRIGLIA_PHP', 'GRIGLIA_CONTAINER'] as $shared) {
            $this->assertStringContainsString('os.getenv("'.$shared.'"', $worker, "the worker must fall back to $shared");
        }
        $unit = (string) file_get_contents($dir.'/systemd/griglia-agent-worker@.service.example');
        $this->assertStringContainsString('%h/.local/bin', $unit);
        $this->assertStringContainsString('/absolute/path/to/project', $unit);
        $this->assertStringContainsString('%p-%i.env', $unit);

        $paths = ServiceProvider::pathsToPublish(GrigliaServiceProvider::class, 'griglia-scripts');
        $this->assertCount(1, $paths);
        $this->assertSame(realpath($dir), realpath((string) array_key_first($paths)));
        $this->assertSame(base_path('scripts'), reset($paths));
    }
}
