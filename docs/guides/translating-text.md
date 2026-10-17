---
title: Writing translatable text
description: t() in templates and services, placeholders, plural forms, escaping and the rules that keep texts extractable.
order: 10
---

A translatable text is a call to `t()` with the text in the source language. The text is the catalog key, so the code reads as it renders, and a text nobody has translated yet still shows something sensible.

## In templates

The plugin adds two [view helpers](https://github.com/systonia/Waypoint/blob/main/README.md#plugins) to every template and layout:

```html
<title>{{ $this->t('Your files') }}</title>
<p>{{ $this->t('Save') }}</p>
<p>{{ $this->t('Hello {name}', ['name' => $model['name']]) }}</p>
<html lang="{{ $this->locale() }}">
```

- `$this->t(string $text, array $params = [], ?string $locale = null)` returns the translation in the current locale.
- `$this->locale()` returns the current locale, for example `de`.

## In services and controllers

The same [`Translator`](../reference/Waypoint/I18n/Translator.md) is a service; inject it:

```php
use Waypoint\Attributes\{Controller, Get, Inject};
use Waypoint\I18n\Translator;

#[Controller]
final class HomeController
{
    #[Inject]
    private Translator $translator;

    #[Get('/api/greeting')]
    public function greeting(): array
    {
        return ['message' => $this->translator->t('Hello {name}', ['name' => 'Ada'])];
    }
}
```

`GET /api/greeting?lang=de` answers `{"message":"Hallo Ada"}` once `lang/de.json` has `"Hello {name}": "Hallo {name}"`.

The third parameter translates into a locale other than the current one, for example a mail to a user who chose another language:

```php
$subject = $this->translator->t('Your invoice {number}', ['number' => $invoice->number], $customer->locale);
```

## Placeholders

`{name}` in the text is replaced with `$params['name']`. Every scalar parameter is replaced, numbers and booleans as PHP turns them into strings; arrays and objects are ignored. A placeholder without a parameter stays as it is. Placeholder names are letters, digits and `_`; `i18n:check` reports a translation whose placeholders differ from the source text's.

```php
$this->t('{count} new messages for {name}', ['count' => 4, 'name' => 'Ada']);   // "4 new messages for Ada"
```

The translation may put the placeholders in any order: `"{name}: {count} neue Nachrichten"`.

## Plural forms

A text with `|` has two forms, `one|other`. When `$params['count']` is numeric, the first form is used when the count, cut to an integer, is 1 (so `1`, `"1"` and `1.5`) and the second for everything else (0, 2, -1):

```php
$this->t('{count} file|{count} files', ['count' => 1]);   // "1 file"
$this->t('{count} file|{count} files', ['count' => 3]);   // "3 files"
```

The translation has the same two forms: `"{count} Datei|{count} Dateien"`. The form is chosen after the lookup, so it is always the translation's own `|` that splits. Without a numeric `count`, the text is returned with the `|` in it. The text is split at the first `|` only; a third form ends up in the second.

## Escaping

`t()` never escapes. That keeps it usable for `<title>`, attributes, JSON and mail text. In a view `{{ }}` escapes the result, parameters from a user included:

```html
<p>{{ $this->t('Welcome back, {name}.', ['name' => $user->name]) }}</p>
```

A translation may contain markup on purpose (`"Read the <a href=\"/terms\">terms</a>"`); then escape the parameters instead of the result and print it as a `Waypoint\Html`:

```html
<p>{{ new Waypoint\Html($this->t('Read the <a href="/terms">terms</a>, {name}.', ['name' => htmlspecialchars($user->name)])) }}</p>
```

## Keep texts extractable

The CLI tasks find texts by scanning PHP files for `->t(` followed by a string literal. So:

- Write the text as a literal: `t('Save')`. `t($label)` works at runtime, but `i18n:extract` cannot see the text, and `i18n:lint` reports the call.
- Use single quotes, or double quotes without `$`: in `t("Hi $name")` PHP interpolates before `t()` sees the text, so every name is a different key. Use a placeholder.
- Call it as a method, `$this->t(...)` or `$translator->t(...)`; a function named `t()` or a static call is not scanned.
- Keep whitespace and punctuation stable: `'Save'`, `' Save '` and `'save.'` are three keys, which `i18n:lint` reports as near-duplicates.

Texts are compared byte for byte, so a changed comma is a new text; [`i18n:extract`](catalogs.md#i18nextract) moves the old one to the orphans with its translation.

See also: [Catalogs and the CLI tasks](catalogs.md), [`Translator`](../reference/Waypoint/I18n/Translator.md).
