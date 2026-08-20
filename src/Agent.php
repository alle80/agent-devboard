<?php

namespace Alle80\Griglia;

use Alle80\Griglia\Models\Checklist;
use Alle80\Griglia\Models\Todo;

/**
 * The coding agents driven by the board — any CLI agent works (Claude Code, Codex CLI, Gemini CLI, …): the
 * board only talks through `griglia:check`/`griglia:watch`, AGENTS.md and the generated context file.
 *
 * Several agents can be active at once: config `griglia.agents` (key => label, e.g. claude => Claude Code) lists
 * them; a list (project) has a default agent (`checklists.agent`) and a task may override it (`todos.agent`);
 * each agent runs `griglia:check --agent=<key>` (or GRIGLIA_AGENT_KEY) and sees only its tasks.
 * `agent_name` stays the generic label of "the agent" in the UI when there is only one.
 */
class Agent
{
    public static function name(): string
    {
        return (string) (config('griglia.agent_name') ?: 'Agent');
    }

    /** key => label of the configured agents (at least one: the default one). */
    public static function all(): array
    {
        $raw = config('griglia.agents');
        $out = [];
        if (is_string($raw) && trim($raw) !== '') {
            foreach (explode(',', $raw) as $pair) {
                [$k, $l] = array_pad(array_map('trim', explode(':', $pair, 2)), 2, null);
                if ($k !== '') $out[$k] = $l ?: $k;
            }
        } elseif (is_array($raw)) {
            foreach ($raw as $k => $l) {
                if (is_int($k)) { $out[(string) $l] = (string) $l; } else { $out[(string) $k] = (string) $l; }
            }
        }
        if ($out === []) {
            $out[self::defaultKey()] = self::name();
        }

        return $out;
    }

    /** Key of the default agent (config griglia.agent_key, else the first configured, else 'agent'). */
    public static function defaultKey(): string
    {
        $key = (string) (config('griglia.agent_key') ?: '');
        if ($key !== '') {
            return $key;
        }
        $raw = config('griglia.agents');
        if (is_string($raw) && trim($raw) !== '') {
            return trim(explode(':', explode(',', $raw)[0], 2)[0]);
        }
        if (is_array($raw) && $raw !== []) {
            $k = array_key_first($raw);

            return is_int($k) ? (string) $raw[$k] : (string) $k;
        }

        return 'agent';
    }

    public static function many(): bool
    {
        return count(self::all()) > 1;
    }

    public static function label(?string $key): string
    {
        return self::all()[$key] ?? ($key ?: self::name());
    }

    /** Effective agent key of a todo: its own, else its list's, else the default. */
    public static function effective(Todo $todo, ?Checklist $list = null): string
    {
        $list ??= $todo->checklist;
        $known = self::all();

        // A key that is not configured any more (agent removed from GRIGLIA_AGENTS) would belong to nobody:
        // the task would be invisible to every agent, waiting forever. It falls back instead (task 347).
        foreach ([$todo->agent, $list?->agent] as $key) {
            if ($key && isset($known[$key])) {
                return (string) $key;
            }
        }

        return self::defaultKey();
    }
}
