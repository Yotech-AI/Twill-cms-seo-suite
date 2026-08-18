<?php

namespace TwillSeo\Analysis\Contracts;

/**
 * Turns a message key plus its parameters into the sentence an editor reads.
 * An interface so the engine can render messages with no translator present
 * and a Laravel host can hand it the real one later.
 */
interface MessageRenderer
{
    /**
     * @param  array<string,mixed>  $params
     */
    public function render(string $key, array $params): string;
}
