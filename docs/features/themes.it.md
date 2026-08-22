# Temi

I temi generici sono rivestimenti fatti di variabili CSS (`.theme-<slug>`); quello integrato è `slate`, altri
si registrano nella config (`themes`) o nel codice (`Themes::registerTheme`). I **pacchetti di temi** sono zip
(`theme.json` + `theme.css` + immagini/font) installati da Impostazioni → Temi (solo amministratori) oppure con:

Seleziona il tema attivo in **Impostazioni → App → Tema**. La board principale resta su `/` (sotto il prefisso configurato); i temi non creano rotte `/<slug>`. La vista desktop più larga resta esclusivamente su `/dashboard` (o `dashboard_route`).

```bash
php artisan griglia:theme-import pack.zip
php artisan griglia:theme-export slug --css-from=…
```

I pacchetti sono trattati come contenuto non fidato: niente SVG, CSS ripulito (niente `@import` né url
esterni), limiti di dimensione e di numero di file, asset serviti in un contesto isolato.

## Scrivere un pacchetto

Un pacchetto è uno zip con `theme.json` (slug, etichetta, versione, autore, font facoltativi), `theme.css` con
un unico blocco `.theme-<slug> { --tl-…: … }` e una cartella `images/` facoltativa. Il modo più rapido di
cominciare è esportare un tema esistente e modificarlo:

```bash
php artisan griglia:theme-export slate --css-from=resources/css/app.css
```

Nel repository, dentro `resources/themes/`, c'è un pacchetto di esempio (`pollon`).

## Testi e lingue

Oltre ai colori, un tema definisce le poche parole che la board stampa: `claim` e `footer` (testa e piede
della pagina), `counter` («3/5 *fatti*»), `done_all`, `add` (il bottone che apre il form di inserimento),
`stamp` (sui task completati), `confirm` (la domanda di eliminazione; `:title` viene sostituito) e
`placeholder` (form di inserimento e sotto-task). Ognuna può essere:

- una **chiave di traduzione** — lo Slate integrato usa `griglia::t.theme.add`,
  `griglia::t.theme.placeholder`, … così i suoi testi seguono la
  [lingua della board](../configuration/index.md#la-lingua-della-board) (un `lang/vendor/griglia/<locale>/t.php`
  pubblicato può riformularli);
- un **testo letterale** — usato così com'è, nella lingua in cui è scritto (il `pollon` di esempio parla solo
  italiano);
- una **mappa per lingua** — `{"en": "add", "it": "aggiungi"}`: la board sceglie la lingua corrente, poi
  `app.fallback_locale`, poi la prima voce.

I testi che un pacchetto non definisce ricadono su quelli tradotti di Slate. `griglia:theme-export` scrive le
chiavi di un tema integrato come mappe per lingua, così il `theme.json` esportato si legge e resta bilingue
una volta importato. Una voce di `config('griglia.themes')` o di `Themes::registerTheme()` con lo slug di un
tema integrato lo sovrascrive **chiave per chiave**: `['slate' => ['icon_img' => '/images/slate.svg']]` cambia
l'icona e tiene i testi tradotti.

## Vedi anche

- [Sicurezza](../operations/security.md) — perché i pacchetti sono trattati come contenuto non fidato.
- [Asset front-end](../getting-started/assets.md) — da dove viene caricato il CSS del tema.
