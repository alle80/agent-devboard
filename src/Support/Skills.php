<?php

namespace Alle80\Griglia\Support;

/**
 * Catalogue of the skills the coding agent has available (Claude Code skills/plugins), imported with
 * `griglia:skills-import` (JSON list of {name, description, source}) into a JSON file. The modal shows
 * them as an accordion of checkboxes under the Task note; the chosen ones are saved in `todos.skills`
 * and printed by `griglia:check` so the agent invokes them for that task.
 */
class Skills
{
    public static function path(): string
    {
        return (string) (config('griglia.skills_file') ?: storage_path('app/griglia/skills.json'));
    }

    /** name => ['name' => …, 'description' => …, 'source' => …], sorted by name. */
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

        return [
            'name' => trim((string) $s['name']),
            'description' => trim((string) ($s['description'] ?? '')),
            'source' => trim((string) ($s['source'] ?? '')),
        ];
    }
}
