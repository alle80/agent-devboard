<?php

namespace Alle80\Griglia\Http\Controllers;

use Alle80\Griglia\Models\Attachment;
use Alle80\Griglia\Models\Checklist;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Serves an attachment only to a user who may see its list (so the disk can be private). */
class AttachmentController
{
    public function __invoke(int $attachment): StreamedResponse
    {
        $a = Attachment::findOrFail($attachment);
        $list = $a->todo?->checklist;
        abort_unless($list && Checklist::mine()->whereKey($list->id)->exists(), 404, __('griglia::t.errors.not_found'));
        $disk = Storage::disk(config('griglia.attachments_disk', 'local'));
        abort_unless($disk->exists($a->path), 404, __('griglia::t.errors.not_found'));

        return $disk->response($a->path, null, [
            'Content-Type' => $a->mime ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }
}
