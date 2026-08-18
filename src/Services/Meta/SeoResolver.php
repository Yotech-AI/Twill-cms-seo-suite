<?php

namespace TwillSeo\Services\Meta;

use DateTimeImmutable;
use DateTimeInterface;
use Throwable;
use TwillSeo\Services\ModelRegistry;
use TwillSeo\Services\Resolvers\UrlResolver;
use TwillSeo\Services\Settings\SeoSettings;
use TwillSeo\Support\TranslatedAttribute;
use TwillSeo\Support\TwillMedia;

/**
 * THE single fallback-cascade authority for everything a rendered page's SEO
 * needs. Every precedence decision (verbatim seo_title vs. template, DB row
 * vs. config, per-field OG/twitter fallbacks, hreflang gating, breadcrumb
 * default vs. registry callback, OG image cascade) lives here and only here
 * — the Head view and the schema pieces just read the already-resolved
 * PageSeo/SchemaContext and decide whether to print, never which value.
 */
final class SeoResolver
{
    /**
     * og:locale bare-subtag map (Decisions: "en_US-style ... via a small
     * fixed map"). Anything else becomes 'xx_XX' (uppercase-doubled).
     */
    private const OG_LOCALE_MAP = [
        'en' => 'en_US',
        'nl' => 'nl_NL',
        'de' => 'de_DE',
    ];

    /**
     * Duplicates TwillSeo\Models\Behaviors\HasSeo::OG_IMAGE_ROLE's value.
     * PHP forbids reading a trait constant directly via the trait name from
     * outside a class that composes it ("Cannot access trait constant ...
     * directly" — see HasSeo's own doc comment); $model here is typed
     * `object`, not statically known to use HasSeo, so there is no concrete
     * class to read it through either. TwillSeo\Services\Form\SeoFields hits
     * the identical constraint and resolves it the same way. Keep in sync if
     * the role name ever changes.
     */
    private const OG_IMAGE_ROLE = 'twill_seo_og_image';

    public function __construct(
        private readonly SeoSettings $settings,
        private readonly ModelRegistry $registry,
        private readonly UrlResolver $urlResolver,
        private readonly TitleTemplate $titleTemplate,
        private readonly RobotsDirectives $robotsDirectives,
    ) {}

    public function forModel(object $model, ?string $locale = null): PageSeo
    {
        $locale ??= app()->getLocale();

        $key = $this->registry->keyFor($model);
        $config = $key !== null ? $this->registry->get($key) : [];

        $seo = method_exists($model, 'seo') ? $model->seo($locale) : null;
        $entry = method_exists($model, 'seoEntry') ? $model->seoEntry : null;

        $siteName = $this->settings->siteName();
        $tagline = $this->settings->tagline();
        $sep = $this->settings->separator();

        $titleAttribute = $config['title_attribute'] ?? 'title';
        $modelTitle = TranslatedAttribute::get($model, $titleAttribute, $locale) ?? '';

        $vars = ['title' => $modelTitle, 'sep' => $sep, 'site_name' => $siteName, 'tagline' => $tagline, 'page' => ''];

        $title = $this->resolveTitle($seo?->seo_title, $key, $vars);
        $description = $this->resolveDescription($seo?->seo_description, $key, $vars);

        $resolvedUrl = $this->urlResolver->resolve($model, $locale);
        $canonical = $this->nonEmpty($seo?->canonical_url) ?? $resolvedUrl;

        $robots = $this->robotsDirectives->for(
            (bool) ($entry?->robots_noindex ?? false),
            (bool) ($entry?->robots_nofollow ?? false),
            $this->settings->robotsDefaults(),
        );

        $schemaType = $this->nonEmpty($entry?->schema_type_override)
            ?? ($key !== null ? $this->settings->schemaType($key) : 'WebPage');

        $ogTitle = $this->nonEmpty($seo?->og_title) ?? $title;
        $ogDescription = $this->nonEmpty($seo?->og_description) ?? $description;
        $ogImage = $this->resolveShareImage($model, $config);

        [$twitterTitle, $twitterDescription] = $this->resolveTwitter(
            $this->nonEmpty($seo?->twitter_title),
            $this->nonEmpty($seo?->twitter_description),
            $ogTitle,
            $ogDescription,
        );

        return new PageSeo(
            title: $title,
            description: $description,
            url: $resolvedUrl,
            canonicalUrl: $canonical,
            robots: $robots,
            ogTitle: $ogTitle,
            ogDescription: $ogDescription,
            ogImage: $ogImage,
            ogType: PageSeo::isArticleType($schemaType) ? 'article' : 'website',
            ogLocale: $this->ogLocale($locale),
            twitterTitle: $twitterTitle,
            twitterDescription: $twitterDescription,
            alternates: $this->resolveAlternates($model, $locale, $resolvedUrl),
            publishedTime: $this->toDateTime($model->published_at ?? $model->created_at ?? null),
            modifiedTime: $this->toDateTime($model->updated_at ?? null),
            schemaType: $schemaType,
            registryKey: $key,
            model: $model,
            breadcrumbs: $this->resolveBreadcrumbs($model, $config, $locale, $modelTitle),
        );
    }

