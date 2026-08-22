<?php

namespace Alle80\Griglia\Models;

use Alle80\Griglia\Support\Live;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Question extends Model
{
    protected $fillable = ['todo_id', 'question', 'choices', 'answer', 'order'];

    protected function casts(): array
    {
        return ['choices' => 'array'];
    }

    public function todo(): BelongsTo
    {
        return $this->belongsTo(Todo::class);
    }
    protected static function booted(): void
    {
        // Aggiornamento live della lista/modale aperti altrove (Reverb)
        static::saved(fn ($m) => $m->todo && Live::todoChanged($m->todo));
        static::deleted(fn ($m) => $m->todo && Live::todoChanged($m->todo));
    }
}
