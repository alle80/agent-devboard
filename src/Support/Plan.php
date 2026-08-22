<?php

namespace Alle80\Griglia\Support;

use Alle80\Griglia\Agent;
use Alle80\Griglia\Ai\Agents\PlanBuilder;
use Alle80\Griglia\Models\Checklist;
use Alle80\Griglia\Models\Todo;
use Closure;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Ai;

/**
 * Plan mode: a list built from a prompt — the goal is split (by the AI SDK agent PlanBuilder, or by a
 * fake/custom resolver in tests) into tasks chained with `depends_on_id`: the first one is left ⚪ for the
 * user to start, each next one opens 🟢 automatically when the previous is completed.
 */
class Plan
{
    /** @var null|Closure(string): array  Custom/fake resolver: prompt → [['title','notes','subtasks'=>[]], …] */
    public static ?Closure $resolver = null;

    public static function available(): bool
    {
        if (self::$resolver) {
            return true;
        }
        if (! class_exists(Ai::class)) {
            return false;
        }
        $provider = (string) config('ai.default', '');

        return $provider !== '' && ! empty(config("ai.providers.$provider.key"));
    }

    /** Ask the model for the tasks of the plan. Returns [] on failure (logged). */
    public static function tasks(string $prompt, ?string $agent = null): array
    {
        $skills = Skills::forAgent($agent);
        try {
            if (self::$resolver) {
                return self::normalize((self::$resolver)($prompt), array_keys($skills));
            }
            $response = (new PlanBuilder($skills))->prompt($prompt);
            $data = method_exists($response, 'toArray') ? $response->toArray() : (array) $response;

            return self::normalize($data['tasks'] ?? $data, array_keys($skills));
        } catch (\Throwable $e) {
            Log::warning('griglia: plan generation failed: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Build the plan into the list: chained todos appended after the existing ones.
     * Returns the number of tasks created (0 = the model gave nothing: a single «plan request» todo is created instead).
     */
    public static function build(Checklist $list, string $prompt): int
    {
        $list->update(['plan_prompt' => $prompt]);
        $agent = $list->agent && isset(Agent::all()[$list->agent]) ? $list->agent : Agent::defaultKey();
        $tasks = self::available() ? self::tasks($prompt, $agent) : [];
        $order = (int) $list->todos()->whereNull('archived_at')->max('order');
        if (! $tasks) {
            $list->todos()->create([
                'title' => __('griglia::t.plan.request_title'),
                'notes' => __('griglia::t.plan.request_notes')."\n\n".$prompt,
                'order' => $order + 1,
            ]);

            return 0;
        }
        $prev = null;
        foreach ($tasks as $t) {
            $todo = $list->todos()->create([
                'title' => $t['title'],
                'notes' => $t['notes'] !== '' ? $t['notes'] : null,
                'order' => ++$order,
                'depends_on_id' => $prev?->id,
                'skills' => $t['skills'] ?: null,
            ]);
            foreach (array_values($t['subtasks']) as $i => $name) {
                $todo->ingredients()->create(['name' => $name, 'order' => $i + 1]);
            }
            $prev = $todo;
        }

        return count($tasks);
    }

    /** @param list<string> $availableSkills */
    private static function normalize(mixed $tasks, array $availableSkills = []): array
    {
        $out = [];
        foreach ((array) $tasks as $t) {
            if (is_string($t)) {
                $t = ['title' => $t];
            }
            if (! is_array($t) || trim((string) ($t['title'] ?? '')) === '') {
                continue;
            }
            $out[] = [
                'title' => mb_strimwidth(trim((string) $t['title']), 0, 200, '…'),
                'notes' => trim((string) ($t['notes'] ?? '')),
                'subtasks' => array_values(array_filter(array_map(fn ($s) => trim((string) $s), (array) ($t['subtasks'] ?? [])), fn ($s) => $s !== '')),
                'skills' => array_values(array_unique(array_filter(
                    array_map(fn ($s) => trim((string) $s), (array) ($t['skills'] ?? [])),
                    fn ($s) => in_array($s, $availableSkills, true),
                ))),
            ];
        }

        return $out;
    }
}
