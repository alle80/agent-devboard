<?php

namespace Alle80\Griglia\Ai\Agents;

use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Descrive un'immagine allegata a un todo per renderla ricercabile a testo libero.
 *
 * Provider e modello NON sono fissati qui: arrivano da config('ai.image_description')
 * (env AI_IMAGE_PROVIDERS / AI_IMAGE_MODEL) e in mancanza dal provider di default
 * dell'SDK, col modello più economico del provider. Vedi Alle80\Griglia\Support\ImageDescription.
 */
#[UseCheapestModel]
#[MaxTokens(300)]
#[Timeout(60)]
class ImageDescriber implements Agent
{
    use Promptable;

    public function instructions(): Stringable|string
    {
        $lang = match (app()->getLocale()) { 'it' => 'Italian', 'en' => 'English', default => app()->getLocale() };

        return <<<TXT
        You describe images for a searchable archive.
        Answer only with the description, in {$lang}, in 2-3 sentences: what the image shows,
        main objects and subjects, places, dominant colours, and transcribe any readable text.
        Do not identify people. No preamble, no bullet points.
        TXT;
    }
}
