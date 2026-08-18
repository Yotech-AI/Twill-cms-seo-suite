<?php

namespace TwillSeo\Console;

use Illuminate\Console\Command;
use TwillSeo\PluginPage\TwillPluginServiceProvider;

/**
 * Cheap sanity check for the shared Plugins-page wiring and package config,
 * run by hand after install. Always exits 0 — an empty model registry is a
 * valid (if inert) install, not a failure.
 */
class DoctorCommand extends Command
{
    protected $signature = 'twill-seo:doctor';

    protected $description = 'Diagnose the Twill SEO environment (Plugins-page wiring and config).';

    public function handle(): int
    {
        $this->info('Twill SEO doctor');
        $this->newLine();

        $registryBound = app()->bound(TwillPluginServiceProvider::REGISTRY_BINDING);
        $this->printCheck(
            $registryBound,
            'Plugins-page registry binding is present.',
            'Plugins-page registry binding is missing — is TwillSeoServiceProvider loaded?'
        );

        $registered = false;

        if ($registryBound) {
            $registry = app(TwillPluginServiceProvider::REGISTRY_BINDING);
            $registered = isset($registry['yotech-ai/twill-cms-seo-suite']);
        }

        $this->printCheck(
            $registered,
            'Manifest is registered on the Plugins page.',
            'Manifest is not registered — is TwillSeoServiceProvider loaded?'
        );

        $configLoaded = config()->has('twill-seo');
        $this->printCheck(
            $configLoaded,
            'Config is loaded.',
            'Config is not loaded — is TwillSeoServiceProvider loaded?'
        );

        $models = config('twill-seo.models', []);

        if ($models === []) {
            $this->warn('  [ ! ] twill-seo.models is empty — no models are managed yet.');
        } else {
            $this->line('  [OK ] twill-seo.models has '.count($models).' registered model(s).');
        }

        return self::SUCCESS;
    }

    protected function printCheck(bool $pass, string $okMessage, string $failMessage): void
    {
        if ($pass) {
            $this->line('  [OK ] '.$okMessage);
        } else {
            $this->error('  [XXX] '.$failMessage);
        }
    }
}
