<?php

namespace Alle80\Griglia\Support;

use Alle80\Griglia\Ai\Agents\ImageDescriber;
use Alle80\Griglia\Models\Attachment;
use Alle80\Griglia\Settings\AppSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Ai\Files\Image;

/**
 * Servizio di descrizione immagini: usa il Laravel AI SDK con il provider
 * configurato (config ai.image_description / ai.default), con failover se
 * ne sono indicati più d'uno. Se nessun provider ha una chiave, è un no-op.
 */
class ImageDescription
{
    /** Provider da usare, in ordine di preferenza (il primo è il primario, gli altri failover). */
    public static function providers(): array
    {
        $settings = app(AppSettings::class);
        if (! $settings->ai_describe_images) {
            return [];
        }

        // Provider scelto da /settings, altrimenti da .env (AI_IMAGE_PROVIDERS con failover, poi AI_PROVIDER)
        $configured = $settings->ai_image_provider !== '' ? [$settings->ai_image_provider] : config('ai.image_description.providers', []);
        $candidates = $configured ?: [config('ai.default')];

        // Tiene solo i provider che hanno effettivamente una chiave (o che non ne richiedono, es. ollama)
        return array_values(array_filter($candidates, function ($name) {
            $p = config("ai.providers.{$name}");

            return $p && (($p['driver'] ?? '') === 'ollama' || ! empty($p['key']));
        }));
    }

    public static function enabled(): bool
    {
        return self::providers() !== [];
    }

    public static function describe(Attachment $attachment): ?string
    {
        if (! self::enabled() || ! Storage::disk(config('griglia.attachments_disk', 'public'))->exists($attachment->path)) {
            return null;
        }

        try {
            $providers = self::providers();
            $model = app(AppSettings::class)->ai_image_model ?: (config('ai.image_description.model') ?: null);

            $response = (new ImageDescriber)->prompt(
                'Describe this image.',
                attachments: [Image::fromStorage($attachment->path, config('griglia.attachments_disk', 'public'))],
                provider: count($providers) > 1 ? $providers : $providers[0],
                model: $model,
            );

            $text = trim((string) $response);

            if ($text === '') {
                return null;
            }

            $attachment->update(['description' => $text]);

            return $text;
        } catch (\Throwable $e) {
            Log::warning('ImageDescription: descrizione fallita', ['attachment' => $attachment->id, 'error' => $e->getMessage()]);

            return null;
        }
    }
}
