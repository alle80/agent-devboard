<?php

namespace Alle80\Griglia\Console;

use Alle80\Griglia\Agent;
use Alle80\Griglia\Domain\ReviewWorkflow;
use Alle80\Griglia\Models\Checklist;
use Alle80\Griglia\Models\Todo;
use Alle80\Griglia\Settings\AgentSettings;
use Alle80\Griglia\Settings\OptimizationSettings;
use Alle80\Griglia\Support\AgentStatus;
use Alle80\Griglia\Support\Markdown;
use Alle80\Griglia\Support\Notify;
use Alle80\Griglia\Support\QuestionLevel;
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
        {--worker-json : Machine-readable tasks plus worker scheduling settings}
        {--take= : Id of the todo to mark as working (take in charge)}
        {--pause= : Id of the working todo to pause until its agent worker can resume it}
        {--done= : Id of the todo to mark as completed}
        {--approve= : Id of a working review attempt to approve}
        {--request-changes= : Id of a working review attempt that must return to its executor}
        {--comment= : Agent comment saved on --take/--done/--approve/--request-changes (claude_comment)}
        {--summary= : Very short result summary shown below the task title (with --done)}
        {--progress= : Progress percentage 0-100 shown on the working todo (with --take; re-run --take=ID --progress=N to update). --take alone starts at 0%}
        {--phase= : Short text of what the agent is doing now (with --take; e.g. "writing code", "testing"); shown next to the %}
        {--outcome= : With --done: how the result feels — ok (default, nothing to check), alert (done, but something needs a look) or blocked (something is in the way). It colours the row until the user opens it}
        {--ask= : Id of the todo to ask questions about (the task pauses in the question state)}
        {--q=* : Text of each question, repeatable}
        {--choices=* : Pipe-separated closed choices for the corresponding --q, repeatable; free text remains available}
        {--tokens-in= : Input tokens spent since the last --take (with any task action)}
        {--tokens-out= : Output tokens spent since the last --take (with any task action)}
        {--agent= : Only the tasks of this agent key (multi-agent; default: GRIGLIA_AGENT_KEY, or every task when one agent)}
        {--force : Act on a task that belongs to another agent, or take again a task the user stopped (--take/--done/--ask refuse it otherwise)}';

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

        // Multi-agent: which agent am I? (option, else config key); with several agents only my tasks are listed
        $me = (string) ($this->option('agent') ?: (Agent::many() ? Agent::defaultKey() : ''));

        // Several agents at once must not step on each other: every task belongs to ONE agent (task override,
        // else list default, else the default agent). Acting on somebody else's task is refused, so a wrong id
        // in a prompt cannot steal the work another agent is already doing; --force is the deliberate way in.
        $trespass = function (Todo $t, string $action) use ($me): bool {
            $owner = Agent::effective($t);
            if ($me === '' || $this->option('force') || $owner === $me) {
                return false;
            }
            $this->error(sprintf('«%s» (id:%d) belongs to agent «%s», you are «%s»: refusing to %s it%s. Reassign it on the board (task or list agent), or re-run with --force.',
                $t->title, $t->id, Agent::label($owner), Agent::label($me), $action,
                $t->working ? ' — it is being worked on right now' : ''));

            return true;
        };

        // Outcome of a closed task: it decides the colour of the highlight the user sees on the board
        $outcome = $this->option('outcome') !== null ? strtolower(trim((string) $this->option('outcome'))) : null;
        if ($outcome !== null && ! in_array($outcome, Todo::OUTCOMES, true)) {
            $this->error(sprintf('--outcome must be one of: %s', implode(', ', Todo::OUTCOMES)));

            return self::FAILURE;
        }

        $actions = array_filter([
            'take' => $this->option('take'), 'pause' => $this->option('pause'),
            'done' => $this->option('done'), 'ask' => $this->option('ask'),
            'approve' => $this->option('approve'),
            'request-changes' => $this->option('request-changes'),
        ], fn ($id) => $id !== null && $id !== false);
        if (count($actions) > 1) {
            $this->error('Use only one task action at a time.');

            return self::FAILURE;
        }

        // Agent pause: preserve progress, close the timed working interval, and wait for the user to reopen it.
        if ($id = $this->option('pause')) {
            $t = $find((int) $id);
            if ($trespass($t, 'pause')) {
                return self::FAILURE;
            }
            if (! $t->working || $t->completed) {
                $this->error(sprintf('«%s» (id:%d) is not being worked on: only a working task can be paused.', $t->title, $t->id));

                return self::FAILURE;
            }
            $phase = trim((string) $this->option('phase'));
            $t->update(['paused' => true, 'working' => false, 'open_to_work' => false, 'question' => false]
                + ($phase !== '' ? ['phase' => $phase] : []) + $this->tokenAttrs($t));
            $this->info(sprintf('⏸ paused: «%s» (id:%d) — its persistent worker will resume it automatically', $t->title, $t->id));
        }

        // Questions: pause the work until the user answers and restarts the item
        if ($id = $this->option('ask')) {
            $t = $find((int) $id);
            if ($trespass($t, 'ask questions on')) {
                return self::FAILURE;
            }
            $qs = array_values(array_filter(array_map('trim', (array) $this->option('q'))));
            if (! $qs) {
                $this->error('At least one --q="question" is required');

                return self::FAILURE;
            }
            $next = ((int) $t->questions()->max('order')) + 1;
            $choiceGroups = array_values((array) $this->option('choices'));
            foreach ($qs as $index => $q) {
                $choices = array_values(array_unique(array_filter(array_map('trim', explode('|', $choiceGroups[$index] ?? '')))));
                $t->questions()->create(['question' => $q, 'choices' => $choices ?: null, 'order' => $next++]);
            }
            $t->update(['question' => true, 'working' => false, 'paused' => false, 'open_to_work' => false, 'phase' => null] + $this->tokenAttrs($t));
            Notify::questionAsked($t, $qs); // the app notifies the user (bell / web push / mail)
            $this->info(sprintf('❓ %d questions asked on «%s» (id:%d, waiting for answers)', count($qs), $t->title, $t->id));
        }

        foreach (['approve', 'request-changes'] as $decision) {
            if (! ($id = $this->option($decision))) {
                continue;
            }
            $t = $find((int) $id);
            if ($trespass($t, $decision === 'approve' ? 'approve' : 'request changes on')) {
                return self::FAILURE;
            }
            $alreadyDecided = $t->review_outcome !== null;
            $comment = Markdown::normalizeAgentResponse($this->option('comment'));
            if ($decision === 'request-changes' && ! $comment) {
                $this->error('--request-changes requires --comment="what must be changed".');

                return self::FAILURE;
            }
            $report = $this->tokenAttrs($t) + [
                'outcome' => $outcome ?? ($decision === 'approve' ? 'ok' : 'alert'),
            ];
            if ($comment) {
                $report['claude_comment'] = $comment;
                $report['result_summary'] = mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags($comment))), 0, 120) ?: null;
            }
            $workflow = app(ReviewWorkflow::class);
            $original = $decision === 'approve'
                ? $workflow->approve($t, $me !== '' ? $me : Agent::effective($t), $report)
                : $workflow->requestChanges($t, $me !== '' ? $me : Agent::effective($t), $report);
            if ($decision === 'approve' && ! $alreadyDecided) {
                Notify::todoCompleted($original);
            }
            $this->info(sprintf('%s: «%s» (id:%d) — original «%s» (id:%d) %s',
                $decision === 'approve' ? '✅ review approved' : '↩ changes requested',
                $t->title, $t->id, $original->title, $original->id,
                $decision === 'approve' ? 'completed' : 'reopened'));
        }

        // Quick actions: take in charge / complete with comment
        // A completed task stays completed: to carry on, the user creates a new one with «resume» (task 348).
        // Done is done: a closed task carries no open question and is no longer «open to work» (same as closing it from the modal).
        foreach (['take' => ['working' => true, 'paused' => false, 'stopped_at' => null, 'question' => false], 'done' => ['working' => false, 'paused' => false, 'completed' => true, 'question' => false, 'open_to_work' => false, 'result_seen' => false]] as $opt => $attrs) {
            if ($id = $this->option($opt)) {
                $t = $find((int) $id);

                if ($trespass($t, $opt === 'take' ? 'take' : 'close')) {
                    return self::FAILURE;
                }
                if ($opt === 'take' && $t->completed) {
                    $this->error(sprintf('«%s» (id:%d) is already completed — a closed task stays closed. Ask the user to press ↻ resume: it creates a new task linked to this one.', $t->title, $t->id));

                    return self::FAILURE;
                }
                // ⏹ The user stopped it (click on the 🔧 dot) and has not put it back to 🟢: a progress update
                // run as «--take=ID --progress=N» must not silently resume the work and wipe the stop.
                if ($opt === 'take' && $t->stopped_at && ! $t->open_to_work && ! $t->working && ! $this->option('force')) {
                    $this->error(sprintf('«%s» (id:%d) was stopped by the user on %s: do NOT work on it until it is 🟢 again (re-run with --force only if the user told you to go on).', $t->title, $t->id, $t->stopped_at->format('d/m H:i')));

                    return self::FAILURE;
                }
                if ($c = Markdown::normalizeAgentResponse($this->option('comment'))) {
                    $attrs['claude_comment'] = $c;
                    if ($opt === 'done' && $this->option('summary') === null) {
                        $attrs['result_summary'] = mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags($c))), 0, 120) ?: null;
                    }
                }
                if ($opt === 'done' && $this->option('summary') !== null) {
                    $summary = preg_replace('/\s+/', ' ', Markdown::normalizeAgentResponse($this->option('summary')));
                    $attrs['result_summary'] = mb_substr(trim((string) $summary), 0, 120) ?: null;
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
                if ($opt === 'take') {
                    $attrs['outcome'] = null; // back to work: the previous result no longer applies
                }
                if ($opt === 'done') {
                    $attrs['progress'] = null; // finished → no progress bar
                    $attrs['phase'] = null;
                    $attrs['outcome'] = $outcome ?? 'ok';
                }
                $submitted = false;
                if ($opt === 'done' && $t->reviewer_agent) {
                    // Reports and counters belong to the original; the service owns the atomic state boundary.
                    $report = array_intersect_key($attrs + $this->tokenAttrs($t), array_flip(['claude_comment', 'result_summary', 'outcome', 'tokens_in', 'tokens_out']));
                    $t->update($report);
                    app(ReviewWorkflow::class)->submit($t, $me !== '' ? $me : Agent::effective($t));
                    $submitted = true;
                } else {
                    $t->update($attrs + $this->tokenAttrs($t));
                }
                if ($opt === 'done' && app(AgentSettings::class)->check_subtasks_on_done) {
                    $t->ingredients()->update(['checked' => true]);
                }
                if ($opt === 'done' && ! $submitted) {
                    Notify::todoCompleted($t); // the app notifies the user (bell / web push / mail)
                }
                $this->info(sprintf('%s: «%s» (id:%d)%s', $opt === 'take' ? '🔧 taken in charge' : ($submitted ? '🔎 submitted for review' : '✔ completed'), $t->title, $t->id, $opt === 'take'
                    ? sprintf(' — %d%%%s', $attrs['progress'], ! empty($attrs['phase']) ? ' · '.$attrs['phase'] : '')
                    : self::outcomeMark($attrs['outcome'] ?? 'ok')));
                if ($opt === 'done' && $t->hasStats()) {
                    $this->line('   📊 '.$t->statsLine());
                }
            }
        }

        $opt = app(OptimizationSettings::class);
        $acted = $this->option('take') || $this->option('pause') || $this->option('done') || $this->option('ask') || $this->option('approve') || $this->option('request-changes');
        // --json and --worker-json are parsed by scripts (the persistent worker reads the latter): nothing but
        // the JSON document may reach stdout, whatever else the command would like to say (task 507)
        $machine = $this->option('json') || $this->option('worker-json');
        if ($acted && $opt->compact_check && ! $this->option('all') && ! $machine) {
            return self::SUCCESS; // compact: the result line is enough, no settings/listing (token saving)
        }

        // The "new since the last check" baseline is per agent: with two agents on the board, one running
        // `check` must not reset the other's 🆕 markers.
        $marker = storage_path('app/griglia-last-check'.($me !== '' ? '-'.preg_replace('/[^A-Za-z0-9_-]+/', '', $me) : ''));
        $last = is_file($marker) ? (int) file_get_contents($marker) : 0;

        $workable = function (Checklist $l) use ($me) {
            $query = $l->todos()->whereNull('archived_at')->with(['ingredients', 'questions', 'parent.ingredients'])->orderBy('order');
            if (! $this->option('all')) {
                // Only what the user marked "open to work" 🟢 (or already in progress)
                $query->where('completed', false)->where('question', false)->where(fn ($q) => $q->where('open_to_work', true)->orWhere('working', true));
            }
            $todos = $query->get();
            if ($me !== '') {
                $todos = $todos->filter(fn ($t) => Agent::effective($t, $l) === $me)->values();
            }

            return $todos;
        };
        $todos = $workable($list);
        $planTodos = $planLists->mapWithKeys(fn ($l) => [$l->id => $workable($l)])->filter(fn ($c) => $c->isNotEmpty());

        if ($machine) {
            $all = $todos;
            foreach ($planTodos as $c) {
                $all = $all->concat($c);
            }
            // Every task carries its full resume chain (oldest steps included): same history the human output prints
            $items = $all->map(function (Todo $t) {
                $row = $t->toArray();
                $row['resume_chain'] = $t->resumeChain()->map(fn (Todo $p) => [
                    'id' => $p->id,
                    'title' => $p->title,
                    'notes' => $p->notes,
                    'claude_comment' => $p->claude_comment,
                    'ingredients' => $p->ingredients->map(fn ($i) => ['name' => $i->name, 'checked' => (bool) $i->checked])->values()->all(),
                ])->values()->all();

                return $row;
            })->values();
            $payload = $this->option('worker-json')
                ? ['task_mode' => app(AgentSettings::class)->task_mode, 'agents' => AgentStatus::workerAgents(), 'items' => $items]
                : $items;
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->line('⚙️ settings (/settings) — FOLLOW THEM: '.app(AgentSettings::class)->summary());
            $this->line(QuestionLevel::checkLine()); // how many questions before starting (task 499)
            $this->line('⚡ optimization: '.$opt->summary());
            if (Agent::many()) {
                $this->line(sprintf('🤝 agents: %s — you are «%s» (%s): only your tasks are listed', implode(', ', array_map(fn ($k, $v) => "$k=$v", array_keys(Agent::all()), Agent::all())), $me, Agent::label($me)));
            }
            if (Agent::many()) {
                $busy = Todo::whereIn('checklist_id', $scopeIds)->whereNull('archived_at')->where('working', true)
                    ->orderBy('id')->get()->filter(fn ($t) => Agent::effective($t) !== $me);
                if ($busy->isNotEmpty()) {
                    $this->line('🔒 busy elsewhere: '.$busy->map(fn ($t) => sprintf('%s on «%s» (id:%d)', Agent::label(Agent::effective($t)), $t->title, $t->id))->implode(' · ')
                        .' — stay out of those files and branches, and run the shared steps (package release, asset build, migrations, caches) one agent at a time');
                }
            }
            if ($opt->terse_agent) {
                $this->line('⚡ '.$opt->terseRules());
            }
            $this->info(sprintf('List "%s": %d items %s', $name, $todos->count(), $this->option('all') ? 'in total' : 'open to work 🟢 (in list order = priority)'));
            if (! $this->option('all')) {
                $waiting = $list->todos()->whereNull('archived_at')->where('completed', false)->where('open_to_work', false)->where('working', false)->where('paused', false)->where('question', false)->count();
                if ($waiting) {
                    $this->line("   (+{$waiting} open but not yet open to work: do not touch them)");
                }
                $asking = $list->todos()->whereNull('archived_at')->where('completed', false)->where('question', true)->count();
                if ($asking) {
                    $this->line("   (+{$asking} waiting for the user's answers ❓)");
                }
                $paused = $list->todos()->whereNull('archived_at')->where('completed', false)->where('paused', true)->count();
                if ($paused) {
                    $this->line("   (+{$paused} paused by the agent ⏸ — reopen on the board to resume)");
                }
            }

            $render = function ($todos) use ($last, $opt) {
                foreach ($todos as $t) {
                    $isNew = $t->updated_at->timestamp > $last;
                    $this->line(sprintf('%s [%s] %s #%d %s%s%s  (id:%d)', $isNew ? '🆕' : '  ', $t->completed ? 'x' : ' ', $t->question ? '❓' : ($t->paused ? '⏸' : ($t->working ? '🔧' : ($t->open_to_work ? '🟢' : '⚪'))), $t->order, $t->title, $t->working && $t->progress !== null ? sprintf(' [%d%%%s]', $t->progress, $t->phase ? ' · '.$t->phase : '') : '', Agent::many() ? ' {agent: '.Agent::effective($t).'}' : '', $t->id));
                    // Resume chain: a resumed task can itself be resumed — print the WHOLE history, newest first (task 416)
                    $chain = $t->resumeChain();
                    if ($chain->isNotEmpty()) {
                        if ($chain->count() > 1) {
                            $this->line(sprintf('        ↩ resume chain: %d previous tasks, newest first — the whole history still applies', $chain->count()));
                        }
                        foreach ($chain as $step => $p) {
                            $this->line($step === 0
                                ? sprintf('        ↩ resumes «%s» (id:%d): the previous context still applies', $p->title, $p->id)
                                : sprintf('        ↩ %d steps back «%s» (id:%d)', $step + 1, $p->title, $p->id));
                            if ($p->notes) {
                                $this->line('           previous note: '.str_replace("\n", "\n              ", $opt->trim($p->notes)));
                            }
                            if ($p->claude_comment) {
                                $this->line('           🤖 previous: '.str_replace("\n", "\n              ", $opt->trim($p->claude_comment)));
                            }
                            foreach ($p->ingredients as $i) {
                                $this->line(sprintf('           - [%s] %s', $i->checked ? 'x' : ' ', $i->name));
                            }
                        }
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
                    if ($t->completed && in_array($t->outcome, ['alert', 'blocked'], true)) {
                        $this->line('        '.trim(self::outcomeMark($t->outcome)));
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
        // that is waiting for the board — say it out loud, with the way out (task 347). Never in --json or
        // --worker-json: a line after the document broke the worker's parser (task 507).
        foreach ($machine ? collect() : $planLists as $pl) {
            $pending = $pl->todos()->whereNull('archived_at')->where('completed', false)->count();
            $openable = $pl->todos()->whereNull('archived_at')->where('completed', false)
                ->where(fn ($q) => $q->where('open_to_work', true)->orWhere('working', true)->orWhere('paused', true)->orWhere('question', true))->count();
            // A plan nobody has started yet is not a dead end: it is simply waiting for ▶. Warn only about
            // plans that were started (or paused) and now have nothing the agent may take.
            $started = $pl->plan_paused || $pl->todos()->whereNull('archived_at')->where('completed', true)->exists();

            if ($started && $pending > 0 && $openable === 0) {
                $this->warn(sprintf('⚠ Plan «%s» (list id:%d): %d task(s) left but none is open to work%s — start it with ▶ on the board, or open one by hand.',
                    $pl->name, $pl->id, $pending, $pl->plan_paused ? ' (the plan is paused)' : ''));
            }
        }

        file_put_contents($marker, (string) now()->timestamp);

        return self::SUCCESS;
    }

    /** Short marker printed next to a closed task: nothing when the result is plain «ok». */
    private static function outcomeMark(?string $outcome): string
    {
        return match ($outcome) {
            'alert' => ' ⚠ alert: the result needs a look',
            'blocked' => ' ⛔ blocked: something is in the way',
            default => '',
        };
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
