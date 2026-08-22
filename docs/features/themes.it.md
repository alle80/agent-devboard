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

## Vedi anche

- [Sicurezza](../operations/security.md) — perché i pacchetti sono trattati come contenuto non fidato.
- [Asset front-end](../getting-started/assets.md) — da dove viene caricato il CSS del tema.
