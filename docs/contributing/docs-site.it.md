# Costruire questo sito di documentazione

La documentazione è Markdown semplice dentro `docs/`, con un `mkdocs.yml` (tema **Material for MkDocs**) alla
radice del package. Il sito è **bilingue**: l'inglese è la lingua base, le pagine italiane stanno accanto come
`pagina.it.md` — vedi [Traduzioni](translations.md).

## Cosa serve

```bash
pip install -r requirements-docs.txt   # Python 3.8+; mkdocs + Material + il plugin static-i18n
```
oppure, senza Python, Docker: `griglia:docs-build --docker` costruisce l'immagine con la catena di strumenti a
partire da `docs.Dockerfile` (l'immagine ufficiale `squidfunk/mkdocs-material` da sola non basta — non porta
il plugin i18n).

## Pagine generate

Tre pagine della sezione Reference le scrive il package stesso, in tutte e due le lingue:

```bash
php artisan griglia:docs-generate            # → docs/reference/{commands,config,settings}.md + .it.md
php artisan griglia:docs-generate --check    # fallisce quando le pagine committate non sono aggiornate (CI)
```

Le versioni italiane vengono dallo stesso codice: le impostazioni dalle traduzioni `it` della pagina, le
descrizioni di comandi e config dal catalogo `resources/docs/reference.it.php` (vedi
[Traduzioni](translations.md)).

`griglia:docs-build` lo lancia prima di ogni compilazione (`--no-generate` per saltarlo), così il sito
corrisponde sempre al codice. Non modificare a mano quei file.

`--check` è pensato per il repository del package (e per la sua CI): dentro un'applicazione ospite la pagina
delle impostazioni è legittimamente diversa, perché elenca i provider AI installati lì.

## Compilare

```bash
php artisan griglia:docs-build                    # → site/ (HTML)
php artisan griglia:docs-build --serve            # anteprima dal vivo su http://127.0.0.1:8000
php artisan griglia:docs-build --out=/var/www/docs
php artisan griglia:docs-build --docker           # costruisce e usa l'immagine di docs.Dockerfile
```

Il comando esegue `mkdocs build` (o l'immagine Docker) dalla cartella del package, segnala con chiarezza
quando MkDocs manca (con il suggerimento per installarlo) o quando la build fallisce (stderr), e stampa dove
è finito l'HTML. L'equivalente senza artisan: `mkdocs build` nella radice del package.

## Tutto il giro, per un agente

Quando si lavora al package, la documentazione fa parte della modifica — non è un ripensamento:

1. **Scrivi** la pagina in `docs/` (mai quelle generate: `reference/{commands,config,settings}.md` e i loro
   `.it.md`) — e la sua controparte italiana, vedi [Traduzioni](translations.md).
2. **Rigenera** quello che viene dal codice, se hai toccato un comando, una chiave di config o
   un'impostazione: `php artisan griglia:docs-generate` (da un'applicazione ospite) oppure
   `vendor/bin/testbench griglia:docs-generate` (dentro il repository del package).
3. **Valida**: `php artisan griglia:docs-build --strict` — gli avvisi diventano errori, quindi un link
   interno rotto o una pagina fuori dalla nav fanno fallire la build. `griglia:docs-generate --check` dice se
   le pagine di reference committate sono vecchie; la CI lo lancia per te.
4. **Guardalo**: `--serve` per l'anteprima dal vivo, oppure compila dentro una cartella che il tuo web server
   serve già.
5. **Committa** `docs/`, `mkdocs.yml` e le pagine rigenerate insieme alla modifica del codice, e aggiungi la
   riga al `CHANGELOG.md` — il changelog *è* una pagina del sito.

`griglia:docs-build --strict` compila **tutte e due le lingue**: un link rotto nell'albero italiano fa
fallire la build esattamente come uno nell'albero inglese.

## Pubblicazione

`.github/workflows/docs.yml` compila il sito (`mkdocs build --strict`) e lo pubblica su **GitHub Pages** a
ogni push su `master` che tocchi `docs/`, `overrides/`, `mkdocs.yml` o `CHANGELOG.md`; si può lanciare anche a
mano (*Run workflow*). Per compilare il sito non serve PHP, perché le pagine generate sono committate — è
`tests.yml` che fallisce quando non sono aggiornate.

Il repository deve avere **Settings → Pages → Source: GitHub Actions** attivato una volta, e `site_url` in
`mkdocs.yml` deve corrispondere all'indirizzo pubblicato.
