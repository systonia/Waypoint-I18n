---
title: Validation messages
description: The Validator's error messages in the request's language, with German included and your own catalog on top.
order: 40
---

Waypoint's Validator writes its messages in English (`This value should not be blank.`) and asks the container for a `Waypoint\Validation\Messages` service to translate them. The plugin binds its [`Translator`](../reference/Waypoint/I18n/Translator.md) as that service, so validation errors come out in the locale of the request, like every other text.

```php
use Waypoint\Attributes\{Body, Controller, Email, NotBlank, Post};
use Waypoint\FromArray;

final class SignupForm
{
    use FromArray;

    #[NotBlank]
    #[Email]
    public ?string $email = null;
}

#[Controller]
final class SignupController
{
    #[Post('/signup')]
    public function signup(#[Body] SignupForm $form): array
    {
        return ['ok' => true];
    }
}
```

```bash
$ curl -X POST 'localhost:8080/signup?lang=de' -H 'Content-Type: application/json' -d '{"email":"nope"}'
{"type":"about:blank","title":"Validation failed","status":422,"detail":"Validation failed","errors":{"email":"Dieser Wert ist keine gültige E-Mail-Adresse."}}
```

Without `?lang=de` (and no cookie or German browser) the same request answers `This value is not a valid email address.`

## The bundled German

The plugin ships `resources/de.json` with German for these messages:

| English | German |
|---|---|
| This value should not be blank. | Dieser Wert darf nicht leer sein. |
| This value is not a valid email address. | Dieser Wert ist keine gültige E-Mail-Adresse. |
| This value is too short. Minimum length is {min}. | Dieser Wert ist zu kurz. Mindestlänge ist {min}. |
| This value is too long. Maximum length is {max}. | Dieser Wert ist zu lang. Maximallänge ist {max}. |
| This value does not match the required format. | Dieser Wert hat nicht das erwartete Format. |
| This value should be one of: {choices}. | Dieser Wert muss einer der folgenden sein: {choices}. |
| This value should match {field}. | Dieser Wert muss mit {field} übereinstimmen. |

The bundled catalog lies underneath the app's: a non-empty entry with the same English text in your `lang/de.json` wins. To word one message differently, add only that entry:

```json
{
    "This value should not be blank.": "Bitte ausfüllen."
}
```

Other languages have no bundled catalog. `i18n:extract` only keeps texts it finds as `t()` calls in the scanned code, and it would move hand-added validation messages to `__orphans`. So list them once in a scanned file; the file is never run, it only makes the texts visible to the tasks:

```php
<?php // src/ValidationTexts.php: the Validator's messages, so i18n:extract keeps them in every catalog

/** @var Waypoint\I18n\Translator $t */
$t->t('This value should not be blank.');
$t->t('This value is not a valid email address.');
$t->t('This value is too short. Minimum length is {min}.');
$t->t('This value is too long. Maximum length is {max}.');
$t->t('This value does not match the required format.');
$t->t('This value should be one of: {choices}.');
$t->t('This value should match {field}.');
```

After `i18n:extract`, `lang/fr.json` has these seven entries empty, ready to translate, and `i18n:check` holds them to the same rules as every other text. In `de.json` they appear empty too: until they are filled, the bundled German is shown, and `i18n:check` reports them as untranslated.

`I18nOptions::$includeDefaults = false` drops the bundled catalog, so only your own entries are used.

See also: [The locale of a request](locale.md), [Catalogs and the CLI tasks](catalogs.md).
