<?php

/*
 * Words that are skipped when comparing how sentences begin.
 *
 * "The cat sat. The dog barked." does not start twice the same way — the
 * article is not what the sentence is about. Determiners and small numerals are
 * therefore stepped over so the comparison lands on cat and dog.
 *
 * Hand-compiled — see docs/lang-data-sources.md.
 */

return [
    // Articles and demonstratives.
    'the', 'a', 'an', 'this', 'that', 'these', 'those',

    // Pronouns that stand in for a determiner.
    'it', 'its', 'his', 'her', 'their', 'my', 'our', 'your',

    // Small cardinals, which open a list item as freely as an article does.
    'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten',
];
