<?php

namespace Waypoint\I18n;

use RuntimeException;

/**
 * One locale's catalog file: a flat JSON object {source text: translation},
 * "" meaning "not translated yet", plus an optional "__orphans" object for
 * entries whose source text no longer occurs in the code (kept, not lost).
 */
final class Catalog
{
    public const ORPHANS = '__orphans';

    /**
     * @param array<string, string> $entries
     * @param array<string, string> $orphans
     */
    public function __construct(public readonly string $file, public array $entries = [], public array $orphans = [])
    {
    }

    /** An empty catalog for a file that doesn't exist yet; invalid JSON is an error, never silently empty. */
    public static function load(string $file): self
    {
        if (!is_file($file)) {
            return new self($file);
        }
        $data = json_decode((string) file_get_contents($file), true);
        if (!is_array($data)) {
            throw new RuntimeException("$file is not a valid JSON object.");
        }
        $catalog = new self($file);
        foreach ($data as $key => $value) {
            if ($key === self::ORPHANS && is_array($value)) {
                $catalog->orphans = self::strings($value);
            } elseif (is_string($key) && is_string($value)) {
                $catalog->entries[$key] = $value;
            }
        }
        return $catalog;
    }

    public function exists(): bool
    {
        return is_file($this->file);
    }

    /** Writes the file, entries sorted naturally and case-insensitively, orphans last. */
    public function save(): void
    {
        uksort($this->entries, 'strnatcasecmp');
        uksort($this->orphans, 'strnatcasecmp');
        $data = $this->entries;
        if ($this->orphans !== []) {
            $data[self::ORPHANS] = $this->orphans;
        }
        $dir = dirname($this->file);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents($this->file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
    }

    /** @return list<string> Source texts without a translation. */
    public function untranslated(): array
    {
        return array_keys(array_filter($this->entries, fn(string $v): bool => $v === ''));
    }

    /**
     * @param array<array-key, mixed> $data
     * @return array<string, string>
     */
    private static function strings(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            if (is_string($key) && is_string($value)) {
                $out[$key] = $value;
            }
        }
        return $out;
    }
}
