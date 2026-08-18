<?php

namespace TwillSeo\Analysis\Language;

/**
 * Decides whether one sentence is in the passive voice.
 *
 * An interface rather than a shared implementation because the construction
 * differs per language: English builds it with an auxiliary plus a participle,
 * where other languages have their own markers entirely.
 */
interface PassiveVoiceDetector
{
    public function isPassive(string $sentence): bool;
}
