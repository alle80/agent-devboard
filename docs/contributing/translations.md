# Translations

The site is bilingual: **English is the base language**, Italian is the translation. There are two separate
things called "translations" in this repository, and they do not touch each other:

| | The site | The board |
|---|---|---|
| What | these documentation pages | the strings of the Livewire UI |
| Where | `docs/**/page.it.md` | `resources/lang/{en,it}/t.php` |
| Checked by | `mkdocs build --strict` | `TranslationsTest` (same keys in both files) |

This page is about the first one. For the second, see [Contributing](contributing.md).

Which language the board *shows* is a setting: **Board language** in the App group of `/settings`
(see [Configuration & settings](../configuration/index.md#the-language-of-the-board)).

## How a translated page works

The site uses [mkdocs-static-i18n](https://ultrabug.github.io/mkdocs-static-i18n/) with the **suffix**
structure: the Italian version of `board/usage.md` is `board/usage.it.md`, in the same folder.

- English is served at `/`, Italian at `/it/`.
- A page with no `.it.md` **falls back** to the English one: a missing translation never breaks a link, it
  just leaves that page in English.
- The language switcher in the header lands on the *same page* in the other language.
- Nav titles are not in the pages, they are in `mkdocs.yml` → `plugins.i18n.languages.it.nav_translations`
  (keyed by the English title). A new nav entry needs its line there too.

## Writing one

1. Copy the English page next to itself with the `.it.md` suffix and translate it.
2. **Keep the links relative and unchanged** (`../features/plans.md`): the plugin points them at the Italian
   version by itself, when there is one.
3. **Anchors are not translated.** A link to a heading (`installation.md#web-push-optional`) must use the
   slug of the *Italian* heading in the Italian page (`#web-push-facoltativo`). `--strict` does not check
   anchors, so this is the one thing to re-read.
4. Front matter travels with the page: the home hero (`hero_title`, `hero_text`, `hero_quickstart`,
   `hero_install`, `hero_meta`) is translated in `index.it.md`, not in the template.
5. Build with `php artisan griglia:docs-build --strict`: it builds **both** trees, so a broken link in the
   Italian one fails just like in the English one.

## The generated pages

`griglia:docs-generate` writes the three Reference pages in both languages
(`reference/commands.md` and `reference/commands.it.md`, and so on). Do not edit any of them by hand:

- **Settings** need nothing: the labels and help texts come from `resources/lang/it/t.php`, exactly the ones
  the `/settings` page shows.
- **Commands** and **configuration file** describe themselves in English, in the code. Their Italian comes
  from the catalogue `resources/docs/reference.it.php`, keyed by the English source string:

```php
'text' => [
    'Machine-readable output' => 'Output leggibile da un programma',
],
```

A description with no entry stays in English, and the command says so at the end of its run:

```console
$ php artisan griglia:docs-generate
3 string(s) with no `it` translation in resources/docs/reference.it.php:
    Only show what would be archived
```

So: change a command description or a comment in `config/griglia.php`, then run `griglia:docs-generate`, and
translate whatever it lists. `griglia:docs-generate --check` (run by CI) fails when the committed pages no
longer match the code, in either language.

## Adding a third language

1. Add the locale to `plugins.i18n.languages` in `mkdocs.yml`, with its `nav_translations`.
2. Add `resources/docs/reference.<locale>.php` and the locale to `DocsGenerate::LANGUAGES`, then run
   `griglia:docs-generate`.
3. Translate the pages as `page.<locale>.md`. Everything you skip stays English.

## See also

- [Building this site](docs-site.md) · [Contributing](contributing.md)
