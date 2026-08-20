<?php

namespace Alle80\Griglia\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Plan mode: turns a goal (the user's prompt) into an ordered list of chained tasks for the coding agent.
 * Structured output: {tasks: [{title, notes, subtasks[]}]}. Provider/model: the SDK defaults (config ai.default).
 */
#[MaxTokens(4000)]
#[Timeout(120)]
class PlanBuilder implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        $lang = match (app()->getLocale()) { 'it' => 'Italian', 'en' => 'English', default => app()->getLocale() };

        return <<<TXT
        You are a senior tech lead planning the work of a coding agent on a software project.
        Given a goal, split it into an ORDERED sequence of 3-12 concrete, self-contained tasks that can be executed
        one after the other (each task may depend on the previous ones; never on later ones). For every task give:
        - title: short imperative sentence (max 80 characters);
        - notes: what to do and why, acceptance criteria, hints (2-6 sentences, markdown allowed);
        - subtasks: 0-6 short checklist items.
        Write in {$lang}. Do not add tasks for things that are not asked. Return only the structured data.
        TXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'tasks' => $schema->array()->items(
                $schema->object([
                    'title' => $schema->string()->required(),
                    'notes' => $schema->string()->required(),
                    'subtasks' => $schema->array()->items($schema->string())->required(),
                ])
            )->required(),
        ];
    }
}
