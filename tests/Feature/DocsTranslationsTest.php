<?php

namespace Alle80\Griglia\Tests\Feature;

use Alle80\Griglia\Tests\TestCase;

/**
 * The documentation site is bilingual (English base + Italian, mkdocs-static-i18n «suffix» structure):
 * these tests are what CI has instead of a human noticing that a page was translated and another was not.
 */
class DocsTranslationsTest extends TestCase
{
    protected function docs(): string
    {
        return realpath(__DIR__.'/../../docs');
    }

    /** @return list<string> every English page, relative to docs/ */
    protected function englishPages(): array
    {
        $pages = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->docs()));
        foreach ($iterator as $file) {
            $path = $file->getPathname();
            if (str_ends_with($path, '.md') && ! preg_match('/\.[a-z]{2}\.md$/', $path)) {
                $pages[] = substr($path, strlen($this->docs()) + 1);
            }
        }
        sort($pages);

        return $pages;
    }

    public function test_every_english_page_has_an_italian_one(): void
    {
        $missing = [];
        foreach ($this->englishPages() as $page) {
            if (! is_file($this->docs().'/'.substr($page, 0, -3).'.it.md')) {
                $missing[] = $page;
            }
        }

        $this->assertSame([], $missing, 'pages with no Italian translation: '.implode(', ', $missing));
        $this->assertNotEmpty($this->englishPages());
    }

    public function test_the_italian_pages_are_not_a_copy_of_the_english_ones(): void
    {
        $copies = [];
        foreach ($this->englishPages() as $page) {
            $it = $this->docs().'/'.substr($page, 0, -3).'.it.md';
            if (is_file($it) && file_get_contents($it) === file_get_contents($this->docs().'/'.$page)) {
                $copies[] = $page;
            }
        }

        $this->assertSame([], $copies, 'untranslated copies: '.implode(', ', $copies));
    }

    public function test_the_site_declares_both_languages(): void
    {
        $root = realpath(__DIR__.'/../..');
        $mkdocs = file_get_contents($root.'/mkdocs.yml');

        $this->assertStringContainsString('docs_structure: suffix', $mkdocs);
        $this->assertStringContainsString('locale: it', $mkdocs);
        $this->assertStringContainsString('fallback_to_default: true', $mkdocs);
        $this->assertStringContainsString(
            'mkdocs-static-i18n',
            file_get_contents($root.'/requirements-docs.txt'),
            'the plugin must be part of the documented toolchain'
        );
    }
}
