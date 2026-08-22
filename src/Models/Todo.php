<?php

namespace Alle80\Griglia\Models;

use Alle80\Griglia\Agent;
use Alle80\Griglia\Domain\ReviewOutcome;
use Alle80\Griglia\Domain\ReviewStatus;
use Alle80\Griglia\Support\Live;
use Alle80\Griglia\Support\Stats;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

class Todo extends Model
{
    use SoftDeletes;

    protected $fillable = ['title', 'order', 'completed', 'completed_at', 'open_to_work', 'working', 'paused', 'stopped_at', 'question', 'notes', 'claude_comment', 'result_summary', 'result_seen', 'outcome', 'progress', 'phase', 'working_since', 'work_seconds', 'tokens_in', 'tokens_out', 'skills', 'agent', 'reviewer_agent', 'review_of_id', 'review_round', 'review_status', 'review_outcome', 'archived_at', 'checklist_id', 'parent_id', 'depends_on_id'];

    protected function casts(): array
    {
        return [
            'completed' => 'boolean',
            'open_to_work' => 'boolean',
            'working' => 'boolean',
            'paused' => 'boolean',
            'question' => 'boolean',
            'result_seen' => 'boolean',
            'progress' => 'integer',
            'working_since' => 'datetime',
            'work_seconds' => 'integer',
            'tokens_in' => 'integer',
            'tokens_out' => 'integer',
            'skills' => 'array',
            'archived_at' => 'datetime',
            'completed_at' => 'datetime',
            'stopped_at' => 'datetime',
            'order' => 'integer',
            'review_round' => 'integer',
            'review_status' => ReviewStatus::class,
            'review_outcome' => ReviewOutcome::class,
        ];
    }

    /** What the agent reports when it closes a task (griglia:check --done --outcome=…): how much attention the result needs. */
    public const OUTCOMES = ['ok', 'alert', 'blocked'];

    /**
     * How loudly this row should ask for the user's attention, null = not at all.
     * 'question' while the agent waits for answers; on a fresh result the user has not opened yet,
     * the outcome reported by the agent ('blocked', 'alert', or 'ok' when there is nothing to check).
     * Once the result is opened (result_seen) the row goes back to its usual look.
     */
    public function attention(): ?string
    {
        if ($this->question) {
            return 'question';
        }
        if (! $this->completed || $this->result_seen) {
            return null;
        }

        return in_array($this->outcome, ['alert', 'blocked'], true) ? $this->outcome : 'ok';
    }

    /**
     * Colour of each attention level. The row paints it inline (see the board view): the border must be
     * visible even when the host app's compiled CSS is older than the package — the views come from
     * `vendor/`, the stylesheet from a build step, and the two drifted apart for three releases in a row
     * (tasks 397, 402, 406), so the border simply never showed up.
     */
    public const ATTENTION_COLORS = [
        'ok' => '#22c55e',        // done, nothing to check
        'alert' => '#eab308',     // done, but look at it
        'blocked' => '#ef4444',   // something is in the way
        'question' => '#a78bfa',  // the agent is waiting for answers
    ];

