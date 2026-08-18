<?php

namespace Alle80\Devboard\Models;

use Alle80\Devboard\Support\Live;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Question extends Model
{
    protected $fillable = ['todo_id', 'question', 'answer', 'order'];

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
