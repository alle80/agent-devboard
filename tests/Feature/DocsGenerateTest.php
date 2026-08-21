<?php

namespace Alle80\Griglia\Tests\Feature;

use Alle80\Griglia\Tests\TestCase;
use Illuminate\Support\Facades\File;

/** `griglia:docs-generate` writes the reference pages of the site from the code itself. */
class DocsGenerateTest extends TestCase
{
    protected string $out;

    protected function setUp(): void
    {
        parent::setUp();
        $this->out = sys_get_temp_dir().'/griglia-docs-'.uniqid();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->out);
        parent::tearDown();
    }

    public function test_it_generates_the_three_reference_pages(): void
    {
        $this->artisan('griglia:docs-generate', ['--out' => $this->out])->assertSuccessful();

        $commands = file_get_contents($this->out.'/commands.md');
        $this->assertStringContainsString('## `griglia:check`', $commands);
        $this->assertStringContainsString('--take', $commands);
        $this->assertStringContainsString('do not edit by hand', $commands);

        $config = file_get_contents($this->out.'/config.md');
        $this->assertStringContainsString('| `agent_list` |', $config);
        $this->assertStringContainsString('`GRIGLIA_MODE`', $config);
        $this->assertStringNotContainsString("env('", $config, 'defaults are readable, not raw php');

        $settings = file_get_contents($this->out.'/settings.md');
        foreach (['commit_after_task', 'terse_agent', 'default_style'] as $key) {
            $this->assertStringContainsString("(`$key`)", $settings);
        }
    }

    /** Task 455: a comment describes the key right below it, so no two keys share a description. */
    public function test_every_config_key_has_a_description_of_its_own(): void
    {
        $this->artisan('griglia:docs-generate', ['--out' => $this->out])->assertSuccessful();

        $seen = [];
        foreach (file($this->out.'/config.md') as $line) {
            if (! preg_match('/^\| `([a-z0-9_]+)` \| .* \| .* \| (.*) \|$/', trim($line), $m)) {
                continue;
            }
            [, $key, $description] = $m;
            $this->assertNotSame('', trim($description), "config key `$key` has no comment of its own");
            $this->assertArrayNotHasKey(
                $description,
                $seen,
                "config keys `$key` and `".($seen[$description] ?? '')."` share the same description"
            );
            $seen[$description] = $key;
        }

        $this->assertArrayHasKey('agents', array_flip($seen), 'the table was actually parsed');
    }

    public function test_it_generates_the_italian_pages_too(): void
    {
        $this->artisan('griglia:docs-generate', ['--out' => $this->out])->assertSuccessful();

        $commands = file_get_contents($this->out.'/commands.it.md');
        $this->assertStringContainsString('# Comandi artisan', $commands);
        $this->assertStringContainsString('## `griglia:check`', $commands, 'command names are not translated');
        $this->assertStringContainsString('| Argomento / opzione | Cosa fa | Default |', $commands);
        $this->assertStringContainsString('non modificare a mano', $commands);

        $config = file_get_contents($this->out.'/config.it.md');
        $this->assertStringContainsString('# File di configurazione', $config);
        $this->assertStringContainsString('| `agent_list` |', $config);

        // The settings page is translated by the lang files of the /settings page itself.
        $settings = file_get_contents($this->out.'/settings.it.md');
        $this->assertStringContainsString('# Impostazioni', $settings);
        $this->assertStringContainsString('(`commit_after_task`)', $settings);
        $this->assertStringNotContainsString('| Setting | Type |', $settings);
    }

    public function test_the_pages_do_not_change_with_the_locale_of_whoever_runs_it(): void
    {
        app()->setLocale('it');
        $this->artisan('griglia:docs-generate', ['--out' => $this->out])->assertSuccessful();
        $english = file_get_contents($this->out.'/settings.md');

        app()->setLocale('en');
        $this->artisan('griglia:docs-generate', ['--out' => $this->out])->assertSuccessful();

        $this->assertSame($english, file_get_contents($this->out.'/settings.md'));
    }

    public function test_every_string_coming_from_the_code_is_translated(): void
    {
        $this->artisan('griglia:docs-generate', ['--out' => $this->out])
            ->doesntExpectOutputToContain('with no `it` translation')
            ->assertSuccessful();
    }

    public function test_check_reports_stale_pages(): void
    {
        $this->artisan('griglia:docs-generate', ['--out' => $this->out])->assertSuccessful();
        $this->artisan('griglia:docs-generate', ['--out' => $this->out, '--check' => true])->assertSuccessful();

        file_put_contents($this->out.'/commands.md', "# stale\n");
        $this->artisan('griglia:docs-generate', ['--out' => $this->out, '--check' => true])->assertFailed();
    }

    public function test_the_pages_committed_in_the_repo_are_up_to_date(): void
    {
        $this->artisan('griglia:docs-generate', ['--check' => true])->assertSuccessful();
    }
}
