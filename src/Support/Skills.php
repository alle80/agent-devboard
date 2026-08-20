<?php

namespace Alle80\Griglia\Support;

/**
 * Catalogue of the skills the coding agents have available (Claude Code, Codex CLI, … skills/plugins),
 * imported with `griglia:skills-import` (JSON list of {name, description, source, agents}) into a JSON
 * file. The modal shows them as an accordion of checkboxes under the Task note; the chosen ones are
 * saved in `todos.skills` and printed by `griglia:check` so the agent invokes them for that task.
 *
 * The SKILL.md format is portable, but a skill only exists for the agent that finds it on disk: each
 * entry carries `agents` (keys of the configured agents that can use it, an empty list = everybody, as
 * for the catalogues imported before this field existed), and the modal only offers the ones that the
 * agent of the task can really invoke (task 375).
 */
class Skills
{
    public static function path(): string
    {
        return (string) (config('griglia.skills_file') ?: storage_path('app/griglia/skills.json'));
    }

    /** name => ['name' => …, 'description' => …, 'source' => …, 'agents' => [...]], sorted by name. */
    public static function all(): array
    {
        $path = self::path();
        if (! is_file($path)) {
            return [];
        }
        $list = json_decode((string) file_get_contents($path), true);
        if (! is_array($list)) {
            return [];
        }
        $out = [];
        foreach ($list as $s) {
            $item = self::normalize($s);
            if ($item) {
                $out[$item['name']] = $item;
            }
        }
        ksort($out, SORT_NATURAL | SORT_FLAG_CASE);

        return $out;
    }

    /**
     * Only the skills the agent $key can use: the ones tagged with it plus the untagged ones (available
     * to everybody). An empty $key (no agent known) returns the whole catalogue.
     */
    public static function forAgent(?string $key): array
    {
        $key = trim((string) $key);
        if ($key === '') {
            return self::all();
        }

        return array_filter(self::all(), fn (array $s) => $s['agents'] === [] || in_array($key, $s['agents'], true));
    }

    /** Replace the catalogue. Returns the number of skills saved. */
    public static function import(array $list): int
    {
        $out = [];
        foreach ($list as $s) {
            $item = self::normalize($s);
            if ($item) {
                $out[$item['name']] = $item;
            }
        }
        ksort($out, SORT_NATURAL | SORT_FLAG_CASE);
        $path = self::path();
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }
        file_put_contents($path, json_encode(array_values($out), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return count($out);
    }

    private static function normalize(mixed $s): ?array
    {
        if (is_string($s)) {
            $s = ['name' => $s];
        }
        if (! is_array($s) || ! isset($s['name']) || trim((string) $s['name']) === '') {
            return null;
        }

        $agents = $s['agents'] ?? [];
        if (is_string($agents)) {
            $agents = preg_split('/[\s,]+/', $agents, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }
        $agents = array_values(array_unique(array_filter(array_map(fn ($a) => trim((string) $a), (array) $agents), fn ($a) => $a !== '')));
        sort($agents);

        return [
            'name' => trim((string) $s['name']),
            'description' => trim((string) ($s['description'] ?? '')),
            'source' => trim((string) ($s['source'] ?? '')),
            'agents' => $agents,
        ];
    }
}
