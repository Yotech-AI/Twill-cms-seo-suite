<?php

namespace TwillSeo\Facades;

use Illuminate\Support\Facades\Facade;
use TwillSeo\SeoManager;
use TwillSeo\Services\Meta\PageSeo;
use TwillSeo\Services\Schema\SchemaBuilder;

/**
 * The host-facing facade over SeoManager. Family packages (this one
 * included) do not register a Laravel alias for their facades — there is no
 * `extra.laravel.aliases` entry in composer.json for this class, and none
 * should be added. A host imports it explicitly:
 *
 *     use TwillSeo\Facades\TwillSeo;
 *
 *     TwillSeo::page(title: 'Search results', noindex: true);
 *
 * @method static PageSeo for(object $model, ?string $locale = null)
 * @method static PageSeo page(?string $title = null, ?string $description = null, ?string $url = null, ?string $canonical = null, bool $noindex = false, bool $nofollow = false, ?int $shareMediaId = null, array $breadcrumbs = [], string $schemaType = 'WebPage')
 * @method static ?PageSeo current()
 * @method static SchemaBuilder graph()
 *
 * @see SeoManager
 */
final class TwillSeo extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SeoManager::class;
    }
}
