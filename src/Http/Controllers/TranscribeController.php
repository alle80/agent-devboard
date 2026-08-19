<?php

namespace Alle80\Devboard\Http\Controllers;

use Alle80\Devboard\Support\Speech;
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

        try {
            $text = (string) \Laravel\Ai\Transcription::fromUpload($file)->language($lang)->timeout(90)->generate();
        } catch (\Throwable $e) {
            Log::warning('devboard: transcription failed: '.$e->getMessage());

            return response()->json(['ok' => false, 'error' => $e->getMessage()], 502);
        }

        return response()->json(['ok' => true, 'text' => trim($text)]);
    }
}
