# waypoint-i18n

Translations for [Waypoint](https://github.com/Systonia/Waypoint): the text in your code is the key.

```php
<h2><?= $this->t('Welcome to gdn.ani') ?></h2>
<p><?= $this->t('{count} file|{count} files', ['count' => $n]) ?></p>
```

The source language needs no catalog. Every other language is one flat JSON file that the CLI
tasks keep in sync with the code, so nothing is maintained by hand and a missing translation shows
the source text, never a raw key.

Documentation: [guides and reference](docs/index.md) — [writing text](docs/guides/translating-text.md),
[catalogs and tasks](docs/guides/catalogs.md), [locale per request](docs/guides/locale.md), [API reference](docs/reference/index.md).

## Installation

```bash
composer require systonia/waypoint-i18n
```

Requires PHP 8.5 and `systonia/waypoint`.

## Setup

```php
use Waypoint\I18n\{I18nOptions, I18nPlugin};

$app->plugin(new I18nPlugin());               // before attach()

$app->configure(function (I18nOptions $opts) {
    $opts->directory = __DIR__ . '/lang';      // lang/de.json, lang/fr.json
    $opts->default = 'en';                     // the language the code is written in
    $opts->supported = ['en', 'de', 'fr'];
    $opts->scanPaths = [__DIR__ . '/views', __DIR__ . '/src'];
});
```

## Writing text

- Templates: `$this->t('Save')`, `$this->t('Hello {name}', ['name' => $user->name])`, `$this->locale()`.
- Services and controllers: `#[Inject] Waypoint\I18n\Translator $translator` and `$translator->t(...)`.
- `{name}` is replaced from the parameters; `one|other` picks the first form when `count` is 1.
- `t()` never escapes, so it also works for `<title>`, attributes or mail text; escape the result where it
  lands in HTML: `<?= $this->t('Welcome back, {name}.', ['name' => $name]) |> htmlspecialchars(...) ?>`.
- Use string literals: `t($variable)` works at runtime but the tasks cannot see it (`i18n:lint` reports it).

## Catalogs

`lang/de.json`, written and updated by the tasks:

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

`""` means not translated yet (the source text is shown). `__orphans` holds entries whose text no
longer occurs in the code; they are kept so a translation can be carried over after a rewording.
A missing entry is looked up in `$fallback` before falling back to the source text.

## Tasks

```bash
php index.php i18n:extract     # scan the code, add new texts to every catalog, move gone ones to __orphans
php index.php i18n:check       # exit 1 on untranslated entries, orphans, placeholder/plural mismatches, missing catalogs
php index.php i18n:status      # per-locale table: total, translated, empty, orphans, percent done
php index.php i18n:add fr      # create lang/fr.json with every source text untranslated
php index.php i18n:lint        # exit 1 on t($var), padded texts, "$interpolation", near-duplicate texts
```

Typical loop: write code, `extract`, translate the empty strings, `check` in CI. Handy as Composer
scripts in your app (`"i18n:check": "php index.php i18n:check"` etc.); arguments pass through
(`composer i18n:add fr`).

## Locale per request

`LocaleMiddleware` decides once per request, first match wins:

1. `I18nOptions::$resolver`, a `Closure(Request): ?string`, if set
2. `?lang=de`, which is also stored in the `lang` cookie
3. the `lang` cookie
4. `Accept-Language`, by quality, with `de-AT` falling back to `de`
5. `$default`

Only locales in `$supported` are accepted; with `$supported` empty, any locale that has a catalog
file. The response carries `Content-Language`. The locale is reset after every request.

## Validation messages

The Validator's English messages (`This value should not be blank.` etc.) are translated through
this plugin like any other text. It ships German in `resources/de.json`; a same-named entry in your
own catalog wins. `I18nOptions::$includeDefaults = false` drops the bundled ones.

## Options

| Setting | Default | Description |
|---|---|---|
| `directory` | `null` | Where the catalogs live. Required for translations and the tasks. |
| `default` | `'en'` | The source language; needs no catalog. |
| `fallback` | `null` | Locale a missing translation is looked up in next; `null` means the source text. |
| `supported` | `[]` | Locales a request may select; empty means every locale with a catalog file. |
| `queryParam` | `'lang'` | Query parameter selecting the locale (`''` disables). |
| `cookieName` | `'lang'` | Cookie remembering the selection (`''` disables). |
| `cookieTtl` | `31536000` | Cookie lifetime in seconds. |
| `resolver` | `null` | `Closure(Request): ?string` consulted first. |
| `includeDefaults` | `true` | Use the bundled validation-message translations underneath yours. |
| `scanPaths` | `[]` | Files/directories the tasks scan; empty means `RendererOptions::$directory`. |

## Development

```bash
composer install
composer test    # PHPUnit on the Waypoint\Testing kit
composer stan    # PHPStan level 10
```

## License

MIT or Apache-2.0, at your option.
