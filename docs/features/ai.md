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

### Nothing is lost

Dictating into a Livewire page means the field can be re-rendered while you talk (another device, the agent
updating a task). The recording lives outside the component, so a re-render no longer kills it silently, and:

- the button counts the **elapsed time** while it records, so you can see it is really recording;
- it turns amber and says so when the **microphone hears nothing** — instead of letting you talk for five
  minutes to a dead mic;
- a **failed transcription keeps its audio**: tap the microphone again to retry (it also retries by itself
  when you come back to the tab). The error stays on the button until you do something about it;
- a transcript that finds **no field to write into** (modal closed mid-transcription) is kept and inserted
  when the field comes back;
- leaving the page while a dictation is running (or still to be transcribed) asks for confirmation;
- a dictation is closed and transcribed after `speech_max_seconds` seconds (default 300, `0` = no limit,
  `GRIGLIA_SPEECH_MAX_SECONDS`), so a single upload never grows past what the provider accepts.

## Plan builder

The **New plan…** page sends your goal to the AI, which splits it into chained tasks (`depends_on_id`):
closing one opens the next. Without AI the list is created with a single task holding
the prompt, so the agent can split it itself. See [Plans](plans.md).

## See also

- [Configuration file](../reference/config.md) · [Settings](../reference/settings.md)
