# Twill CMS SEO Suite

A Yoast-style SEO suite for [Twill CMS](https://twillcms.com): content analysis with traffic lights, meta tags, XML sitemaps and schema.org structured data.

**Requires PHP 8.3+, Laravel 12 or 13, Twill 3.6+.**

## Installation

```bash
composer require yotech-ai/twill-cms-seo-suite
```

## Status

Under construction. This package currently boots, registers itself on the shared Plugins page, and serves a placeholder settings page. Content analysis, meta tags, sitemaps and structured data all land in upcoming tasks.

## The Plugins page

The shared Plugins-page code ships built in — no separate dependency required. It adds a **Plugins** entry to the admin navigation (next to Media Library) listing every installed Yotech plugin, with a link to each plugin's own admin screen. Nothing to configure.

### How it works

- Shared state lives in the Laravel container under two well-known keys: `yotech.twill-plugins.registry` (an `ArrayObject` of plugin manifests, plain arrays only) and `yotech.twill-plugins.page-owner` (the provider class that owns the page).
- The **first** Yotech plugin provider to register binds both keys, registers the `plugins` admin route/controller/view, and owns the page.
- Every **later** Yotech plugin provider — even one vendoring a differently-namespaced copy of this same code, as this package does — detects the existing bindings and only adds its own manifest to the registry, so an install with several Yotech plugins still shows a single Plugins page listing all of them.

## License

MIT
