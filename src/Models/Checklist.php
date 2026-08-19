<?php

namespace Alle80\Devboard\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Checklist extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'user_id', 'plan_prompt', 'plan_paused', 'agent'];

    protected static function booted(): void
    {
        // Deleting a list carries its tasks along: soft → soft (stats keep reading them, task 298),
        // force → force (so the attachment files are cleaned up too; the FK alone would leave them).
        static::deleting(function (Checklist $list) {
            $list->todos()->withTrashed()->get()
                ->each(fn (Todo $t) => $list->isForceDeleting() ? $t->forceDelete() : $t->delete());
        });
    }

    protected function casts(): array
    {
        return ['plan_paused' => 'boolean'];
    }

    public function todos(): HasMany
    {
        return $this->hasMany(Todo::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('devboard.user_model', 'App\\Models\\User'));
    }

    /** Solo le liste dell'utente autenticato. */
    public static function mine(): Builder
    {
        // Local mode: one global set of lists (no users); server mode: the logged-in user's lists
        return \Alle80\Devboard\Mode::isLocal() ? static::query() : static::where('user_id', auth()->id());
    }

    /** Id della lista corrente dell'utente (dalla sessione, con fallback alla prima sua lista). */
    public static function currentId(): int
    {
        $id = session('checklist_id');

        if ($id && static::mine()->whereKey($id)->exists()) {
            return (int) $id;
        }

        $first = static::mine()->orderBy('id')->first()
            ?? static::create(['name' => __('devboard::t.default_list'), 'user_id' => auth()->id()]);

        session(['checklist_id' => $first->id]);

        return $first->id;
    }
}
