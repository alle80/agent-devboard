<?php

namespace Alle80\Devboard\Console;

use Illuminate\Console\Command;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * `devboard:docs-build` — builds the package documentation (docs/ + mkdocs.yml, Material for MkDocs) into a
 * static HTML site. Uses the local `mkdocs` (or `python3 -m mkdocs`), or the official Docker image with --docker.
 */
class DocsBuild extends Command
{
    protected $signature = 'devboard:docs-build
        {--out= : Output directory (default: <package>/site)}
        {--serve : Run `mkdocs serve` (live preview) instead of building}
        {--docker : Use the squidfunk/mkdocs-material Docker image instead of a local mkdocs}
        {--strict : Pass --strict to mkdocs (warnings fail the build)}';

    protected $description = 'Builds the package documentation as a static HTML site with MkDocs (Material theme)';

    public function handle(): int
    {
        $root = realpath(__DIR__.'/../..');
        if (! is_file($root.'/mkdocs.yml')) {
            $this->error("mkdocs.yml not found in $root");

            return self::FAILURE;
        }
        $out = $this->option('out') ?: $root.'/site';
        $serve = (bool) $this->option('serve');

        if ($this->option('docker')) {
            $docker = (new ExecutableFinder)->find('docker');
            if (! $docker) {
                $this->error('docker not found (needed for --docker)');

                return self::FAILURE;
            }
            $uid = function_exists('posix_getuid') ? posix_getuid().':'.posix_getgid() : '1000:1000';
            $cmd = $serve
                ? [$docker, 'run', '--rm', '-it', '-p', '8000:8000', '-v', "$root:/docs", 'squidfunk/mkdocs-material', 'serve', '--dev-addr=0.0.0.0:8000']
                : [$docker, 'run', '--rm', '-u', $uid, '-v', "$root:/docs", 'squidfunk/mkdocs-material', 'build', '--site-dir', '/docs/'.$this->relative($root, $out)];
        } else {
            $mkdocs = (new ExecutableFinder)->find('mkdocs');
            if ($mkdocs) {
                $base = [$mkdocs];
            } elseif ($py = (new ExecutableFinder)->find('python3')) {
                $probe = new Process([$py, '-c', 'import mkdocs, material']);
                $probe->run();
                if (! $probe->isSuccessful()) {
                    return $this->missing();
                }
                $base = [$py, '-m', 'mkdocs'];
            } else {
                return $this->missing();
            }
            $cmd = $serve ? [...$base, 'serve'] : [...$base, 'build', '--site-dir', $out];
            if ($this->option('strict')) {
                $cmd[] = '--strict';
            }
        }

        $this->line(($serve ? 'Serving' : 'Building').' the documentation from '.$root.($serve ? '' : ' → '.$out));
        $process = new Process($cmd, $root, null, null, $serve ? null : 600);
        $process->setTty($serve && Process::isTtySupported());
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if (! $process->isSuccessful()) {
            $this->error('MkDocs failed (exit '.$process->getExitCode().').'.($process->getErrorOutput() ? ' '.trim($process->getErrorOutput()) : ''));

            return self::FAILURE;
        }
        if (! $serve) {
            $this->info('Site built in '.$out.' (open index.html).');
        }

        return self::SUCCESS;
    }

    private function missing(): int
    {
        $this->error('MkDocs is not installed. Install it with:  pip install mkdocs-material   (or run with --docker)');

        return self::FAILURE;
    }

    private function relative(string $root, string $out): string
    {
        $out = rtrim($out, '/');
        if (str_starts_with($out, $root.'/')) {
            return substr($out, strlen($root) + 1);
        }

        return 'site';
    }
}
