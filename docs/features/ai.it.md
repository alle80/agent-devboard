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

### Non si perde niente

Si detta dentro una pagina Livewire, quindi il campo può essere ri-renderizzato mentre parli (un altro
dispositivo, l'agente che aggiorna un task). La registrazione vive fuori dal componente, così un
ri-render non la uccide più in silenzio, e:

- il pulsante mostra i **secondi trascorsi** mentre registra: si vede che sta davvero registrando;
- diventa ambra e lo dice quando il **microfono non sente nulla**, invece di lasciarti parlare cinque
  minuti a un microfono morto;
- una **trascrizione fallita non butta via l'audio**: tocca di nuovo il microfono per riprovare (ci
  riprova anche da solo quando torni sulla scheda). L'errore resta sul pulsante finché non fai qualcosa;
- un testo trascritto che **non trova più il campo** (modale chiuso durante la trascrizione) viene messo
  da parte e inserito quando il campo torna;
- lasciare la pagina con una dettatura in corso (o ancora da trascrivere) chiede conferma;
- la dettatura viene chiusa e trascritta dopo `speech_max_seconds` secondi (default 300, `0` = nessun
  limite, `GRIGLIA_SPEECH_MAX_SECONDS`): un solo upload non supera mai quello che il provider accetta.

## Costruttore di piani

La pagina **Nuovo piano…** manda il tuo obiettivo all'AI, che lo spezza in task concatenati
(`depends_on_id`): chiuderne uno apre il successivo. Senza AI la lista viene creata con un unico task che
contiene il prompt, così l'agente può spezzarlo da sé. Vedi [Piani](plans.md).

## Vedi anche

- [File di configurazione](../reference/config.md) · [Impostazioni](../reference/settings.md)
