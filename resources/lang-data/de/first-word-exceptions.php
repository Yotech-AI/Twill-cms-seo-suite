<?php

/*
 * Words that are skipped when comparing how German sentences begin.
 *
 * "Die Katze schlief. Der Hund bellte." does not start twice the same way —
 * the article is not what the sentence is about. Determiners and small numerals
 * are therefore stepped over so the comparison lands on Katze and Hund.
 *
 * German declines its determiners, so each case form has to be listed: a
 * sentence opening with "Dem" is exactly as uninformative as one opening with
 * "Der".
 *
 * Hand-compiled — see docs/lang-data-sources.md.
 */

return [
    // Definite and indefinite articles.
    'der', 'die', 'das', 'den', 'dem', 'des',
    'ein', 'eine', 'einen', 'einem', 'einer', 'eines',

    // Demonstratives.
    'dieser', 'diese', 'dieses', 'diesen', 'diesem',

    // Possessive determiners.
    'sein', 'seine', 'seinen', 'ihr', 'ihre', 'ihren',
    'mein', 'meine', 'unser', 'unsere',

    // Small cardinals, which open a list item as freely as an article does.
    'eins', 'zwei', 'drei', 'vier', 'fünf', 'sechs', 'sieben', 'acht', 'neun',
    'zehn',
];
