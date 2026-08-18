<?php

namespace TwillSeo\Services\Schema;

use TwillSeo\Contracts\GraphPiece;
use TwillSeo\Events\BuildingSchemaGraph;
use TwillSeo\Services\Schema\Pieces\ArticlePiece;
use TwillSeo\Services\Schema\Pieces\BreadcrumbListPiece;
use TwillSeo\Services\Schema\Pieces\OrganizationPiece;
use TwillSeo\Services\Schema\Pieces\PersonPiece;
use TwillSeo\Services\Schema\Pieces\PrimaryImagePiece;
use TwillSeo\Services\Schema\Pieces\WebPagePiece;
use TwillSeo\Services\Schema\Pieces\WebSitePiece;

/**
 * Assembles the final JSON-LD document: the seven built-in pieces (in a
 * fixed order — see builtins()), then every class named in
 * config('twill-seo.schema.pieces'), then every piece a host pushed via
 * SeoManager::graph()->push() before render, then a BuildingSchemaGraph
 * event giving listeners the last word. Cross-references are by @id only —
 * a flat graph, no node ever nests another node's full body inside itself.
 *
 * One instance lives for the lifetime of SeoManager (itself a per-request
 * singleton) — see SeoManager::graph()'s own doc comment for why it is
 * never reset between for()/page() calls within one request.
 */
final class SchemaBuilder
{
    /** @var list<GraphPiece|class-string<GraphPiece>> */
    private array $pushed = [];

    public function push(GraphPiece|string $piece): static
    {
        $this->pushed[] = $piece;

        return $this;
    }

    /**
     * @return array{'@context': string, '@graph': list<array<string,mixed>>}
     */
    public function build(SchemaContext $context): array
    {
        $nodes = [];

        foreach ($this->builtins() as $piece) {
            array_push($nodes, ...$piece->pieces($context));
        }

        foreach ((array) config('twill-seo.schema.pieces', []) as $class) {
            array_push($nodes, ...$this->resolve($class)->pieces($context));
        }

        foreach ($this->pushed as $piece) {
            array_push($nodes, ...$this->resolve($piece)->pieces($context));
        }

        $event = new BuildingSchemaGraph($nodes, $context);
        event($event);

        return ['@context' => 'https://schema.org', '@graph' => $event->graph];
    }

    /**
     * @return list<GraphPiece>
     */
    private function builtins(): array
    {
        return [
            new OrganizationPiece,
            new PersonPiece,
            new WebSitePiece,
            new WebPagePiece,
            new ArticlePiece,
            new BreadcrumbListPiece,
            new PrimaryImagePiece,
        ];
    }

    private function resolve(GraphPiece|string $piece): GraphPiece
    {
        return is_string($piece) ? app($piece) : $piece;
    }
}
