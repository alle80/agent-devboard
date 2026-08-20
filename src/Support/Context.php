<?php

namespace Alle80\Griglia\Support;

use Alle80\Griglia\Models\ContextBlock;
use Alle80\Griglia\Models\ContextGroup;
use Illuminate\Support\Facades\DB;

/**
 * The agent's context (its instructions file, e.g. CLAUDE.md) as switchable groups and blocks.
 * import(): markdown → groups (`## ` headings; the text before the first one goes in a first group named
 * after the `# ` title) and blocks (top-level bullets, paragraphs, `###` sub-sections, fenced code kept
 * whole). export(): the enabled blocks of the enabled groups back to markdown, ready to be written to the
 * file (scripts on the host do that: the board itself cannot see the repo).
 */
class Context
{
    /** Parse markdown into [['title' => …, 'blocks' => [['title' => ?, 'body' => …], …]], …]. */
    public static function parse(string $markdown): array
    {
        $lines = preg_split('/\r?\n/', rtrim($markdown));
        $groups = [];
        $current = null;
        $buffer = [];
        $inFence = false;
        $flush = function () use (&$current, &$buffer) {
            $text = trim(implode("\n", $buffer));
            $buffer = [];
            if ($text === '' || $current === null) {
                return;
            }
            $current['blocks'][] = ['title' => self::titleOf($text), 'body' => $text];
        };
        foreach ($lines as $line) {
            if (preg_match('/^```/', $line)) {
                $inFence = ! $inFence;
                $buffer[] = $line;
                continue;
            }
            if ($inFence) {
                $buffer[] = $line;
                continue;
            }
            if (preg_match('/^#\s+(.+)$/', $line, $m) && $current === null) {
                $current = ['title' => trim($m[1]), 'blocks' => []];
                continue;
            }
            if (preg_match('/^##\s+(.+)$/', $line, $m)) {
                $flush();
                if ($current !== null) {
                    $groups[] = $current;
                }
                $current = ['title' => trim($m[1]), 'blocks' => []];
                continue;
            }
            if ($current === null) {
                $current = ['title' => 'Intro', 'blocks' => []];
            }
            // New block on: blank line, top-level bullet, ### sub-heading
            if (trim($line) === '') {
                // a lone ### heading waits for its paragraph (keep the blank line between them)
                if (count($buffer) === 1 && preg_match('/^###\s/', $buffer[0])) {
                    $buffer[] = '';
                } else {
                    $flush();
                }
                continue;
            }
            // New block also on a top-level bullet, a ### sub-heading, or a «**Bold lead**» line following plain text
            if ($buffer && ! preg_match('/^###\s/', end($buffer))
                && (preg_match('/^(- |\* |\d+\. |###\s)/', $line) || (preg_match('/^\*\*[^*]+\*\*/', $line) && ! preg_match('/^(\s+|- |\* |\d+\. )/', end($buffer))))) {
                $flush();
            }
            $buffer[] = $line;
        }
        $flush();
        if ($current !== null) {
            $groups[] = $current;
        }

        return $groups;
    }

    /** Short title for a block: bold lead («**Foo**: …»), ### heading, or the first words. */
    public static function titleOf(string $text): ?string
    {
        $first = trim((string) strtok($text, "\n"));
        if (preg_match('/^###\s+(.+)$/', $first, $m)) {
            return trim($m[1]);
        }
        if (preg_match('/^(?:[-*]\s+|\d+\.\s+)?\*\*([^*]{1,80})\*\*/', $first, $m)) {
            return trim($m[1], " :");
        }
        $plain = trim(preg_replace('/^(?:[-*]\s+|\d+\.\s+)/', '', $first));
        $plain = preg_replace('/[`*_]/', '', $plain);

        return $plain === '' ? null : mb_strimwidth($plain, 0, 70, '…');
    }

    /** Store parsed groups. $replace = wipe the current context first. Returns [groups, blocks] counts. */
    public static function import(string $markdown, bool $replace = false): array
    {
        $parsed = self::parse($markdown);
        DB::transaction(function () use ($parsed, $replace, &$g, &$b) {
            if ($replace) {
                ContextBlock::query()->delete();
                ContextGroup::query()->delete();
            }
            $g = $b = 0;
            $order = (int) ContextGroup::max('order');
            foreach ($parsed as $group) {
                $model = ContextGroup::create(['title' => $group['title'], 'order' => ++$order, 'enabled' => true]);
                $g++;
                foreach ($group['blocks'] as $i => $block) {
                    $model->blocks()->create(['title' => $block['title'], 'body' => $block['body'], 'order' => $i + 1, 'enabled' => true]);
                    $b++;
                }
            }
        });

        return [$g, $b];
    }

    /** Markdown of the enabled blocks of the enabled groups (or everything with $all). */
    public static function export(bool $all = false): string
    {
        $out = [];
        $groups = ContextGroup::with('blocks')->orderBy('order')->orderBy('id')->get();
        foreach ($groups as $i => $group) {
            if (! $all && ! $group->enabled) {
                continue;
            }
            $blocks = $group->blocks->filter(fn ($b) => $all || $b->enabled);
            if ($i === 0 && ! $group->title) {
                // no heading
            }
            $out[] = ($i === 0 ? '# ' : '## ').$group->title;
            $out[] = '';
            $prevBullet = false;
            foreach ($blocks as $b) {
                $bullet = (bool) preg_match('/^(- |\* |\d+\. )/', $b->body);
                if ($bullet && $prevBullet && end($out) === '') {
                    array_pop($out); // consecutive bullets stay a tight list
                }
                $out[] = $b->body;
                $out[] = '';
                $prevBullet = $bullet;
            }
        }

        return rtrim(implode("\n", $out))."\n";
    }

    /** Estimated tokens: [enabled, total]. */
    public static function tokens(): array
    {
        $enabled = $total = 0;
        foreach (ContextGroup::with('blocks')->get() as $g) {
            foreach ($g->blocks as $b) {
                $total += $b->tokens();
                if ($g->enabled && $b->enabled) {
                    $enabled += $b->tokens();
                }
            }
        }

        return [$enabled, $total];
    }
}
