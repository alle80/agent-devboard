<?php

namespace Alle80\Devboard\Models;

use Alle80\Devboard\Support\Live;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Todo extends Model
{
    use SoftDeletes;

    protected $fillable =['title', 'order', 'completed', 'completed_at', 'open_to_work', 'working', 'stopped_at', 'question', 'notes', 'claude_comment', 'result_seen', 'progress', 'phase', 'working_since', 'work_seconds', 'tokens_in', 'tokens_out', 'skills', 'agent', 'archived_at', 'checklist_id', 'parent_id', 'depends_on_id'];

    protected function casts(): array
    {
        return [
            'completed' => 'boolean',
            'open_to_work' => 'boolean',
            'working' => 'boolean',
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
        ];
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
        if ($h > 0) return sprintf('%dh %02dm', $h, $m);
        if ($m > 0) return sprintf('%dm %02ds', $m, $s);
        return sprintf('%ds', $s);
    }

    /** "1.2M", "45k", "812". */
    public static function formatTokens(int $n): string
    {
        if ($n >= 1_000_000) return rtrim(rtrim(number_format($n / 1_000_000, 1, '.', ''), '0'), '.').'M';
        if ($n >= 1_000) return rtrim(rtrim(number_format($n / 1_000, 1, '.', ''), '0'), '.').'k';
        return (string) $n;
    }

    /** Estimated cost from the price list in AppSettings (null when no prices or no tokens). */
    public function cost(): ?float
    {
        return \Alle80\Devboard\Support\Stats::cost((int) $this->tokens_in, (int) $this->tokens_out);
    }

    /** One-line summary for CLI/UI: "⏱ 1h 12m · 🪙 1.2M in / 12k out". */
    public function statsLine(): string
    {
        $parts = [];
        if ($this->workSeconds() > 0) $parts[] = '⏱ '.self::formatDuration($this->workSeconds());
        if ($this->tokens_in > 0 || $this->tokens_out > 0) $parts[] = '🪙 '.self::formatTokens((int) $this->tokens_in).' in / '.self::formatTokens((int) $this->tokens_out).' out';
        return implode(' · ', $parts);
    }

    protected static function booted(): void
    {
        // Plan lists: a new task joins the chain (depends on the previous task by order) unless told otherwise
        static::creating(function (Todo $todo) {
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

        // History: completed_at follows the `completed` flag (set when it becomes true, cleared when reopened)
        static::saving(function (Todo $todo) {
            if ($todo->isDirty('completed')) {
                $todo->completed_at = $todo->completed ? now() : null;
            }
        });

        // Statistics: every 🔧 interval is timed, whatever flips `working` (CLI take/done/ask, user stop from the web)
        static::saving(function (Todo $todo) {
            if (! $todo->isDirty('working')) return;
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
                $todo->dependents()->where('completed', false)->where('open_to_work', false)->where('working', false)->where('question', false)->whereNull('archived_at')
                    ->get()->each(fn (Todo $next) => $next->update(['open_to_work' => true, 'stopped_at' => null]));
            }
        });

        // Aggiornamento live delle pagine aperte (Reverb)
        static::saved(fn (Todo $todo) => Live::todoChanged($todo, stateChanged: $todo->wasChanged(['completed', 'open_to_work', 'working', 'question'])));
        static::deleted(fn (Todo $todo) => Live::todoChanged($todo, deleted: true));
    }
}
