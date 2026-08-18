<?php

namespace TwillSeo\Services\Meta;

use DateTimeInterface;

/**
 * Everything the Head view needs, fully resolved by SeoResolver — the single
 * cascade authority (see its own doc comment). No fallback/precedence logic
 * belongs anywhere downstream of this object: the Blade view only ever
 * checks "is this null/empty" to decide whether to print a tag, it never
 * decides WHICH value to print.
 *
 * A field being null (or, for collections, empty) is itself the "should this
 * render at all" signal for several fields, folded in here rather than left
 * for the view to re-derive:
 *  - description: null when neither seo_description nor a rendered
 *    description_template produced anything (Yoast parity — no synthetic
 *    description is invented).
 *  - twitterTitle/twitterDescription: null unless the twitter feature is on
 *    AND they differ from ogTitle/ogDescription (or OG has nothing to differ
 *    from) — see SeoResolver's own twitter cascade.
 *  - alternates: empty unless the hreflang feature is on AND at least two
 *    locales resolved a URL (x-default included in the same map when so).
 * ogTitle/ogDescription/ogImage/ogType/ogLocale are, by contrast, ALWAYS
 * resolved regardless of the `og` feature toggle — schema pieces (e.g.
 * PrimaryImagePiece) may need the image even when the og: meta tags
 * themselves are switched off, so gating their PRINTING is the view's job
 * (it checks the og feature directly), not something baked into nullability
 * here.
 */
final readonly class PageSeo
{
    /**
     * @param  ?array{url: string, width: int, height: int}  $ogImage
     * @param  array<string,string>  $alternates  locale => URL, plus an
     *                                            'x-default' entry when hreflang is active; empty when it is not.
     * @param  list<array{0: string, 1: ?string}>  $breadcrumbs  [title, url]
     *                                                           pairs, url null on the last (current-page) item.
     */
    public function __construct(
        public string $title,
        public ?string $description,
        public ?string $url,
        public ?string $canonicalUrl,
        public string $robots,
        public ?string $ogTitle,
        public ?string $ogDescription,
        public ?array $ogImage,
        public string $ogType,
        public string $ogLocale,
        public ?string $twitterTitle,
        public ?string $twitterDescription,
        public array $alternates,
        public ?DateTimeInterface $publishedTime,
        public ?DateTimeInterface $modifiedTime,
        public string $schemaType,
        public ?string $registryKey,
        public ?object $model,
        public array $breadcrumbs,
    ) {}

    /**
     * `summary_large_image` whenever there is an image, else `summary` (the
     * brief's own fixed rule) — a method rather than a constructor field
     * since it is a pure, one-line function of $ogImage with nothing else to
     * cascade through; keeping it here rather than inline in the Blade view
     * still means only one place ever decides it.
     */
    public function twitterCard(): string
    {
        return $this->ogImage !== null ? 'summary_large_image' : 'summary';
    }

    /**
     * Whether a schema.org type is Article or one of its subtypes
     * (NewsArticle, BlogPosting, TechArticle, ...) — the single
     * implementation of that pattern match. SeoResolver::forModel()/
     * forPage() call this to derive PageSeo::ogType from PageSeo::schemaType
     * in the first place, and withOverrides() calls it again to keep
     * ogType consistent when a $type override changes schemaType after the
     * fact. Kept here (not on SeoResolver, where it originally lived) so
     * both call sites can share one implementation without PageSeo having to
     * depend on SeoResolver to reach it.
     */
    public static function isArticleType(string $schemaType): bool
    {
        return $schemaType === 'Article'
            || str_ends_with($schemaType, 'Article')
            || str_ends_with($schemaType, 'Posting');
    }

    /**
     * A copy with title/description/schemaType swapped for the Head
     * component's own $title/$description/$type constructor overrides —
     * applied AFTER the full resolver cascade, on top of it, never threaded
     * through SeoResolver itself. Deliberately narrow: overriding the
     * visible title does not reach into ogTitle/twitterTitle (each has its
     * own independent, already-resolved value), matching the brief's own
     * "hard overrides applied on top of the resolved PageSeo" wording rather
     * than inventing a broader cascade this class was never asked for.
     *
     * ogType IS recomputed from the (possibly overridden) schema type,
     * though — unlike ogTitle/ogDescription it is not independently
     * resolved data of its own, it is a pure function of schemaType, and
     * og:type/the article: meta tags/ArticlePiece's own gate all read
     * ogType rather than schemaType directly. Leaving it copied verbatim
     * from $this would make a `$type="Article"` override silently do
     * nothing everywhere that matters.
     *
     * All-null is the common case (no overrides given) and returns $this
     * unchanged rather than an equal-but-distinct clone.
     */
    public function withOverrides(?string $title, ?string $description, ?string $schemaType): self
    {
        if ($title === null && $description === null && $schemaType === null) {
            return $this;
        }

        $resolvedSchemaType = $schemaType ?? $this->schemaType;

        return new self(
            title: $title ?? $this->title,
            description: $description ?? $this->description,
            url: $this->url,
            canonicalUrl: $this->canonicalUrl,
            robots: $this->robots,
            ogTitle: $this->ogTitle,
            ogDescription: $this->ogDescription,
            ogImage: $this->ogImage,
            ogType: self::isArticleType($resolvedSchemaType) ? 'article' : 'website',
            ogLocale: $this->ogLocale,
            twitterTitle: $this->twitterTitle,
            twitterDescription: $this->twitterDescription,
            alternates: $this->alternates,
            publishedTime: $this->publishedTime,
            modifiedTime: $this->modifiedTime,
            schemaType: $resolvedSchemaType,
            registryKey: $this->registryKey,
            model: $this->model,
            breadcrumbs: $this->breadcrumbs,
        );
    }
}
