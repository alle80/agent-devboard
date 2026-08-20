# AI features

Every AI feature is **optional** and goes through [`laravel/ai`](https://laravel.com/docs), so any provider
it supports works. With no AI configured the board simply skips them.

```bash
composer require laravel/ai
```

## Image descriptions

Attachments can be described automatically, and the description feeds the search — so «the screenshot with
the red error» becomes findable.

- Settings → App: `ai_describe_images`, `ai_image_provider`, `ai_image_model`.
- Backfill or regenerate from the CLI:

```bash
php artisan griglia:describe-images            # only the ones without a description
php artisan griglia:describe-images --all      # regenerate everything
```

## Speech to text

Every text field has a microphone:

- with `laravel/ai` and a transcription provider the clip is transcribed **server-side** (best quality);
- otherwise the browser's **Web Speech API** is used;
- `speech_mode` chooses: `auto`, `server`, `browser`.

Server-side transcription sends a short **vocabulary hint** with the audio, so names and jargon come out
right («l'agente», not «la gente»). It is translated with the app locale; override it with
`GRIGLIA_SPEECH_PROMPT` (or `config('griglia.speech_prompt')`), and set it to an empty string to send no
hint at all.

## Plan builder

Creating a list **as a plan** sends your prompt to the AI, which splits the goal into chained tasks
(`depends_on_id`): closing one opens the next. Without AI the list is created with a single task holding
the prompt, so the agent can split it itself. See [Plans](plans.md).
