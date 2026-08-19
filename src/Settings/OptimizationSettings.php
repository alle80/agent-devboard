<?php

namespace Alle80\Devboard\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * «Optimization» group: switches that make the agent spend fewer tokens.
 * Most of an agent session's cost is the context re-read at every turn, so the levers are: fewer turns
 * (batching, no re-listing after actions), smaller command outputs, and less prose in the chat.
 * `devboard:check` reads them: it trims its own output and prints the terse-mode instructions
 * the agent must follow. Edited from /settings.
 */
class OptimizationSettings extends Settings
{
    /** Action calls (--take/--done/--ask/--progress) print only the result line: no settings, no listing. */
    public bool $compact_check;

    /** Terse mode: the agent keeps chat prose to a minimum (batched commands, no recaps). Human readability of the chat is sacrificed. */
    public bool $terse_agent;

    /** Max characters of previous context (🤖 comment / note of a resumed item, agent comments) shown by devboard:check; 0 = unlimited. */
    public int $context_max_chars;

    /** The agent updates the progress % only piggybacked on other commands (never a turn just for it). */
    public bool $progress_piggyback;

    /** The agent reports the tokens spent when closing a task (costs one extra command per task). */
    public bool $token_report;

    public static function group(): string
    {
        return 'optimization';
    }

    /**
     * Fields for the settings page and devboard:check.
     * key => [label, help, type (bool|int), options]
     */
    public static function fields(): array
    {
        $types = [
            'compact_check' => ['bool', []],
            'terse_agent' => ['bool', []],
            'context_max_chars' => ['int', ['min' => 0, 'max' => 5000]],
            'progress_piggyback' => ['bool', []],
            'token_report' => ['bool', []],
        ];
        $labels = (array) __('devboard::t.settings_fields');
        $out = [];
        foreach ($types as $key => [$type, $opts]) {
            [$label, $help] = $labels[$key] ?? [$key, ''];
            $out[$key] = [$label, $help, $type, $opts];
        }

        return $out;
    }

    /** Compact one-liner for devboard:check. */
    public function summary(): string
    {
        $out = [];
        foreach (self::fields() as $key => $f) {
            $v = $this->{$key};
            $out[] = $f[0].': '.match ($f[2]) {
                'bool' => $v ? __('devboard::t.yes') : __('devboard::t.no'),
                default => (string) $v,
            };
        }

        return implode(' · ', $out);
    }

    /** Extra rules printed to the agent when terse mode is on. */
    public function terseRules(): string
    {
        return 'TERSE MODE ON (token saving): no narration or recaps in the chat, one-line status at most; batch shell commands into a single call; do not re-run devboard:check after --take/--done/--ask (the result line is enough); read only the file parts you need (offset/limit, grep, head) and never re-read what you already saw; short commit messages; 🤖 comment ≤ 8 lines unless the setting says detailed; no screenshots unless «verify before closing» is on.';
    }

    /** Cut a text to context_max_chars (0 = unlimited). */
    public function trim(?string $text): ?string
    {
        if ($text === null || $this->context_max_chars <= 0 || mb_strlen($text) <= $this->context_max_chars) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $this->context_max_chars)).' […]';
    }
}
