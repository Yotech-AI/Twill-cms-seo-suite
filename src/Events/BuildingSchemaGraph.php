<?php

namespace TwillSeo\Events;

use TwillSeo\Services\Schema\SchemaContext;

/**
 * Dispatched by SchemaBuilder::build() once every built-in piece, every
 * config('twill-seo.schema.pieces') piece and every piece pushed onto
 * SeoManager::graph() has already contributed its nodes — the last chance to
 * mutate or extend the graph before it is serialized to JSON-LD.
 *
 * $graph is deliberately a public, non-readonly property rather than a
 * return value: a PHP object passed to event()/Dispatcher::dispatch() is
 * passed by handle, so a listener mutating $event->graph in place (push a
 * node, or reassign the whole array to edit/remove existing ones) is visible
 * to SchemaBuilder the moment dispatch() returns — no listener return-value
 * plumbing needed. $context stays readonly: a listener may read it (site
 * URL, locale, the resolved PageSeo, settings) but changing "what page this
 * is" mid-build would leave the graph inconsistent with the meta tags
 * already rendered around it.
 */
final class BuildingSchemaGraph
{
    /**
     * @param  list<array<string,mixed>>  $graph
     */
    public function __construct(
        public array $graph,
        public readonly SchemaContext $context,
    ) {}
}
