<?php

namespace Alle80\Devboard\Models;

use Alle80\Devboard\Support\Live;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    protected $fillable = ['todo_id', 'path', 'original_name', 'description', 'mime', 'size', 'width', 'height'];

    protected static function booted(): void
    {
        // Aggiornamento live della lista/modale aperti altrove (Reverb)
        static::saved(fn ($m) => $m->todo && Live::todoChanged($m->todo));
        static::deleted(fn ($m) => $m->todo && Live::todoChanged($m->todo));

        // Cancellando il record sparisce anche il file
        static::deleted(fn (Attachment $a) => Storage::disk(config('devboard.attachments_disk', 'public'))->delete($a->path));
    }

    public function todo(): BelongsTo
    {
        return $this->belongsTo(Todo::class);
    }

    /** URL of the image: the authorised controller route (default) or the disk's public URL. */
    public function url(): string
    {
        if (config('devboard.attachments_via_controller', true) && \Illuminate\Support\Facades\Route::has('devboard.attachment')) {
            return route('devboard.attachment', $this->id);
        }

        return Storage::disk(config('devboard.attachments_disk', 'public'))->url($this->path);
    }
}
