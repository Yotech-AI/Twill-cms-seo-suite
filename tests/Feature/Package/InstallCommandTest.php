<?php

it('publishes the config and prints the remaining setup steps', function () {
    $this->artisan('twill-seo:install')
        ->expectsOutputToContain('Register each model')
        ->expectsOutputToContain('php artisan migrate')
        ->expectsOutputToContain('<x-twill-seo::head />')
        ->expectsOutputToContain('twill-seo:doctor')
        ->assertExitCode(0);
});
