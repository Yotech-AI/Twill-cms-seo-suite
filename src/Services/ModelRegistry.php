<?php

namespace TwillSeo\Services;

use InvalidArgumentException;

/**
 * Wraps config('twill-seo.models') — the closed vocabulary of Twill models
 * this package manages SEO for, keyed by a stable string rather than a class
 * name. That key is the only thing the analyze endpoint accepts from the
 * client: see the config file's own comment ("never expose or accept class
 * names from the client").
 *
 * Reads config fresh on every call rather than caching it on the instance:
 * this is registered as a container singleton (one instance per request), and
 * a test that mutates config('twill-seo.models') mid-test must see that
 * change immediately rather than through a stale snapshot taken at
 * construction.
 */
final class ModelRegistry
{
    /** @var array<string,mixed> */
    private const DEFAULTS = [
        'title_attribute' => 'title',
        'schema_type' => 'WebPage',
        'sitemap' => true,
        'sitemap_images' => false,
        'image_role' => null,
        'url' => null,
        'content' => null,
        'content_fields' => [],
        // Which block editors feed the content analysis. Twill's single-editor
        // modules use 'default'; hosts with named editors (e.g. a hero editor
        // above a content editor) list them all, in reading order.
        'block_editors' => ['default'],
        'breadcrumbs' => null,
    ];

    /**
     * @return array<string,array<string,mixed>>
     */
    public function all(): array
    {
        $normalized = [];

        foreach ((array) config('twill-seo.models', []) as $key => $entry) {
            $normalized[$key] = [...self::DEFAULTS, ...(array) $entry];
        }

        return $normalized;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    /**
     * @return array<string,mixed>
     */
    public function get(string $key): array
    {
        $all = $this->all();

        if (! array_key_exists($key, $all)) {
            throw new InvalidArgumentException("Unknown twill-seo model registry key \"{$key}\".");
        }

        return $all[$key];
    }

    public function modelClass(string $key): string
    {
        return $this->get($key)['model'];
    }

    /**
     * The registry key for a model class or instance, or null when it is not
     * managed by this package at all — the fallback every caller here treats
     * as "nothing to do" rather than an error, since plenty of a host's
     * models will never be SEO-managed.
     */
    public function keyFor(object|string $model): ?string
    {
        $class = is_object($model) ? get_class($model) : $model;

        foreach ($this->all() as $key => $config) {
            if (($config['model'] ?? null) === $class) {
                return $key;
            }
        }

        return null;
    }
}
