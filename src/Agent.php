<?php

namespace Alle80\Devboard;

/**
 * The coding agent driven by the board — any CLI agent works (Claude Code, Codex CLI, Gemini CLI, …):
 * the board only talks through `devboard:check`/`devboard:watch`, AGENTS.md and the generated context file.
 * `agent_name` is how the UI calls it (config devboard.agent_name, default «Agent»).
 */
class Agent
{
    public static function name(): string
    {
        return (string) (config('devboard.agent_name') ?: 'Agent');
    }
}
