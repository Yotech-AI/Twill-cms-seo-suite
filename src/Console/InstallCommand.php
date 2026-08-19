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

        $adminPath = rtrim(ltrim((string) config('twill.admin_app_path', 'admin'), '/'), '/');

        $this->newLine();
        $this->line('Remaining steps:');
        $this->line('  1. Register each model to manage in the `models` key of config/twill-seo.php.');
        $this->line('  2. Add TwillSeo\\Models\\Behaviors\\HasSeo to each registered model, and');
        $this->line('     TwillSeo\\Repositories\\Behaviors\\HandleSeo to its repository (after HandleTranslations,');
        $this->line('     if the repository uses it — see HandleSeo\'s own doc comment for why the order matters).');
        $this->line("  3. Add TwillSeo\\Services\\Form\\SeoFields::fieldset() to each model's form fields.");
        $this->line('  4. Run php artisan migrate.');
        $this->line('  5. Add <x-twill-seo::head /> to your public front-end layout.');
        $this->line("  6. Visit /{$adminPath}/seo to review settings — site identity, content-type templates,");
        $this->line('     features and advanced options.');
        $this->newLine();
        $this->line('Run php artisan twill-seo:doctor at any point to check the install.');

        return self::SUCCESS;
    }
}
