<?php

namespace TwillSeo\Analysis\Messages;

use TwillSeo\Analysis\Contracts\MessageRenderer;
use TwillSeo\Analysis\Language\Data\DataFileLoader;

/**
 * Renders messages straight from the package's PHP language files, with no
 * translator involved.
 *
 * Keys are written in Laravel's namespaced form (twill-seo::analysis.group.branch)
 * so the exact same key works once a host boots the package under Laravel.
 * This renderer strips the namespace, treats the first segment as the file
 * name and the rest as a path into it.
 */
final class ArrayMessageRenderer implements MessageRenderer
{
    private const NAMESPACE_PREFIX = 'twill-seo::';

    public function __construct(private readonly string $directory = __DIR__.'/../../../resources/lang/en') {}

    /**
     * @param  array<string,mixed>  $params
     */
    public function render(string $key, array $params): string
    {
        $message = $this->lookup($key);

        // An unknown key returns itself: a missing translation should show up
        // in the panel as an obvious key, never as an exception in the middle
        // of an analysis run.
        if ($message === null) {
            return $key;
        }

        return self::replaceParams($message, $params);
    }

    private function lookup(string $key): ?string
    {
        $path = str_starts_with($key, self::NAMESPACE_PREFIX)
            ? substr($key, strlen(self::NAMESPACE_PREFIX))
            : $key;

        $segments = explode('.', $path);

        // basename() keeps a malformed key from reaching outside the language
        // directory.
        $value = DataFileLoader::load($this->directory.'/'.basename((string) array_shift($segments)).'.php');

        foreach ($segments as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return null;
            }

            $value = $value[$segment];
        }

        return is_string($value) ? $value : null;
    }

    /**
     * @param  array<string,mixed>  $params
     */
    private static function replaceParams(string $message, array $params): string
    {
        $replacements = [];

        foreach ($params as $name => $value) {
            $replacements[':'.$name] = match (true) {
                is_bool($value) => $value ? 'true' : 'false',
                is_scalar($value) => (string) $value,
                // Arrays and objects are data for the UI, not for a sentence.
                default => '',
            };
        }

        // strtr() with a map tries the longest key first, so :max cannot eat
        // the front of :maximum.
        return $replacements === [] ? $message : strtr($message, $replacements);
    }
}
