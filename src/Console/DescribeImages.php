<?php

namespace Alle80\Griglia\Console;

use Alle80\Griglia\Models\Attachment;
use Alle80\Griglia\Support\ImageDescription;
use Illuminate\Console\Command;

class DescribeImages extends Command
{
    protected $signature = 'griglia:describe-images {--all : Also regenerate existing descriptions} {--limit=100}';

    protected $aliases = ['images:describe'];

    protected $description = 'Generates the AI text description of attached images (used by the search)';

    public function handle(): int
    {
        if (! ImageDescription::enabled()) {
            $this->warn('No AI provider configured (AI_IMAGE_PROVIDERS / API keys in .env, or the setting is off): nothing to do.');

            return self::SUCCESS;
        }

        $query = Attachment::query()->orderBy('id');
        if (! $this->option('all')) {
            $query->whereNull('description');
        }

        $done = 0;
        foreach ($query->limit((int) $this->option('limit'))->get() as $a) {
            $text = ImageDescription::describe($a);
            $this->line(sprintf('#%d %s → %s', $a->id, $a->original_name, $text ? mb_substr($text, 0, 80).'…' : '(no description)'));
            $done += $text ? 1 : 0;
        }

        $this->info("Described: {$done}");

        return self::SUCCESS;
    }
}
