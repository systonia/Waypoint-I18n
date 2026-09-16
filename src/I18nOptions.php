<?php

namespace Waypoint\I18n;

use Closure;
use Waypoint\Http\Request;

/**
 * Configure via App::configure(function (I18nOptions $opts) { ... }).
 * Source texts in code are the keys; a catalog is {directory}/{locale}.json
 * mapping them to translations, kept up to date by the i18n:* CLI tasks.
 */
class I18nOptions
{
    /** Where the catalogs live. Required for translations and for the CLI tasks. */
    public ?string $directory = null;

    /** The language the source texts are written in; needs no catalog. */
    public string $default = 'en';

    /** Locale a missing translation is looked up in next; null = $default (i.e. the source text). */
    public ?string $fallback = null;

    /** @var list<string> Locales a request may select; empty = every locale with a catalog file. */
    public array $supported = [];

    /** Query parameter selecting the locale for this request and storing it in the cookie ('' disables). */
    public string $queryParam = 'lang';

    /** Cookie remembering the selected locale ('' disables). */
    public string $cookieName = 'lang';

    /** Cookie lifetime in seconds. */
    public int $cookieTtl = 31536000;

    /** Custom resolver consulted first: Closure(Request): ?string. */
    public ?Closure $resolver = null;

    /** Use the bundled translations of the Validator's messages underneath your own catalogs. */
    public bool $includeDefaults = true;

    /** @var list<string> Files/directories the CLI tasks scan for t('...') calls; empty = RendererOptions::$directory. */
    public array $scanPaths = [];
}
