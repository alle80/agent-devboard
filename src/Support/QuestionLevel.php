<?php

namespace Alle80\Griglia\Support;

use Alle80\Griglia\Models\ContextBlock;
use Alle80\Griglia\Models\ContextGroup;
use Alle80\Griglia\Settings\AgentSettings;

/**
 * The question level (setting `agent.autonomy`, task 499): how many questions the agent asks before it
 * really starts working on a task — from `autonomous` (never asks) to `paranoid` (questions, then plan
 * approval). Every level has a short block of rules: `griglia:check` prints it right under the settings
 * line, /settings previews it next to the select, and sync() writes it into the agent context
 * (/context → the generated instruction files) as a *managed* block that follows the setting.
 */
class QuestionLevel
{
    /** The levels in order, from the fewest questions to the most. */
    public const LEVELS = ['autonomous', 'essential', 'ask', 'many', 'paranoid'];

    /** Key of the managed context block (context_blocks.key). */
    public const BLOCK_KEY = 'question_level';

    /** The level in force (an unknown stored value counts as the default `ask`). */
    public static function current(): string
    {
        $v = (string) app(AgentSettings::class)->autonomy;

        return in_array($v, self::LEVELS, true) ? $v : 'ask';
    }

    /** Short name of a level («Paranoid»): the option label without its « — explanation» tail. */
    public static function name(string $level): string
    {
        $labels = (array) __('griglia::t.settings_options.autonomy');

        return trim(explode(' — ', (string) ($labels[$level] ?? $level), 2)[0]);
    }

    /** The rules of a level, in the language of the board. */
    public static function rules(?string $level = null): string
    {
        return (string) __('griglia::t.question_level.rules.'.($level ?? self::current()));
    }

    /** The context block of a level: bold lead with the level name, then its rules (markdown). */
    public static function body(?string $level = null): string
    {
        $level ??= self::current();

        return '**'.__('griglia::t.question_level.block_title').': '.self::name($level).'** — '.self::rules($level);
    }

    /** level => block body, for the preview on /settings. */
    public static function previews(): array
    {
        return array_combine(self::LEVELS, array_map(fn ($l) => self::body($l), self::LEVELS));
    }

    /** The line `griglia:check` prints under the settings. */
    public static function checkLine(): string
    {
        return '❓ question level — FOLLOW IT: '.self::body();
    }

    /**
     * Write the block of the current level into the context. The managed block is updated in place
     * (wherever the user moved it, keeping its switch); a block that carries the same lead but no key
     * (a re-import of the generated file) is adopted; otherwise a new group with the block is appended.
     */
    public static function sync(): ContextBlock
    {
        $title = (string) __('griglia::t.question_level.block_title');
        $block = ContextBlock::where('key', self::BLOCK_KEY)->first()
            ?? ContextBlock::whereNull('key')->where('body', 'like', '**'.$title.': %')->orderBy('id')->first();
        if ($block) {
            $block->update(['key' => self::BLOCK_KEY, 'title' => $title, 'body' => self::body()]);

            return $block;
        }
        $group = ContextGroup::create(['title' => __('griglia::t.question_level.group_title'), 'order' => ((int) ContextGroup::max('order')) + 1, 'enabled' => true]);

        return $group->blocks()->create(['key' => self::BLOCK_KEY, 'title' => $title, 'body' => self::body(), 'order' => 1, 'enabled' => true]);
    }
}
