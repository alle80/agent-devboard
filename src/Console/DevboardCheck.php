<?php

namespace Alle80\Devboard\Console;

use Alle80\Devboard\Models\Checklist;
use Alle80\Devboard\Settings\AgentSettings;
use Illuminate\Console\Command;

/**
 * Communication channel user → coding agent: the "agent list" (config devboard.agent_list, e.g. «dev»)
 * holds requests as todos. Workflow on the row dot: ⚪ waiting (do not touch) → 🟢 open to work (user)
 * → 🔧 working (agent, --take) → ✔ done (--done --comment); ❓ questions (--ask --q) pause the item
 * until the user answers and restarts it. This command lists what the agent may work on, with notes,
 * sub-tasks, questions/answers, the context of resumed items and the agent settings to follow.
 */
class DevboardCheck extends Command
{
    protected $signature = 'devboard:check
        {--all : Also show completed items and items not open to work}
        {--json : Machine-readable output}
        {--take= : Id of the todo to mark as working (take in charge)}
        {--done= : Id of the todo to mark as completed}
        {--comment= : Agent comment saved on the todo of --take/--done (claude_comment)}
        {--progress= : Progress percentage 0-100 to set while working (use with --take; re-run to update)}
        {--ask= : Id of the todo to ask questions about (state ❓)}
        {--q=* : Text of each question, repeatable}';

    protected $aliases = ['sviluppo:check'];

    protected $description = 'Lists the open requests of the agent list (see config devboard.agent_list)';

    public function handle(): int
    {
        $name = (string) config('devboard.agent_list', 'dev');
        $list = Checklist::where('name', $name)->first();

        if (! $list) {
            $this->warn(sprintf('No list named "%s" (config devboard.agent_list).', $name));

            return self::SUCCESS;
        }

        // Questions: pause the work until the user answers and restarts the item
        if ($id = $this->option('ask')) {
            $t = $list->todos()->findOrFail((int) $id);
            $qs = array_values(array_filter(array_map('trim', (array) $this->option('q'))));
            if (! $qs) {
                $this->error('At least one --q="question" is required');

                return self::FAILURE;
            }
            $next = ((int) $t->questions()->max('order')) + 1;
            foreach ($qs as $q) {
                $t->questions()->create(['question' => $q, 'order' => $next++]);
            }
            $t->update(['question' => true, 'working' => false, 'open_to_work' => false]);
            $this->info(sprintf('❓ %d questions asked on «%s» (id:%d, waiting for answers)', count($qs), $t->title, $t->id));
        }

        // Quick actions: take in charge / complete with comment
        foreach (['take' => ['working' => true, 'stopped_at' => null], 'done' => ['working' => false, 'completed' => true, 'result_seen' => false]] as $opt => $attrs) {
            if ($id = $this->option($opt)) {
                $t = $list->todos()->findOrFail((int) $id);
                if ($c = $this->option('comment')) {
                    $attrs['claude_comment'] = $c;
                }
                if ($opt === 'take' && $this->option('progress') !== null) {
                    $attrs['progress'] = max(0, min(100, (int) $this->option('progress')));
                }
                if ($opt === 'done') {
                    $attrs['progress'] = null; // finished → no progress bar
                }
                $t->update($attrs);
                if ($opt === 'done' && app(AgentSettings::class)->check_subtasks_on_done) {
                    $t->ingredients()->update(['checked' => true]);
                }
                $this->info(sprintf('%s: «%s» (id:%d)', $opt === 'take' ? '🔧 taken in charge' : '✔ completed', $t->title, $t->id));
            }
        }

        $marker = storage_path('app/devboard-last-check');
        $last = is_file($marker) ? (int) file_get_contents($marker) : 0;

        $query = $list->todos()->whereNull('archived_at')->with(['ingredients', 'questions', 'parent.ingredients'])->orderBy('order');
        if (! $this->option('all')) {
            // Only what the user marked "open to work" 🟢 (or already in progress)
            $query->where('completed', false)->where('question', false)->where(fn ($q) => $q->where('open_to_work', true)->orWhere('working', true));
        }
        $todos = $query->get();

        if ($this->option('json')) {
            $this->line($todos->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->line('⚙️ settings (/settings) — FOLLOW THEM: '.app(AgentSettings::class)->summary());
            $this->info(sprintf('List "%s": %d items %s', $name, $todos->count(), $this->option('all') ? 'in total' : 'open to work 🟢 (in list order = priority)'));
            if (! $this->option('all')) {
                $waiting = $list->todos()->whereNull('archived_at')->where('completed', false)->where('open_to_work', false)->where('working', false)->where('question', false)->count();
                if ($waiting) $this->line("   (+{$waiting} open but not yet open to work: do not touch them)");
                $asking = $list->todos()->whereNull('archived_at')->where('completed', false)->where('question', true)->count();
                if ($asking) $this->line("   (+{$asking} waiting for the user's answers ❓)");
            }

            foreach ($todos as $t) {
                $isNew = $t->updated_at->timestamp > $last;
                $this->line(sprintf('%s [%s] %s #%d %s  (id:%d)', $isNew ? '🆕' : '  ', $t->completed ? 'x' : ' ', $t->question ? '❓' : ($t->working ? '🔧' : ($t->open_to_work ? '🟢' : '⚪')), $t->order, $t->title, $t->id));
                if ($t->parent) {
                    $this->line(sprintf('        ↩ resumes «%s» (id:%d): the previous context still applies', $t->parent->title, $t->parent->id));
                    if ($t->parent->notes) $this->line('           previous note: '.str_replace("\n", "\n              ", $t->parent->notes));
                    if ($t->parent->claude_comment) $this->line('           🤖 previous: '.str_replace("\n", "\n              ", $t->parent->claude_comment));
                    foreach ($t->parent->ingredients as $i) $this->line(sprintf('           - [%s] %s', $i->checked ? 'x' : ' ', $i->name));
                }
                if ($t->stopped_at) {
                    $this->line('        ⏹ stopped by the user on '.$t->stopped_at->format('d/m H:i').': do NOT work on it until it is 🟢 again');
                }
                if ($t->notes) {
                    $this->line('        note: '.str_replace("\n", "\n              ", $t->notes));
                }
                if ($t->claude_comment) {
                    $this->line('        🤖 agent: '.str_replace("\n", "\n                 ", $t->claude_comment));
                }
                foreach ($t->ingredients as $i) {
                    $this->line(sprintf('        - [%s] %s', $i->checked ? 'x' : ' ', $i->name));
                }
                foreach ($t->questions as $q) {
                    $this->line('        ❓ '.$q->question);
                    $this->line('           → '.($q->answer ?? '(no answer)'));
                }
            }
        }

        file_put_contents($marker, (string) now()->timestamp);

        return self::SUCCESS;
    }
}
