<?php

namespace Alle80\Griglia\Console;

use Alle80\Griglia\Models\Checklist;
use Alle80\Griglia\Models\Todo;
use Alle80\Griglia\Settings\AgentSettings;
use Alle80\Griglia\Settings\OptimizationSettings;
use Alle80\Griglia\Support\Notify;
use Illuminate\Console\Command;

/**
 * Communication channel user → coding agent: the "agent list" (config griglia.agent_list, e.g. «dev»)
 * holds requests as todos. Workflow on the row dot: ⚪ waiting (do not touch) → 🟢 open to work (user)
 * → 🔧 working (agent, --take) → ✔ done (--done --comment); ❓ questions (--ask --q) pause the item
 * until the user answers and restarts it. This command lists what the agent may work on, with notes,
 * sub-tasks, questions/answers, the context of resumed items and the agent settings to follow.
 */
class GrigliaCheck extends Command
{
    protected $signature = 'griglia:check
        {--all : Also show completed items and items not open to work}
        {--json : Machine-readable output}
        {--take= : Id of the todo to mark as working (take in charge)}
        {--done= : Id of the todo to mark as completed}
        {--comment= : Agent comment saved on the todo of --take/--done (claude_comment)}
        {--progress= : Progress percentage 0-100 shown on the working todo (with --take; re-run --take=ID --progress=N to update). --take alone starts at 0%}
        {--phase= : Short text of what the agent is doing now (with --take; e.g. "writing code", "testing"); shown next to the %}
        {--ask= : Id of the todo to ask questions about (the task pauses in the question state)}
        {--q=* : Text of each question, repeatable}
        {--tokens-in= : Input tokens spent on the todo since the last --take (added to its stats; with --take/--done/--ask)}
        {--tokens-out= : Output tokens spent on the todo since the last --take (added to its stats; with --take/--done/--ask)}
        {--agent= : Only the tasks of this agent key (multi-agent; default: GRIGLIA_AGENT_KEY, or every task when one agent)}';

    protected $aliases = ['sviluppo:check'];

    protected $description = 'Lists the open requests of the agent list (see config griglia.agent_list)';

