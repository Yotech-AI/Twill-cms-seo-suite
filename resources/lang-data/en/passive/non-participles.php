<?php

/*
 * Words that end in -ed without being a past participle.
 *
 * The passive detector treats any word of four letters or more ending in -ed as
 * a regular participle. That rule is right far more often than it is wrong, but
 * it would read "There is a need for change" and "The result is indeed good" as
 * passive, so the exceptions are listed here.
 *
 * Three-letter words (bed, red, led, fed, wed) never reach this list: the
 * length rule already excludes them, and led/fed/wed are genuine participles
 * that must keep matching through the irregular list.
 *
 * Two kinds of word are collected:
 *  - base forms and nouns that happen to end in -ed (need, speed, breed, deed);
 *  - adjectives with the pronounced -ed ending that were never verbs, either
 *    ancient (naked, wicked, sacred) or formed from a noun (talented, skilled).
 *
 * Deliberately absent: tired, bored, excited, married and the rest of the
 * participles that also work as adjectives. They ARE participles, and the
 * detector handles their adjectival use through the degree-adverb rule instead
 * ("was very excited" is not passive, "was excited by the news" is).
 *
 * Hand-compiled — see docs/lang-data-sources.md.
 */

return [
    // Base forms and nouns ending in -ed.
    'bleed', 'breed', 'creed', 'deed', 'embed', 'exceed', 'feed', 'greed', 'indeed', 'misdeed',
    'moped', 'need', 'proceed', 'seed', 'shred', 'sled', 'speed', 'steed', 'succeed', 'tweed',
    'weed',

    // Numerals and other nouns.
    'hatred', 'hundred', 'kindred',

    // Adjectives that were never verbs.
    'bearded', 'beloved', 'crooked', 'gifted', 'jagged', 'moneyed', 'naked', 'ragged', 'rugged',
    'sacred', 'skilled', 'talented', 'wicked', 'wooded', 'wretched',
];
