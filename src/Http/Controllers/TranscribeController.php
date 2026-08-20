<?php

namespace Alle80\Griglia\Http\Controllers;

use Alle80\Griglia\Support\Speech;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/** Server-side speech to text: the browser uploads a short recording, the AI SDK transcribes it. */
class TranscribeController
{
    public function __invoke(Request $request): JsonResponse
    {
        if (! Speech::serverAvailable()) {
            return response()->json(['ok' => false, 'error' => 'transcription not configured'], 422);
        }
        $request->validate([
            'audio' => ['required', 'file', 'max:25600'], // 25 MB (provider limit)
            'lang' => ['nullable', 'string', 'max:8'],
        ]);
        $file = $request->file('audio');
        $lang = substr((string) ($request->input('lang') ?: Speech::language()), 0, 2);
        $mime = Speech::audioMime($file->getClientMimeType(), $file->getClientOriginalName());
        $prompt = Speech::prompt();

        try {
            $audio = \Laravel\Ai\Files\Base64Audio::fromUpload($file, $mime);
            $pending = \Laravel\Ai\Transcription::of($audio)->language($lang)->timeout(90);

            if ($prompt !== '' && method_exists($pending, 'withProviderOptions')) {
                $pending = $pending->withProviderOptions(['prompt' => $prompt]);
            }

            $text = (string) $pending->generate();
        } catch (\Throwable $e) {
            Log::warning('devboard: transcription failed: '.$e->getMessage());

            return response()->json(['ok' => false, 'error' => __('griglia::t.mic_error')], 502); // details only in the log
        }

        return response()->json(['ok' => true, 'text' => trim($text)]);
    }
}
