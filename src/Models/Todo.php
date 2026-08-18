<?php

namespace Alle80\Devboard\Models;

use Alle80\Devboard\Support\Live;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Todo extends Model
{
    protected $fillable = ['title', 'order', 'completed', 'open_to_work', 'working', 'stopped_at', 'question', 'notes', 'claude_comment', 'archived_at', 'checklist_id', 'parent_id'];

    protected function casts(): array
    {
        return [
            'completed' => 'boolean',
            'open_to_work' => 'boolean',
            'working' => 'boolean',
            'question' => 'boolean',
            'archived_at' => 'datetime',
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

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('order')->orderBy('id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class)->orderBy('id');
    }

    protected static function booted(): void
    {
        // Eliminando un todo vanno eliminati anche i file allegati (la FK cancella solo i record)
        static::deleting(fn (Todo $todo) => $todo->attachments->each->delete());

        // Aggiornamento live delle pagine aperte (Reverb)
        static::saved(fn (Todo $todo) => Live::todoChanged($todo, stateChanged: $todo->wasChanged(['completed', 'open_to_work', 'working', 'question'])));
        static::deleted(fn (Todo $todo) => Live::todoChanged($todo, deleted: true));
    }
}
