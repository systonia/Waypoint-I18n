---
title: "LocaleMiddleware"
description: "Picks the locale of each request and sets it on the Translator; the plugin adds it app-wide."
order: 100
generated: true
---

`Waypoint\I18n\LocaleMiddleware` · final class · namespace [`Waypoint\I18n`](index.md)

```php
final class LocaleMiddleware extends \Waypoint\Http\MiddlewareBase
```

Extends `Waypoint\Http\MiddlewareBase`

Picks the locale of each request and sets it on the Translator; the plugin adds it app-wide.

The order is: resolver, `?lang=`, cookie, Accept-Language, default. An explicit choice by query
parameter is remembered in the cookie, and the response carries Content-Language. The Translator
is reset to the default afterwards.

## Methods

### __construct()

```php
public function __construct(
    private \Waypoint\I18n\Translator $translator,
    private \Waypoint\I18n\I18nOptions $options,
)
```

**Parameters**

- `$translator` [`Translator`](Translator.md)
- `$options` [`I18nOptions`](I18nOptions.md)
