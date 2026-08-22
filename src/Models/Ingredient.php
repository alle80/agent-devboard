<?php

namespace Alle80\Griglia\Models;

use Alle80\Griglia\Database\Factories\IngredientFactory;
use Alle80\Griglia\Support\Live;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ingredient extends Model
{
    /** @use HasFactory<IngredientFactory> */
    use HasFactory;

    protected static function newFactory(): IngredientFactory
    {
        return IngredientFactory::new();
    }

    protected $fillable = ['todo_id', 'name', 'checked', 'order'];

    protected function casts(): array
    {
        return [
            'checked' => 'boolean',
            'order' => 'integer',
        ];
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
