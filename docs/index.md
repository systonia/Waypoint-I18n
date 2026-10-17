---
title: Waypoint I18n
description: Translations for Waypoint apps, with the text in the code as the key, JSON catalogs and a locale per request.
order: 1
---

Waypoint I18n translates the texts of a [Waypoint](https://github.com/systonia/Waypoint) app. The text you write in the code is the key: `$this->t('Save')` in a template shows "Save" in the source language and "Speichern" once the German catalog has that entry. The source language needs no catalog. Every other language is one flat JSON file that CLI tasks keep in sync with the code, and a text without a translation shows the source text, never a raw key.

The plugin also picks a locale for every request (query parameter, cookie, `Accept-Language`), sends `Content-Language`, and translates the Validator's error messages, with German ones included.

## Install

```bash
composer require systonia/waypoint-i18n
```

It needs PHP 8.5 and `systonia/waypoint`. Register the plugin before `attach()` and tell it where the catalogs live:

```php
use Waypoint\I18n\{I18nOptions, I18nPlugin};

$app->plugin(new I18nPlugin());

$app->configure(function (I18nOptions $opts) {
    $opts->directory = __DIR__ . '/lang';                       // lang/de.json, lang/fr.json
    $opts->default = 'en';                                      // the language the code is written in
    $opts->supported = ['en', 'de'];
    $opts->scanPaths = [__DIR__ . '/views', __DIR__ . '/src'];  // where the tasks look for t() calls
});
```

## A first translated page

`index.php`:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Waypoint\Attributes\{Controller, Get};
use Waypoint\Http\View;
use Waypoint\I18n\{I18nOptions, I18nPlugin};
use Waypoint\Options\RendererOptions;
use Waypoint\Waypoint;

#[Controller]
final class HomeController
{
    #[Get('/')]
    public function home(): View
    {
        return new View('Home', ['name' => 'Ada', 'files' => 3], partial: true);
    }
}

$app = Waypoint::create();
$app->plugin(new I18nPlugin());
$app->configure(function (I18nOptions $opts) {
    $opts->directory = __DIR__ . '/lang';
    $opts->supported = ['en', 'de'];
    $opts->scanPaths = [__DIR__ . '/views'];
});
$app->configure(function (RendererOptions $opts) {
    $opts->directory = __DIR__ . '/views';
});
$app->attach([HomeController::class]);
$app->serve();
```

`views/Home.php`:

```html
<h1>{{ $this->t('Hello {name}', ['name' => $model['name']]) }}</h1>
<p>{{ $this->t('{count} file|{count} files', ['count' => $model['files']]) }}</p>
<p>{{ $this->t('Current language: {locale}', ['locale' => $this->locale()]) }}</p>
```

Create the German catalog from the code, then fill in the empty strings:

```bash
php index.php i18n:add de
```

```json
{
    "Current language: {locale}": "Aktuelle Sprache: {locale}",
    "Hello {name}": "Hallo {name}",
    "{count} file|{count} files": "{count} Datei|{count} Dateien"
}
```

Run it with `tenma index.php` (or `php -S localhost:8080 index.php`). `/` answers in English with `Content-Language: en`; `/?lang=de` answers "Hallo Ada", "3 Dateien", "Aktuelle Sprache: de" and sets the `lang` cookie, so the following requests stay German. A browser sending `Accept-Language: de-AT` gets German without asking.

## How these docs are organised

- **[Guides](guides/index.md)** cover one topic each: [writing translatable text](guides/translating-text.md), [catalogs and the CLI tasks](guides/catalogs.md), [the locale of a request](guides/locale.md), [validation messages](guides/validation-messages.md), [configuration](guides/configuration.md) and [testing](guides/testing.md).
- **[Reference](reference/index.md)** has one page per class, generated from the code: [`Translator`](reference/Waypoint/I18n/Translator.md), [`I18nOptions`](reference/Waypoint/I18n/I18nOptions.md), [`I18nTasks`](reference/Waypoint/I18n/I18nTasks.md) and the rest.

What the plugin adds to an app:

| Part | What it does |
|---|---|
| `$this->t()`, `$this->locale()` | view helpers in every template and layout |
| [`Translator`](reference/Waypoint/I18n/Translator.md) | the same translation in services and controllers, by `#[Inject]` |
| [`LocaleMiddleware`](reference/Waypoint/I18n/LocaleMiddleware.md) | picks the locale of every request, sends `Content-Language` |
| `i18n:extract`, `check`, `status`, `add`, `lint` | keep the catalogs in sync with the code, fail in CI |
| bundled `resources/de.json` | German for the Validator's messages |
