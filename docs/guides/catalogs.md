---
title: Catalogs and the CLI tasks
description: The JSON catalog format, how a text is looked up, and the i18n tasks that keep catalogs in sync with the code.
order: 20
---

Every language except the source language has one catalog: `{directory}/{locale}.json`, a flat JSON object from source text to translation. The tasks write and update these files; a translator only fills in the empty strings.

```json
{
    "Hello {name}": "Hallo {name}",
    "Save": "Speichern",
    "{count} file|{count} files": "{count} Datei|{count} Dateien",
    "Create an account": "",
    "__orphans": {
        "Old wording": "Alte Formulierung"
    }
}
```

- `""` means not translated yet; the source text is shown.
- `__orphans` holds entries whose text no longer occurs in the code. They are kept so a translation can be carried over after a rewording, and are never used for lookups.
- The tasks write the file sorted naturally and case-insensitively, orphans last, pretty-printed with unescaped Unicode and slashes, so diffs stay small.
- A file that is not a JSON object is an error (`lang/de.json is not a valid JSON object.`), never an empty catalog. Non-string values are skipped.

## How a text is looked up

`t('Save')` in locale `de`:

1. the app's `lang/de.json`, if it has a non-empty entry,
2. the bundled catalog of the plugin for `de` (the [validation messages](validation-messages.md)), unless `includeDefaults` is off,
3. the same two for the fallback locale, `I18nOptions::$fallback`, or for `$default` when no fallback is set,
4. the source text itself.

With `fallback = 'de'` a Swiss site can keep `de-CH.json` to the few words that differ and get everything else from `de.json`:

```php
$app->configure(function (I18nOptions $opts) {
    $opts->directory = __DIR__ . '/lang';
    $opts->supported = ['en', 'de', 'de-CH'];
    $opts->fallback = 'de';
});
```

The Translator reads a catalog the first time a locale is used and keeps it for the life of the process. A long-running worker (Tenma) keeps using the catalog it read until it restarts, so restart it after editing a catalog; under FPM every request reads it again.

## The tasks

The tasks run through the app's own front controller, like any `#[Task]` of Waypoint:

```bash
php index.php i18n:extract     # scan the code, add new texts to every catalog, move gone ones to __orphans
php index.php i18n:check       # exit 1 on untranslated entries, orphans, placeholder/plural mismatches, missing catalogs
php index.php i18n:status      # per-locale table: total, translated, empty, orphans, percent done
php index.php i18n:add fr      # create lang/fr.json with every source text untranslated
php index.php i18n:lint        # exit 1 on t($var), padded texts, "$interpolation", near-duplicate texts
```

They scan the files and directories in `I18nOptions::$scanPaths` (every `.php` file, recursively), or `RendererOptions::$directory` when that list is empty. The locales they work on are those in `$supported` plus every `*.json` already in the catalog directory, without `$default`. A task that finds a problem throws, so the process exits with 1.

A typical loop: write code, `i18n:extract`, translate the empty strings, `i18n:check` in CI.

### i18n:add

```bash
$ php index.php i18n:add de
/app/lang/de.json created with 3 untranslated texts.
```

Creates the catalog with every text found in the code set to `""`. The locale must look like `fr`, `de-AT` or `zh-Hant` (two or three lowercase letters, then `-` parts); an existing file is never overwritten.

### i18n:extract

After the heading in `views/Home.php` changed from `'Hello {name}'` to `'Welcome, {name}'`:

```bash
$ php index.php i18n:extract
3 source texts found.
de: 1 added, 1 orphaned, 1 untranslated -> /app/lang/de.json
```

```json
{
    "Current language: {locale}": "Aktuelle Sprache: {locale}",
    "Welcome, {name}": "",
    "{count} file|{count} files": "{count} Datei|{count} Dateien",
    "__orphans": {
        "Hello {name}": "Hallo {name}"
    }
}
```

New texts are added as `""`; texts no longer in the code move to `__orphans` with their translation. When a text that is in the orphans appears in the code again, it comes back with its translation. A supported locale without a file is reported (`fr: no catalog file; run i18n:add fr`) and left alone.

### i18n:check

```bash
$ php index.php i18n:check
de: untranslated: "Welcome, {name}"
de: orphan: "Hello {name}"
2 problem(s).
```

Every one of these fails the check:

- an entry that is `""`,
- an orphan (translate the new text, then delete the orphan),
- a translation whose `{placeholders}` differ from the source text's: `placeholders differ in "Hello {name}" (missing: name; extra: nom)`,
- a translation that has `|` when the source text has none, or the other way round,
- a locale in `$supported` without a catalog file.

When everything is complete it prints `OK: every catalog is complete and consistent.` and exits with 0, which makes it a CI step:

```bash
php index.php i18n:extract && php index.php i18n:check
```

Run `extract` first, or the check compares the catalogs with the code as it was at the last extract.

### i18n:status

```bash
$ php index.php i18n:status
locale       total  translated   empty  orphans   done
de               3           2       1        1    67%
fr*              0           0       0        0   100%
(* = no catalog file yet)
```

Only reports; it never fails.

### i18n:lint

```bash
$ php index.php i18n:lint
/app/src/Bad.php:1: t() with a non-literal argument ($label) -- extract cannot see it
/app/src/Bad.php:1: double-quoted text contains $ -- PHP interpolates it before t() sees it
/app/src/Bad.php:1: text has leading or trailing whitespace: " Save "
near-duplicate texts: " Save " (/app/src/Bad.php:1), "save." (/app/src/Bad.php:1)
4 finding(s).
```

Reports calls that `extract` cannot see or that split one text into several keys: a non-literal argument, whitespace around the text, a double-quoted text with `$`, and texts that are equal once trimmed, lowercased and stripped of a trailing `.`, `:`, `!` or `?`. Without findings it prints `OK: no lint findings.`

## As Composer scripts

In the app's `composer.json`; arguments pass through, so `composer i18n:add fr` works:

```json
{
    "scripts": {
        "i18n:extract": "php index.php i18n:extract",
        "i18n:check": "php index.php i18n:check",
        "i18n:status": "php index.php i18n:status",
        "i18n:add": "php index.php i18n:add",
        "i18n:lint": "php index.php i18n:lint"
    }
}
```

See also: [`I18nTasks`](../reference/Waypoint/I18n/I18nTasks.md), [`Catalog`](../reference/Waypoint/I18n/Catalog.md), [Configuration](configuration.md).
