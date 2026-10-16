---
title: Testing
description: Requests in another language, catalogs as fixtures, and the i18n tasks in PHPUnit.
order: 60
---

Tests use Waypoint's testing kit, `Waypoint\Testing\TestCase`. Requests go through the whole app, middleware included, so the locale is picked exactly as in production.

## Translated pages and responses

Point `directory` at small fixture catalogs next to the tests, and choose the language per request with the query parameter, a cookie or `Accept-Language`:

```php
<?php

use Waypoint\I18n\{I18nOptions, I18nPlugin};
use Waypoint\Options\RendererOptions;
use Waypoint\Testing\TestCase;

final class GreetingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $app = $this->app();
        $app->plugin(new I18nPlugin());
        $app->configure(function (I18nOptions $opts) {
            $opts->directory = __DIR__ . '/lang';            // tests/lang/de.json: {"Hello {name}": "Hallo {name}"}
            $opts->supported = ['en', 'de'];
        });
        $app->configure(function (RendererOptions $opts) {
            $opts->directory = __DIR__ . '/views';
        });
        $app->attach([HomeController::class]);
    }

    public function testEnglishByDefault(): void
    {
        $this->get('/api/greeting')
            ->assertJsonPath('message', 'Hello Ada')
            ->assertHeader('Content-Language', 'en');
    }

    public function testTheQueryParameterSelectsAndRemembersGerman(): void
    {
        $this->get('/api/greeting?lang=de')
            ->assertJsonPath('message', 'Hallo Ada')
            ->assertCookie('lang', 'de');
    }

    public function testTheBrowserLanguage(): void
    {
        $this->get('/api/greeting', ['Accept-Language' => 'de-AT,de;q=0.9'])->assertJsonPath('message', 'Hallo Ada');
    }

    public function testTheCookie(): void
    {
        $this->request('GET', '/api/greeting')->withCookie('lang', 'de')->send()->assertJsonPath('message', 'Hallo Ada');
    }

    public function testValidationErrorsInGerman(): void
    {
        $this->json('POST', '/signup?lang=de', ['email' => 'nope'])
            ->assertStatus(422)
            ->assertJsonPath('errors.email', 'Dieser Wert ist keine gültige E-Mail-Adresse.');
    }
}
```

## The Translator on its own

The [`Translator`](../reference/Waypoint/I18n/Translator.md) needs nothing but its options, so formatting and fallback can be tested with plain PHPUnit:

```php
use PHPUnit\Framework\TestCase;
use Waypoint\I18n\{I18nOptions, Translator};

final class PluralTest extends TestCase
{
    public function testGermanPlural(): void
    {
        $options = new I18nOptions();
        $options->directory = __DIR__ . '/lang';
        $translator = new Translator($options);
        $translator->setLocale('de');

        $this->assertSame('3 Dateien', $translator->t('{count} file|{count} files', ['count' => 3]));
    }
}
```

## Catalogs in CI

The simplest guard runs the tasks themselves; `i18n:check` exits with 1 when a text is untranslated or a translation lost a placeholder:

```bash
php index.php i18n:extract && php index.php i18n:check && php index.php i18n:lint
```

Run `extract` on a clean checkout; if it changes a catalog, someone forgot to run it. A CI step can fail on that:

```bash
php index.php i18n:extract && git diff --exit-code lang/
```

The tasks can also run inside a test. They are methods on the router; a failing task throws a `RuntimeException` with the problems in its message:

```php
public function testEveryCatalogIsComplete(): void
{
    $argv = ['index.php', 'i18n:check'];
    $output = (string) $this->app()->getRouter()->executeTask('i18n:check', $argv, count($argv));
    $this->assertStringStartsWith('OK', $output);
}
```

For that test, configure `directory` and `scanPaths` with the app's real catalogs and code, not fixtures.

See also: [Waypoint's testing kit](https://github.com/systonia/Waypoint/blob/main/README.md#testing).
