# Traduzioni

Il sito è bilingue: **la lingua base è l'inglese**, l'italiano è la traduzione. In questo repository ci sono
due cose diverse che si chiamano «traduzioni», e non si toccano fra loro:

| | Il sito | La board |
|---|---|---|
| Cosa | queste pagine di documentazione | le stringhe dell'interfaccia Livewire |
| Dove | `docs/**/pagina.it.md` | `resources/lang/{en,it}/t.php` |
| Chi controlla | `mkdocs build --strict` | `TranslationsTest` (stesse chiavi nei due file) |

Questa pagina parla della prima. Per la seconda vedi [Contribuire](contributing.md).

## Come funziona una pagina tradotta

Il sito usa [mkdocs-static-i18n](https://ultrabug.github.io/mkdocs-static-i18n/) con la struttura a
**suffisso**: la versione italiana di `board/usage.md` è `board/usage.it.md`, nella stessa cartella.

- L'inglese si serve da `/`, l'italiano da `/it/`.
- Una pagina senza `.it.md` **ripiega** su quella inglese: una traduzione mancante non rompe mai un link,
  lascia solo quella pagina in inglese.
- Il selettore di lingua nell'intestazione porta alla *stessa pagina* nell'altra lingua.
- I titoli della navigazione non stanno nelle pagine, ma in `mkdocs.yml` →
  `plugins.i18n.languages.it.nav_translations` (con la chiave in inglese). Una voce nuova nella nav vuole
  anche la sua riga lì.

## Scriverne una

1. Copia la pagina inglese accanto a sé stessa con il suffisso `.it.md` e traducila.
2. **Lascia i link relativi come sono** (`../features/plans.md`): il plugin li fa puntare da solo alla
   versione italiana, quando c'è.
3. **Le ancore non si traducono da sole.** Un link a un titolo (`installation.md#web-push-optional`) nella
   pagina italiana deve usare lo slug del titolo *italiano* (`#web-push-facoltativo`). `--strict` non
   controlla le ancore: è l'unica cosa da rileggere a mano.
4. Il front matter viaggia con la pagina: l'hero della home (`hero_title`, `hero_text`, `hero_quickstart`,
   `hero_install`, `hero_meta`) si traduce in `index.it.md`, non nel template.
5. Compila con `php artisan griglia:docs-build --strict`: costruisce **tutti e due** gli alberi, quindi un
   link rotto in quello italiano fa fallire la build esattamente come in quello inglese.

## Le pagine generate

`griglia:docs-generate` scrive le tre pagine di Reference in tutte e due le lingue
(`reference/commands.md` e `reference/commands.it.md`, e così via). Nessuna di loro si modifica a mano:

- Le **impostazioni** non hanno bisogno di niente: etichette e testi di aiuto arrivano da
  `resources/lang/it/t.php`, esattamente quelli che mostra la pagina `/settings`.
- **Comandi** e **file di configurazione** si descrivono da soli, in inglese, dentro il codice. Il loro
  italiano viene dal catalogo `resources/docs/reference.it.php`, con la stringa inglese come chiave:

```php
'text' => [
    'Machine-readable output' => 'Output leggibile da un programma',
],
```

Una descrizione senza voce nel catalogo resta in inglese, e il comando lo dice a fine esecuzione:

```console
$ php artisan griglia:docs-generate
3 string(s) with no `it` translation in resources/docs/reference.it.php:
    Only show what would be archived
```

Quindi: cambi la descrizione di un comando o un commento in `config/griglia.php`, lanci
`griglia:docs-generate` e traduci quello che ti elenca. `griglia:docs-generate --check` (lo fa la CI)
fallisce quando le pagine committate non corrispondono più al codice, in una qualunque delle due lingue.

## Aggiungere una terza lingua

1. Aggiungi il locale a `plugins.i18n.languages` in `mkdocs.yml`, con le sue `nav_translations`.
2. Aggiungi `resources/docs/reference.<locale>.php` e il locale a `DocsGenerate::LANGUAGES`, poi lancia
   `griglia:docs-generate`.
3. Traduci le pagine come `pagina.<locale>.md`. Tutto quello che salti resta in inglese.

## Vedi anche

- [Costruire questo sito](docs-site.md) · [Contribuire](contributing.md)
