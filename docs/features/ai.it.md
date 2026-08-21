# Funzioni AI

Ogni funzione AI è **facoltativa** e passa da [`laravel/ai`](https://laravel.com/docs), quindi va bene
qualunque provider supportato. Senza AI configurata la board semplicemente le salta.

```bash
composer require laravel/ai
```

## Descrizione delle immagini

Gli allegati possono essere descritti in automatico, e la descrizione alimenta la ricerca — così «lo
screenshot con l'errore rosso» diventa una cosa che si trova.

- Impostazioni → App: `ai_describe_images`, `ai_image_provider`, `ai_image_model`.
- Per riempire il pregresso o rigenerare, dalla riga di comando:

```bash
php artisan griglia:describe-images            # solo quelle senza descrizione
php artisan griglia:describe-images --all      # rigenera tutto
```

## Dettatura vocale (speech to text)

Ogni campo di testo ha il suo microfono:

- con `laravel/ai` e un provider di trascrizione la registrazione viene trascritta **lato server** (qualità
  migliore);
- altrimenti si usa la **Web Speech API** del browser;
- `speech_mode` sceglie: `auto`, `server`, `browser`.

La trascrizione lato server manda insieme all'audio un breve **suggerimento di vocabolario**, così nomi e gergo
escono giusti («l'agente», non «la gente»). È tradotto con la lingua dell'applicazione; puoi cambiarlo con
`GRIGLIA_SPEECH_PROMPT` (o `config('griglia.speech_prompt')`), e metterlo a stringa vuota per non mandare
alcun suggerimento.

## Costruttore di piani

La pagina **Nuovo piano…** manda il tuo obiettivo all'AI, che lo spezza in task concatenati
(`depends_on_id`): chiuderne uno apre il successivo. Senza AI la lista viene creata con un unico task che
contiene il prompt, così l'agente può spezzarlo da sé. Vedi [Piani](plans.md).

## Vedi anche

- [File di configurazione](../reference/config.md) · [Impostazioni](../reference/settings.md)
