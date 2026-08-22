<?php

namespace Alle80\Griglia\Tests\Feature;

use Alle80\Griglia\Support\Markdown;
use Alle80\Griglia\Tests\TestCase;

class MarkdownTest extends TestCase
{
    public function test_renders_formatting(): void
    {
        $html = Markdown::render("**b** and `c`\n\n- one\n- two");
        $this->assertStringContainsString('<strong>b</strong>', $html);
        $this->assertStringContainsString('<code>c</code>', $html);
        $this->assertStringContainsString('<ul>', $html);
    }

    public function test_renders_single_newlines_as_break_tags(): void
    {
        $this->assertStringContainsString("first<br>\nsecond", Markdown::render("first\nsecond"));
    }

    public function test_agent_response_normalizes_legacy_escaped_newlines(): void
    {
        $this->assertStringContainsString("first<br>\nsecond", Markdown::renderAgentResponse('first\\nsecond'));
        $this->assertSame("first\nsecond", Markdown::normalizeAgentResponse('first\\r\\nsecond'));
    }

    public function test_renders_tables(): void
    {
        $this->assertStringContainsString('<table>', Markdown::render("| a | b |\n| --- | --- |\n| 1 | 2 |"));
    }

    public function test_strips_raw_html_and_unsafe_links(): void
    {
        $html = Markdown::render('<script>alert(1)</script><img src=x onerror=alert(1)>');
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('onerror', $html);

        $this->assertStringNotContainsString('javascript:', Markdown::render('[x](javascript:alert(1))'));
    }

    public function test_inline_drops_wrapping_paragraph(): void
    {
        $this->assertSame('<strong>hi</strong>', Markdown::inline('**hi**'));
    }

    public function test_empty_is_empty(): void
    {
        $this->assertSame('', Markdown::render('   '));
        $this->assertSame('', Markdown::inline(null));
    }
}
