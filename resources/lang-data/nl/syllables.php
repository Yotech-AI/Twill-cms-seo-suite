<?php

/*
 * Dutch words whose spelling lies about how many syllables they have.
 *
 * The Dutch counter is a vowel-group counter with one refinement English does
 * not need: it splits a run of vowels wherever Dutch does not spell that pair
 * as a single sound. "eo", "ea", "ua" and "io" are never one Dutch vowel, so
 * "theater", "januari", "video" and "reactie" come out right without any entry
 * here — which is why this list is a fraction of the size the English one is.
 * The diaeresis does the same job wherever Dutch writes one ("ideeën",
 * "patiënt", "ruïne"), and the counter reads it as the syllable break it is.
 *
 * What is left is the opposite case: a pair that Dutch really does spell as one
 * sound, in a word where it happens to be two. "eu" is one sound in "geur" and
 * "kleur", but "mu-se-um" and "se-ri-eus" say it apart, and no rule can tell
 * the counter which is which.
 *
 * Inflections need their own entry, since the map is looked up on the exact
 * word.
 *
 * Hand-compiled by reading each word aloud and checking it against the way
 * Dutch hyphenates it — see docs/lang-data-sources.md.
 */

return [
    'deviations' => [
        // -eum: "eu" is one sound everywhere else, two beats here.
        'museum' => 3,
        'jubileum' => 4,
        'lyceum' => 3,
        'petroleum' => 4,

        // -ieu: written as the diphthong of "nieuw", spoken as i + eu.
        'serieus' => 3,
        'serieuze' => 4,
        'interieur' => 4,
        'ingenieur' => 4,
        'ingenieurs' => 4,
    ],
];
