<?php

namespace Alle80\Devboard\Support;

use Alle80\Devboard\Models\Checklist;
use Alle80\Devboard\Models\Todo;
use Closure;
use Illuminate\Support\Facades\Log;

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
        if (! class_exists(\Laravel\Ai\Ai::class)) {
            return false;
        }
        $provider = (string) config('ai.default', '');

        return $provider !== '' && ! empty(config("ai.providers.$provider.key"));
    }

    /** Ask the model for the tasks of the plan. Returns [] on failure (logged). */
    public static function tasks(string $prompt): array
    {
        try {
            if (self::$resolver) {
                return self::normalize((self::$resolver)($prompt));
            }
            $response = (new \Alle80\Devboard\Ai\Agents\PlanBuilder)->prompt($prompt);
            $data = method_exists($response, 'toArray') ? $response->toArray() : (array) $response;

            return self::normalize($data['tasks'] ?? $data);
        } catch (\Throwable $e) {
            Log::warning('devboard: plan generation failed: '.$e->getMessage());

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
        $tasks = self::available() ? self::tasks($prompt) : [];
        $order = (int) $list->todos()->whereNull('archived_at')->max('order');
        if (! $tasks) {
            $list->todos()->create([
                'title' => __('devboard::t.plan.request_title'),
                'notes' => __('devboard::t.plan.request_notes')."\n\n".$prompt,
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
            ]);
            foreach (array_values($t['subtasks']) as $i => $name) {
                $todo->ingredients()->create(['name' => $name, 'order' => $i + 1]);
            }
            $prev = $todo;
        }

        return count($tasks);
    }

    private static function normalize(mixed $tasks): array
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
            ];
        }

        return $out;
    }
}
