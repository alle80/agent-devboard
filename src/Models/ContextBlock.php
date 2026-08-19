<?php

namespace Alle80\Devboard\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One switchable piece of the agent's context (a bullet / paragraph / sub-section in markdown). */
class ContextBlock extends Model
{
    protected $fillable = ['group_id', 'title', 'body', 'order', 'enabled'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'order' => 'integer'];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ContextGroup::class, 'group_id');
    }

    /** Rough token estimate (≈ 4 characters per token). */
    public function tokens(): int
    {
        return (int) ceil(mb_strlen($this->body) / 4);
    }
}