    public function handle(): int
    {
        $name = (string) config('griglia.agent_list', 'dev');
        $list = Checklist::where('name', $name)->first();

        if (! $list) {
            $this->warn(sprintf('No list named "%s" (config griglia.agent_list).', $name));

            return self::SUCCESS;
        }

        // Scope: the agent list + the owner's PLAN lists (built from a prompt / chained tasks): starting a plan
        // means the agent works on that list too, after the agent list
        $planLists = Checklist::where('user_id', $list->user_id)->whereKeyNot($list->id)->whereNull('archived_at')
            ->where(fn ($q) => $q->whereNotNull('plan_prompt')->orWhereHas('todos', fn ($t) => $t->whereNotNull('depends_on_id')))
            ->orderBy('id')->get();
        $scopeIds = $planLists->pluck('id')->push($list->id)->all();
        $find = fn (int $id) => Todo::whereIn('checklist_id', $scopeIds)->findOrFail($id);

        // Questions: pause the work until the user answers and restarts the item
        if ($id = $this->option('ask')) {
            $t = $find((int) $id);
            $qs = array_values(array_filter(array_map('trim', (array) $this->option('q'))));
            if (! $qs) {
                $this->error('At least one --q="question" is required');

                return self::FAILURE;
            }
            $next = ((int) $t->questions()->max('order')) + 1;
            foreach ($qs as $q) {
                $t->questions()->create(['question' => $q, 'order' => $next++]);
            }
            $t->update(['question' => true, 'working' => false, 'open_to_work' => false, 'phase' => null] + $this->tokenAttrs($t));
            Notify::questionAsked($t, $qs); // the app notifies the user (bell / web push / mail)
            $this->info(sprintf('❓ %d questions asked on «%s» (id:%d, waiting for answers)', count($qs), $t->title, $t->id));
        }

        // Quick actions: take in charge / complete with comment
        foreach (['take' => ['working' => true, 'stopped_at' => null], 'done' => ['working' => false, 'completed' => true, 'result_seen' => false]] as $opt => $attrs) {
            if ($id = $this->option($opt)) {
                $t = $find((int) $id);
                if ($c = $this->option('comment')) {
                    $attrs['claude_comment'] = $c;
                }
                if ($opt === 'take') {
                    // Always show a percentage on a working todo: explicit value, else keep the current one, else start at 0%
                    $attrs['progress'] = $this->option('progress') !== null
                        ? max(0, min(100, (int) $this->option('progress')))
                        : ($t->progress ?? 0);
                }
                if ($opt === 'take' && $this->option('phase') !== null) {
                    $attrs['phase'] = mb_substr(trim((string) $this->option('phase')), 0, 80) ?: null;
                }
                if ($opt === 'done') {
                    $attrs['progress'] = null; // finished → no progress bar
                    $attrs['phase'] = null;
                }
                $t->update($attrs + $this->tokenAttrs($t));
                if ($opt === 'done' && app(AgentSettings::class)->check_subtasks_on_done) {
                    $t->ingredients()->update(['checked' => true]);
                }
                if ($opt === 'done') {
                    Notify::todoCompleted($t); // the app notifies the user (bell / web push / mail)
                }
                $this->info(sprintf('%s: «%s» (id:%d)%s', $opt === 'take' ? '🔧 taken in charge' : '✔ completed', $t->title, $t->id, $opt === 'take' ? sprintf(' — %d%%%s', $attrs['progress'], ! empty($attrs['phase']) ? ' · '.$attrs['phase'] : '') : ''));
                if ($opt === 'done' && $t->hasStats()) {
                    $this->line('   📊 '.$t->statsLine());
                }
            }
        }

        $opt = app(OptimizationSettings::class);
        $acted = $this->option('take') || $this->option('done') || $this->option('ask');
        if ($acted && $opt->compact_check && ! $this->option('all') && ! $this->option('json')) {
            return self::SUCCESS; // compact: the result line is enough, no settings/listing (token saving)
        }

        $marker = storage_path('app/griglia-last-check');
        $last = is_file($marker) ? (int) file_get_contents($marker) : 0;

        // Multi-agent: which agent am I? (option, else config key); with several agents only my tasks are listed
        $me = (string) ($this->option('agent') ?: (\Alle80\Griglia\Agent::many() ? \Alle80\Griglia\Agent::defaultKey() : ''));
        $workable = function (Checklist $l) use ($me) {
            $query = $l->todos()->whereNull('archived_at')->with(['ingredients', 'questions', 'parent.ingredients'])->orderBy('order');
            if (! $this->option('all')) {
                // Only what the user marked "open to work" 🟢 (or already in progress)
                $query->where('completed', false)->where('question', false)->where(fn ($q) => $q->where('open_to_work', true)->orWhere('working', true));
            }
            $todos = $query->get();
            if ($me !== '') {
                $todos = $todos->filter(fn ($t) => \Alle80\Griglia\Agent::effective($t, $l) === $me)->values();
            }

            return $todos;
        };
        $todos = $workable($list);
        $planTodos = $planLists->mapWithKeys(fn ($l) => [$l->id => $workable($l)])->filter(fn ($c) => $c->isNotEmpty());

        if ($this->option('json')) {
            $all = $todos;
            foreach ($planTodos as $c) $all = $all->concat($c);
            $this->line($all->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->line('⚙️ settings (/settings) — FOLLOW THEM: '.app(AgentSettings::class)->summary());
            $this->line('⚡ optimization: '.$opt->summary());
            if (\Alle80\Griglia\Agent::many()) {
                $this->line(sprintf('🤝 agents: %s — you are «%s» (%s): only your tasks are listed', implode(', ', array_map(fn ($k, $v) => "$k=$v", array_keys(\Alle80\Griglia\Agent::all()), \Alle80\Griglia\Agent::all())), $me, \Alle80\Griglia\Agent::label($me)));
            }
            if ($opt->terse_agent) {
                $this->line('⚡ '.$opt->terseRules());
            }
            $this->info(sprintf('List "%s": %d items %s', $name, $todos->count(), $this->option('all') ? 'in total' : 'open to work 🟢 (in list order = priority)'));
            if (! $this->option('all')) {
                $waiting = $list->todos()->whereNull('archived_at')->where('completed', false)->where('open_to_work', false)->where('working', false)->where('question', false)->count();
                if ($waiting) $this->line("   (+{$waiting} open but not yet open to work: do not touch them)");
                $asking = $list->todos()->whereNull('archived_at')->where('completed', false)->where('question', true)->count();
                if ($asking) $this->line("   (+{$asking} waiting for the user's answers ❓)");
            }

            $render = function ($todos) use ($last, $opt) {
            foreach ($todos as $t) {
                $isNew = $t->updated_at->timestamp > $last;
                $this->line(sprintf('%s [%s] %s #%d %s%s%s  (id:%d)', $isNew ? '🆕' : '  ', $t->completed ? 'x' : ' ', $t->question ? '❓' : ($t->working ? '🔧' : ($t->open_to_work ? '🟢' : '⚪')), $t->order, $t->title, $t->working && $t->progress !== null ? sprintf(' [%d%%%s]', $t->progress, $t->phase ? ' · '.$t->phase : '') : '', \Alle80\Griglia\Agent::many() ? ' {agent: '.\Alle80\Griglia\Agent::effective($t).'}' : '', $t->id));
                if ($t->parent) {
                    $this->line(sprintf('        ↩ resumes «%s» (id:%d): the previous context still applies', $t->parent->title, $t->parent->id));
                    if ($t->parent->notes) $this->line('           previous note: '.str_replace("\n", "\n              ", $opt->trim($t->parent->notes)));
                    if ($t->parent->claude_comment) $this->line('           🤖 previous: '.str_replace("\n", "\n              ", $opt->trim($t->parent->claude_comment)));
                    foreach ($t->parent->ingredients as $i) $this->line(sprintf('           - [%s] %s', $i->checked ? 'x' : ' ', $i->name));
                }
                if ($t->working && $t->working_since) {
                    $this->line(sprintf('        ⏱ working since %s (%s this interval%s)', $t->working_since->toIso8601String(), Todo::formatDuration(max(0, (int) $t->working_since->diffInSeconds(now()))), $t->work_seconds ? ', '.Todo::formatDuration($t->workSeconds()).' in total' : ''));
                } elseif ($t->hasStats()) {
                    $this->line('        📊 '.$t->statsLine());
                }
                if ($t->stopped_at) {
                    $this->line('        ⏹ stopped by the user on '.$t->stopped_at->format('d/m H:i').': do NOT work on it until it is 🟢 again');
                }
                if ($t->depends_on_id) {
                    $dep = Todo::find($t->depends_on_id);
                    $this->line(sprintf('        ⛓ plan chain: after «%s» (id:%d, %s) — the next task opens automatically when this one is done', $dep?->title ?? '?', $t->depends_on_id, $dep?->completed ? 'done' : 'NOT done yet'));
                }
                if ($t->skills) {
                    $this->line('        🧩 skills to activate for this task (Skill tool): '.implode(', ', (array) $t->skills));
                }
                if ($t->notes) {
                    $this->line('        note: '.str_replace("\n", "\n              ", $t->notes));
                }
                if ($t->claude_comment) {
                    $this->line('        🤖 agent: '.str_replace("\n", "\n                 ", $opt->trim($t->claude_comment)));
                }
                foreach ($t->ingredients as $i) {
                    $this->line(sprintf('        - [%s] %s', $i->checked ? 'x' : ' ', $i->name));
                }
                foreach ($t->questions as $q) {
                    $this->line('        ❓ '.$q->question);
                    $this->line('           → '.($q->answer ?? '(no answer)'));
                }
            }
            };
            $render($todos);

            // Plans: lists built from a prompt / chained tasks — work them AFTER the agent list, following the chain
            foreach ($planTodos as $listId => $pt) {
                $pl = $planLists->firstWhere('id', $listId);
                $this->info(sprintf('📐 Plan «%s» (list id:%d): %d items %s — follow the chain (next task opens when the previous is done)', $pl->name, $pl->id, $pt->count(), $this->option('all') ? 'in total' : 'open to work 🟢'));
                $render($pt);
            }
        }

        // Dead ends: a plan with work left but nothing the agent may take. The user would wait for an agent
        // that is waiting for the board — say it out loud, with the way out (task 347). Never in --json:
        // that output is parsed by scripts.
        foreach ($this->option('json') ? collect() : $planLists as $pl) {
            $pending = $pl->todos()->whereNull('archived_at')->where('completed', false)->count();
            $openable = $pl->todos()->whereNull('archived_at')->where('completed', false)
                ->where(fn ($q) => $q->where('open_to_work', true)->orWhere('working', true)->orWhere('question', true))->count();

            if ($pending > 0 && $openable === 0) {
                $this->warn(sprintf('⚠ Plan «%s» (list id:%d): %d task(s) left but none is open to work%s — start it with ▶ on the board, or open one by hand.',
                    $pl->name, $pl->id, $pending, $pl->plan_paused ? ' (the plan is paused)' : ''));
            }
        }

        file_put_contents($marker, (string) now()->timestamp);

        return self::SUCCESS;
    }

    /** Token counters to add to the todo from --tokens-in / --tokens-out (cumulative per todo). */
    private function tokenAttrs(Todo $t): array
    {
        $attrs = [];
        foreach (['tokens-in' => 'tokens_in', 'tokens-out' => 'tokens_out'] as $opt => $col) {
            if ($this->option($opt) !== null) {
                $attrs[$col] = (int) $t->{$col} + max(0, (int) $this->option($opt));
            }
        }

        return $attrs;
    }
}
