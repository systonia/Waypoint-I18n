<?php

namespace Waypoint\I18n;

use Waypoint\Validation\Messages;

/**
 * t('Source text', [...]) -> the current locale's translation, else the
 * fallback locale's, else the source text itself. {name} placeholders are
 * replaced from $params; "one|other" picks by $params['count']. Also the
 * container-bound Validation\Messages, so the Validator's English texts are
 * translated the same way.
 */
final class Translator implements Messages
{
    /** @var array<string, array<string, string>> locale => source => translation (bundled defaults underneath the app's catalog) */
    private array $catalogs = [];

    private string $locale;

    public function __construct(private I18nOptions $options)
    {
        $this->locale = $options->default;
    }

    public function locale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    /** @param array<string, mixed> $params Scalars replace {placeholders}; 'count' selects the plural form. */
    public function t(string $text, array $params = [], ?string $locale = null): string
    {
        $locale ??= $this->locale;
        $translated = $this->lookup($text, $locale)
            ?? $this->lookup($text, $this->options->fallback ?? $this->options->default)
            ?? $text;
        return self::format($translated, $params);
    }

    public function translate(string $text, array $params): string
    {
        return $this->t($text, $params);
    }

    /** True for the default locale or any locale with a catalog file. */
    public function has(string $locale): bool
    {
        return $locale === $this->options->default || is_file($this->catalogFile($locale));
    }

    /** @return list<string> The default locale plus every locale with a catalog file. */
    public function available(): array
    {
        $locales = [$this->options->default];
        foreach (glob(($this->options->directory ?? '') . '/*.json') ?: [] as $file) {
            $locales[] = basename($file, '.json');
        }
        return array_values(array_unique($locales));
    }

    public function catalogFile(string $locale): string
    {
        return rtrim($this->options->directory ?? '', '/') . "/$locale.json";
    }

    private function lookup(string $text, string $locale): ?string
    {
        $value = $this->catalog($locale)[$text] ?? '';
        return $value === '' ? null : $value;
    }

    /** @return array<string, string> */
    private function catalog(string $locale): array
    {
        if (!isset($this->catalogs[$locale])) {
            $entries = [];
            if ($this->options->includeDefaults) {
                $entries = Catalog::load(__DIR__ . "/../resources/$locale.json")->entries;
            }
            if ($this->options->directory !== null) {
                $entries = array_filter(Catalog::load($this->catalogFile($locale))->entries, fn(string $v): bool => $v !== '') + $entries;
            }
            $this->catalogs[$locale] = $entries;
        }
        return $this->catalogs[$locale];
    }

    /** @param array<string, mixed> $params */
    private static function format(string $message, array $params): string
    {
        $count = $params['count'] ?? null;
        if (str_contains($message, '|') && is_numeric($count)) {
            [$one, $other] = explode('|', $message, 2);
            $message = (int) $count === 1 ? $one : $other;
        }
        foreach ($params as $name => $value) {
            if (is_scalar($value)) {
                $message = str_replace('{' . $name . '}', (string) $value, $message);
            }
        }
        return $message;
    }
}
