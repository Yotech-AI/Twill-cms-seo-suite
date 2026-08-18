<?php

namespace TwillSeo\Analysis\Language\Data;

/**
 * Reads a PHP data file (a word list, a message file) once per process.
 *
 * The cache is static rather than per-instance: word lists run to thousands of
 * entries and are identical for every paper, so a long-lived worker should pay
 * for them once. Being purely idempotent, it is safe to keep across requests
 * under Octane.
 */
final class DataFileLoader
{
    /** @var array<string,array<mixed>> */
    private static array $cache = [];

    /**
     * @return array<mixed> empty when the file is missing or does not return an
     *                      array — a broken data file degrades the analysis
     *                      rather than stopping it
     */
    public static function load(string $path): array
    {
        if (array_key_exists($path, self::$cache)) {
            return self::$cache[$path];
        }

        $data = is_file($path) ? require $path : null;

        return self::$cache[$path] = is_array($data) ? $data : [];
    }
}
