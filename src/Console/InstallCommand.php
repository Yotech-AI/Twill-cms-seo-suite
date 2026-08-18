<?php

namespace TwillSeo\Console;

use Illuminate\Console\Command;

/**
 * One-step setup for a host application. Only publishes config — which models
 * to manage is a per-project decision the package cannot infer, so the
 * remaining steps are printed instead of run.
 */
class InstallCommand extends Command
{
    protected $signature = 'twill-seo:install';

    protected $description = 'Publish the Twill SEO config and report the remaining setup steps.';

    public function handle(): int
    {
        $this->info('Installing Twill SEO');
        $this->newLine();

        $this->call('vendor:publish', ['--tag' => 'twill-seo-config']);

        $this->newLine();
        $this->line('Remaining steps:');
        $this->line('  1. Register your models in config/twill-seo.php.');
        $this->line('  2. Add the SEO traits to each registered model.');
        $this->line("  3. Add SeoFields::fieldset() to each model's form.");
        $this->line('  4. Run php artisan migrate.');
        $this->line('  5. Add the SEO head component to your front-end layout.');

        return self::SUCCESS;
    }
}
