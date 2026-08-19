<?php

/*
 * German words whose spelling lies about how many syllables they have.
 *
 * The German counter is a vowel-group counter with one refinement English does
 * not need: it splits a run of vowels wherever German does not spell that pair
 * as a single sound. "io", "ea" and "ua" are never one German vowel, so
 * "Na-ti-on", "The-a-ter" and "Si-tu-a-ti-on" come out right without any entry
 * here — which is what makes the whole -tion family work by rule rather than by
 * list.
 *
 * What is left is the opposite case: a pair German really does spell as one
 * sound, in a word where it happens to be two.
 *
 *  - "eu" is one sound in "heute" and "neu", two beats in "Mu-se-um";
 *  - "ee" is one sound in "Meer" and "Idee", two beats in "I-de-en";
 *  - "ie" is one sound in "Liebe" and "Studie", two beats in the -ien plural
 *    of nouns whose singular ends in -ie ("Fa-mi-li-en", "Fe-ri-en").
 *
 * That last group cannot be ruled. A blanket "-ien is two beats" would break
 * "schien", "erschien" and "Wien", which are one beat each and common enough
 * in German prose to matter, so the plurals are listed one by one instead.
 * The list is not exhaustive and does not try to be: an odd word counted a beat
 * short moves a reading-ease score by a fraction of a point.
 *
 * Hand-compiled by reading each word aloud and checking it against the way
 * German hyphenates it — see docs/lang-data-sources.md.
 */

return [
    'deviations' => [
        // -eum and -äum: "eu" and "äu" are one sound everywhere else.
        'museum' => 3,
        'museen' => 3,
        'jubiläum' => 4,
        'jubiläums' => 4,

        // -een: two vowels of the same letter, said apart.
        'ideen' => 3,
        'alleen' => 3,

        // The -ien plurals of nouns ending in -ie.
        'familien' => 4,
        'ferien' => 3,
        'linien' => 3,
        'studien' => 3,
        'serien' => 3,
        'kopien' => 3,
        'aktien' => 3,
        'italien' => 4,
        'spanien' => 3,
        'prinzipien' => 4,
        'kategorien' => 5,
        'materialien' => 6,
        'immobilien' => 5,
    ],
];