    /** Hex colour of this row's attention level, null when the row asks for nothing. */
    public function attentionColor(): ?string
    {
        return self::ATTENTION_COLORS[$this->attention()] ?? null;
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(Ingredient::class)->orderBy('order')->orderBy('id');
    }

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(Checklist::class);
    }

    /** Todo chiuso da cui questo è stato «ripreso» (ne porta il contesto). */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Todo::class, 'parent_id');
    }

    /**
     * Catena dei «riprendi»: tutti i todo da cui questo discende, dal più recente al più vecchio
     * (parent, nonno, …). Un resume può nascere da un resume: l'agente deve avere tutto lo storico,
     * non solo l'ultimo passo (task 416). Protetta da cicli e da catene assurdamente lunghe.
     *
     * @return Collection<int, Todo>
     */
    public function resumeChain(int $max = 20): Collection
    {
        $chain = new Collection;
        $seen = [$this->id => true];
        $node = $this;

        while ($node->parent_id && $chain->count() < $max && ! isset($seen[$node->parent_id])) {
            $seen[$node->parent_id] = true;
            $next = $node->relationLoaded('parent') ? $node->getRelation('parent') : $node->parent()->with('ingredients')->first();
            if (! $next) {
                break;
            }
            $chain->push($next);
            $node = $next;
        }

        return $chain;
    }

    /** Todo aperti a partire da questo con «Riprendi». */
    public function followUps(): HasMany
    {
        return $this->hasMany(Todo::class, 'parent_id')->orderBy('id');
    }

    /** The task this one waits for (plan chain): it opens to work when that one is completed. */
    public function dependsOn(): BelongsTo
    {
        return $this->belongsTo(Todo::class, 'depends_on_id');
    }

    /** Tasks chained after this one. */
    public function dependents(): HasMany
    {
        return $this->hasMany(Todo::class, 'depends_on_id')->orderBy('order');
    }

    public function reviewOf(): BelongsTo
    {
        return $this->belongsTo(Todo::class, 'review_of_id');
    }

    public function reviewAttempts(): HasMany
    {
        return $this->hasMany(Todo::class, 'review_of_id')->orderBy('review_round')->orderBy('id');
    }

    public function isReviewAttempt(): bool
    {
        return $this->review_of_id !== null;
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('order')->orderBy('id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class)->orderBy('id');
    }

    /* ---------- Statistics: agent working time + tokens ---------- */

    /** Total working seconds, including the interval still open (if working now). */
    public function workSeconds(): int
    {
        return (int) $this->work_seconds + ($this->working && $this->working_since ? max(0, (int) $this->working_since->diffInSeconds(now())) : 0);
    }

    /** True when there is something to show (time or tokens). */
    public function hasStats(): bool
    {
        return $this->workSeconds() > 0 || $this->tokens_in > 0 || $this->tokens_out > 0;
    }

    /** "1h 12m", "4m 30s", "12s". */
    public static function formatDuration(int $seconds): string
    {
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;
        if ($h > 0) {
            return sprintf('%dh %02dm', $h, $m);
        }
        if ($m > 0) {
            return sprintf('%dm %02ds', $m, $s);
        }

        return sprintf('%ds', $s);
    }

    /** "1.2M", "45k", "812". */
    public static function formatTokens(int $n): string
    {
        if ($n >= 1_000_000) {
            return rtrim(rtrim(number_format($n / 1_000_000, 1, '.', ''), '0'), '.').'M';
        }
        if ($n >= 1_000) {
            return rtrim(rtrim(number_format($n / 1_000, 1, '.', ''), '0'), '.').'k';
        }

        return (string) $n;
    }

    /** Estimated cost from the price list in AppSettings (null when no prices or no tokens). */
    public function cost(): ?float
    {
        return Stats::cost((int) $this->tokens_in, (int) $this->tokens_out);
    }

    /** One-line summary for CLI/UI: "⏱ 1h 12m · 🪙 1.2M in / 12k out". */
    public function statsLine(): string
    {
        $parts = [];
        if ($this->workSeconds() > 0) {
            $parts[] = '⏱ '.self::formatDuration($this->workSeconds());
        }
        if ($this->tokens_in > 0 || $this->tokens_out > 0) {
            $parts[] = '🪙 '.self::formatTokens((int) $this->tokens_in).' in / '.self::formatTokens((int) $this->tokens_out).' out';
        }

        return implode(' · ', $parts);
    }

    /**
     * Give this task's dependents to its own predecessor. Without it, archiving or deleting a task of a
     * plan leaves the next one waiting for something that will never be completed: the agent has nothing
     * to do and the board shows no way out (task 347).
     */
    public function handOverChain(): void
    {
        $previous = $this->depends_on_id;
        $dependents = static::where('depends_on_id', $this->id)->whereNull('archived_at')->get();

        if ($dependents->isEmpty()) {
            return;
        }

        $predecessorDone = $previous === null || (bool) static::whereKey($previous)->value('completed');
        $paused = (bool) $this->checklist?->plan_paused;

        foreach ($dependents as $next) {
            $next->depends_on_id = $previous;

            if ($predecessorDone && ! $paused && ! $next->completed && ! $next->working && ! $next->question) {
                $next->open_to_work = true;
                $next->stopped_at = null;
            }

            $next->save();
        }
    }

    protected static function booted(): void
    {
        // Plan lists: a new task joins the chain (depends on the previous task by order) unless told otherwise
        static::creating(function (Todo $todo) {
            if ($todo->isReviewAttempt()) {
                return;
            }
            if ($todo->depends_on_id || ! $todo->checklist_id || $todo->archived_at) {
                return;
            }
            $list = Checklist::find($todo->checklist_id);
            $isPlan = $list && ($list->plan_prompt || static::where('checklist_id', $list->id)->whereNotNull('depends_on_id')->exists());
            if (! $isPlan) {
                return;
            }
            $prev = static::where('checklist_id', $list->id)->whereNull('archived_at')->where('order', '<', (int) $todo->order)->orderByDesc('order')->first()
                ?? static::where('checklist_id', $list->id)->whereNull('archived_at')->orderByDesc('order')->first();
            if ($prev && $prev->id !== $todo->id) {
                $todo->depends_on_id = $prev->id;
                // the previous task is already done → this one opens right away only if the plan is running
            }
        });

        // Review aggregate invariants check one record here; cross-row transitions live in ReviewWorkflow.
        static::saving(function (Todo $todo) {
            if ($todo->isReviewAttempt()) {
                if (! $todo->review_round || ! $todo->agent || $todo->reviewer_agent || $todo->parent_id || $todo->depends_on_id || $todo->review_status) {
                    throw new DomainException('Invalid review-attempt fields.');
                }
                if ((bool) $todo->completed !== ($todo->review_outcome !== null)) {
                    throw new DomainException('A review attempt is completed if and only if it has an outcome.');
                }
                if ($todo->exists && $todo->getOriginal('review_outcome') !== null && $todo->isDirty('review_outcome')) {
                    throw new DomainException('A review decision is immutable.');
                }
                if ($todo->exists && $todo->review_outcome === null && $todo->isDirty(['agent', 'checklist_id', 'archived_at'])) {
                    throw new DomainException('An active review attempt cannot be reassigned, moved, or archived.');
                }
            } else {
                if ($todo->review_round || $todo->review_outcome) {
                    throw new DomainException('Review rounds and outcomes belong only to review attempts.');
                }
                if ($todo->reviewer_agent && ! array_key_exists($todo->reviewer_agent, Agent::all())) {
                    throw new DomainException('The reviewer must be a configured agent.');
                }
                if ($todo->reviewer_agent && $todo->reviewer_agent === Agent::effective($todo)) {
                    throw new DomainException('An executor cannot review their own task.');
                }
                if ($todo->reviewer_agent && $todo->completed && $todo->review_status !== ReviewStatus::Approved) {
                    throw new DomainException('A reviewed task can be completed only after approval.');
                }
                if ($todo->review_status === ReviewStatus::Approved && ! $todo->completed) {
                    throw new DomainException('An approved task must be completed.');
                }
                if ($todo->review_status === ReviewStatus::InReview && ($todo->working || $todo->paused || $todo->open_to_work || $todo->question || $todo->completed)) {
                    throw new DomainException('A task in review cannot also be open, working, paused, questioned, or completed.');
                }
                if ($todo->exists && $todo->reviewAttempts()->whereNull('review_outcome')->exists()
                    && $todo->isDirty(['agent', 'reviewer_agent', 'checklist_id', 'archived_at'])) {
                    throw new DomainException('A task with an active review cannot be reassigned, moved, or archived.');
                }
            }
        });

        // History: completed_at follows the `completed` flag (set when it becomes true, cleared when reopened)
        static::saving(function (Todo $todo) {
            if ($todo->isDirty('completed')) {
                $todo->completed_at = $todo->completed ? now() : null;
            }
        });

        // Statistics: every 🔧 interval is timed, whatever flips `working` (CLI take/done/ask, user stop from the web)
        static::saving(function (Todo $todo) {
            if (! $todo->isDirty('working')) {
                return;
            }
            if ($todo->working) {
                $todo->working_since ??= now();
            } elseif ($todo->working_since) {
                $todo->work_seconds = (int) $todo->work_seconds + max(0, (int) $todo->working_since->diffInSeconds(now()));
                $todo->working_since = null;
            }
        });

        // Solo l'eliminazione DEFINITIVA rimuove i file allegati (il soft delete tiene tutto: le statistiche
        // continuano a leggere la riga — task 298). La FK cancella solo i record, i file vanno tolti qui.
        static::deleting(function (Todo $todo) {
            if ($todo->isForceDeleting()) {
                $todo->attachments->each->delete();
            }
        });

        // Plan chain: when a task gets completed, the tasks waiting for it become open to work 🟢
        static::saved(function (Todo $todo) {
            if ($todo->completed && $todo->wasChanged('completed') && ! ($todo->checklist?->plan_paused)) {
                $todo->dependents()->where('completed', false)->where('open_to_work', false)->where('working', false)->where('paused', false)->where('question', false)->whereNull('archived_at')
                    ->get()->each(fn (Todo $next) => $next->update(['open_to_work' => true, 'stopped_at' => null]));
            }

            // …and when a completed task is reopened, the tasks it had opened go back to waiting, unless
            // somebody already worked on them: otherwise the agent would run ahead of the task you reopened.
            if (! $todo->completed && $todo->wasChanged('completed')) {
                $todo->dependents()->where('completed', false)->where('open_to_work', true)->where('working', false)->where('paused', false)->where('question', false)
                    ->where('work_seconds', 0)->whereNull('archived_at')
                    ->get()->each(fn (Todo $next) => $next->update(['open_to_work' => false]));
            }
        });

        // A task that leaves the board (archived or deleted) must not leave the chain hanging behind it:
        // whoever depended on it inherits its own predecessor — and opens right away if that one is done.
        static::updated(function (Todo $todo) {
            if ($todo->wasChanged('archived_at') && $todo->archived_at) {
                $todo->handOverChain();
            }
        });

        static::deleting(fn (Todo $todo) => $todo->handOverChain());

        // Aggiornamento live delle pagine aperte (Reverb)
        static::saved(fn (Todo $todo) => Live::todoChanged($todo, stateChanged: $todo->wasChanged(['completed', 'open_to_work', 'working', 'paused', 'question'])));
        static::deleted(fn (Todo $todo) => Live::todoChanged($todo, deleted: true));
    }
}