    /**
     * The manual path for a non-model route (search results, a static
     * contact page, ...). $title runs through the SAME template engine as a
     * model's own {title} variable would — there is no per-entry "seo_title"
     * concept to short-circuit it here, so every manual page gets the site's
     * default title template applied for consistent branding. A host that
     * genuinely wants a verbatim, un-templated title can still reach for the
     * Head component's own $title override, which applies after this.
     *
     * @param  list<array{0: string, 1: ?string}>  $breadcrumbs
     */
    public function forPage(
        ?string $title = null,
        ?string $description = null,
        ?string $url = null,
        ?string $canonical = null,
        bool $noindex = false,
        bool $nofollow = false,
        ?int $shareMediaId = null,
        array $breadcrumbs = [],
        string $schemaType = 'WebPage',
    ): PageSeo {
        $siteName = $this->settings->siteName();
        $tagline = $this->settings->tagline();
        $sep = $this->settings->separator();
        $rawTitle = (string) $title;

        $resolvedTitle = $this->titleTemplate->render(
            (string) config('twill-seo.title.default_template', '{title} {sep} {site_name}'),
            ['title' => $rawTitle, 'sep' => $sep, 'site_name' => $siteName, 'tagline' => $tagline, 'page' => ''],
        );

        $resolvedDescription = $this->nonEmpty($description);
        $resolvedCanonical = $this->nonEmpty($canonical) ?? $url;

        $robots = $this->robotsDirectives->for($noindex, $nofollow, $this->settings->robotsDefaults());

        $ogImage = TwillMedia::fromMediaId($shareMediaId);

        [$twitterTitle, $twitterDescription] = $this->resolveTwitter(
            null,
            null,
            $resolvedTitle,
            $resolvedDescription,
        );

        return new PageSeo(
            title: $resolvedTitle,
            description: $resolvedDescription,
            url: $url,
            canonicalUrl: $resolvedCanonical,
            robots: $robots,
            ogTitle: $resolvedTitle,
            ogDescription: $resolvedDescription,
            ogImage: $ogImage,
            ogType: PageSeo::isArticleType($schemaType) ? 'article' : 'website',
            ogLocale: $this->ogLocale(app()->getLocale()),
            twitterTitle: $twitterTitle,
            twitterDescription: $twitterDescription,
            alternates: [],
            publishedTime: null,
            modifiedTime: null,
            schemaType: $schemaType,
            registryKey: null,
            model: null,
            breadcrumbs: $breadcrumbs !== [] ? array_values($breadcrumbs) : $this->defaultBreadcrumbs($rawTitle),
        );
    }

    /**
     * @param  array<string,string>  $vars
     */
    private function resolveTitle(?string $seoTitle, ?string $registryKey, array $vars): string
    {
        $verbatim = $this->nonEmpty($seoTitle);

        if ($verbatim !== null) {
            return $verbatim;
        }

        $template = $registryKey !== null
            ? $this->settings->titleTemplate($registryKey)
            : (string) config('twill-seo.title.default_template', '{title} {sep} {site_name}');

        return $this->titleTemplate->render($template, $vars);
    }

    /**
     * @param  array<string,string>  $vars
     */
    private function resolveDescription(?string $seoDescription, ?string $registryKey, array $vars): ?string
    {
        $verbatim = $this->nonEmpty($seoDescription);

        if ($verbatim !== null) {
            return $verbatim;
        }

        $template = $registryKey !== null ? $this->settings->descriptionTemplate($registryKey) : null;

        return $template !== null ? $this->titleTemplate->render($template, $vars) : null;
    }

