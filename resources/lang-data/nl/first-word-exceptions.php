<?php

/*
 * Words that are skipped when comparing how Dutch sentences begin.
 *
 * "De kat sliep. De hond blafte." does not start twice the same way — the
 * article is not what the sentence is about. Determiners and small numerals are
 * therefore stepped over so the comparison lands on kat and hond.
 *
 * Hand-compiled — see docs/lang-data-sources.md.
 */

return [
    // Articles and demonstratives.
    'de', 'het', 'een', 'dit', 'dat', 'deze', 'die',

    // The existential "er", which opens a Dutch sentence the way "there" opens
    // an English one.
    'er',

    // Possessive determiners.
    'zijn', 'haar', 'hun', 'mijn', 'jouw', 'uw', 'onze', 'ons',

    // Small cardinals, which open a list item as freely as an article does.
    'één', 'twee', 'drie', 'vier', 'vijf', 'zes', 'zeven', 'acht', 'negen', 'tien',
];
