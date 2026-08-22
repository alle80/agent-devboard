<?php

namespace Alle80\Griglia\Http\Controllers;

use Alle80\Griglia\Support\Speech;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Files\Base64Audio;
use Laravel\Ai\Transcription;

/** Server-side speech to text: the browser uploads a short recording, the AI SDK transcribes it. */
class TranscribeController
{
    public function __invoke(Request $request): JsonResponse
    {
        if (! Speech::serverAvailable()) {
            return response()->json(['ok' => false, 'error' => 'transcription not configured'], 422);
        }
        $request->validate([
            'audio' => ['required', 'file', 'mimetypes:audio/webm,video/webm,audio/ogg,audio/mp4,audio/mpeg,audio/wav,audio/x-wav,application/ogg', 'max:25600'], // 25 MB (provider limit)
            'lang' => ['nullable', 'string', 'max:8'],
        ]);
        $file = $request->file('audio');
        $lang = substr((string) ($request->input('lang') ?: Speech::language()), 0, 2);
        $mime = Speech::audioMime($file->getClientMimeType(), $file->getClientOriginalName());
        $prompt = Speech::prompt();

        try {
            $audio = Base64Audio::fromUpload($file, $mime);
            // A long dictation is a big file: a fixed short timeout threw away five minutes of talking.
            $pending = Transcription::of($audio)->language($lang)
                ->timeout($file->getSize() > 2_000_000 ? 180 : 90);

            if ($prompt !== '' && method_exists($pending, 'withProviderOptions')) {
                $pending = $pending->withProviderOptions(['prompt' => $prompt]);
            }

            $text = (string) $pending->generate();
        } catch (\Throwable $e) {
            // Details in the log only (the browser gets a generic message), but enough to tell a provider
            // failure from an audio the browser recorded badly: size and mime are half the diagnosis.
            Log::warning('griglia: transcription failed: '.$e->getMessage(), [
                'bytes' => $file->getSize(),
                'mime' => $mime,
                'client_mime' => $file->getClientMimeType(),
                'lang' => $lang,
            ]);

            return response()->json(['ok' => false, 'error' => __('griglia::t.mic_error')], 502); // details only in the log
        }

        return response()->json(['ok' => true, 'text' => trim($text)]);
    }
}