    /**
     * Twitter tags render only when the twitter feature is on AND there is
     * something distinct from OG to say (or OG has nothing to differ from
     * at all) — see the brief's own decision. $ogTitle/$ogDescription are
     * always the fully-resolved OG values regardless of whether the og
     * FEATURE is on, which is what makes this correct even when a host runs
     * with `og` off and `twitter` on (twitter still gets a real value to
     * fall back to instead of the (also-suppressed) og fields).
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function resolveTwitter(?string $twitterTitleRaw, ?string $twitterDescriptionRaw, string $ogTitle, ?string $ogDescription): array
    {
        if (! $this->settings->feature('twitter')) {
            return [null, null];
        }

        $ogIsOn = $this->settings->feature('og');

        $titleDiffers = $twitterTitleRaw !== null && $twitterTitleRaw !== $ogTitle;
        $descriptionDiffers = $twitterDescriptionRaw !== null && $twitterDescriptionRaw !== $ogDescription;

        if (! $ogIsOn || $titleDiffers || $descriptionDiffers) {
            return [$twitterTitleRaw ?? $ogTitle, $twitterDescriptionRaw ?? $ogDescription];
        }

        return [null, null];
    }

    /**
     * @param  array<string,mixed>  $config
     * @return ?array{url: string, width: int, height: int}
     */
    private function resolveShareImage(object $model, array $config): ?array
    {
        // HasSeo's own dedicated OG/Twitter share role wins...
        $fromOgRole = TwillMedia::fromRole($model, self::OG_IMAGE_ROLE);

        if ($fromOgRole !== null) {
            return $fromOgRole;
        }

        // ...then the registry's own per-type image_role on the same model...
        $registryRole = $config['image_role'] ?? null;

        if (is_string($registryRole) && $registryRole !== '') {
            $fromRegistryRole = TwillMedia::fromRole($model, $registryRole);

            if ($fromRegistryRole !== null) {
                return $fromRegistryRole;
            }
        }

        // ...then the install-wide settings default share image.
        return TwillMedia::fromMediaId($this->settings->defaultShareMediaId());
    }

    /**
     * $currentLocaleUrl is whatever forModel() already resolved for
     * $currentLocale (for canonical/url) — reused here instead of asking
     * UrlResolver to resolve that exact same model+locale pair a second
     * time.
     *
     * @return array<string,string>
     */
    private function resolveAlternates(object $model, string $currentLocale, ?string $currentLocaleUrl): array
    {
        if (! $this->settings->feature('hreflang')) {
            return [];
        }

        $locales = array_values(array_unique(array_map(strval(...), (array) config('translatable.locales', [$currentLocale]))));

        $byLocale = [];

        foreach ($locales as $locale) {
            $url = $locale === $currentLocale ? $currentLocaleUrl : $this->urlResolver->resolve($model, $locale);

            if ($url !== null) {
                $byLocale[$locale] = $url;
            }
        }

        if (count($byLocale) < 2) {
            return [];
        }

        // x-default points at the first CONFIGURED locale's URL — falling
        // back to whichever resolved first if that particular locale itself
        // did not resolve, rather than being left undefined.
        $default = $byLocale[$locales[0]] ?? reset($byLocale);

        return [...$byLocale, 'x-default' => $default];
    }

    /**
     * @param  array<string,mixed>  $config
     * @return list<array{0: string, 1: ?string}>
     */
    private function resolveBreadcrumbs(object $model, array $config, string $locale, string $rawTitle): array
    {
        $callback = $config['breadcrumbs'] ?? null;

        if (is_callable($callback)) {
            try {
                $result = $callback($model, $locale);
            } catch (Throwable $e) {
                report($e);
                $result = null;
            }

            if (is_array($result) && $result !== []) {
                return array_values($result);
            }
        }

        return $this->defaultBreadcrumbs($rawTitle);
    }

    /**
     * @return list<array{0: string, 1: ?string}>
     */
    private function defaultBreadcrumbs(string $currentTitle): array
    {
        return [
            [__('Home'), (string) config('app.url')],
            [$currentTitle !== '' ? $currentTitle : __('Page'), null],
        ];
    }

    /**
     * Bare-subtag og:locale mapping (see the class-level decision comment).
     */
    private function ogLocale(string $locale): string
    {
        $bare = strtolower((string) preg_replace('/[-_].*$/', '', $locale));

        if ($bare === '') {
            return self::OG_LOCALE_MAP['en'];
        }

        return self::OG_LOCALE_MAP[$bare] ?? $bare.'_'.strtoupper($bare);
    }

    private function toDateTime(mixed $value): ?DateTimeInterface
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value;
        }

        try {
            return new DateTimeImmutable((string) $value);
        } catch (Throwable) {
            return null;
        }
    }

    private function nonEmpty(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
