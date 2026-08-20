<?php

namespace Alle80\Griglia\Support;

use League\CommonMark\GithubFlavoredMarkdownConverter;

/**
 * Renders user Markdown to SAFE HTML: GitHub-flavoured (tables, code, lists, task lists, autolinks,
 * strikethrough) with raw HTML stripped and unsafe links blocked — so notes/sub-tasks can be formatted
 * without any XSS risk.
 */
class Markdown
{
    private static ?GithubFlavoredMarkdownConverter $converter = null;

    private static function converter(): GithubFlavoredMarkdownConverter
    {
        return self::$converter ??= new GithubFlavoredMarkdownConverter([
            'html_input' => 'strip',        // drop any raw HTML in the source
            'allow_unsafe_links' => false,  // no javascript: / data: links
            'max_nesting_level' => 20,
        ]);
    }

    /** Full block render (paragraphs, tables, code blocks, …). */
    public static function render(?string $text): string
    {
        $text = trim((string) $text);

        return $text === '' ? '' : self::converter()->convert($text)->getContent();
    }

    /** Inline render for short text (sub-task names): drops the single wrapping <p>. */
    public static function inline(?string $text): string
    {
        $html = trim(self::render($text));

        return preg_replace('#^<p>(.*)</p>$#s', '$1', $html) ?? $html;
    }
}
