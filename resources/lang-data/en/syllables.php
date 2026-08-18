<?php

/*
 * Words whose spelling lies about how many syllables they have.
 *
 * The counter works from vowel groups, which is right for the overwhelming
 * majority of English words. These are the ones it gets wrong, in both
 * directions:
 *
 *  - written with more vowels than are spoken ("business" is bizness, and the
 *    "-ery" of "every" is one beat, not two);
 *  - written with fewer vowel groups than are spoken, because two vowels that
 *    sit next to each other are pronounced apart ("i-de-a", "vi-de-o").
 *
 * Inflections need their own entry, since the map is looked up on the exact
 * word: "create" is here, so "created" and "creating" are too.
 *
 * Hand-compiled by reading each word aloud and checking it against a
 * pronunciation dictionary's syllable division — see docs/lang-data-sources.md.
 */

return [
    'deviations' => [
        // Spoken with fewer syllables than they are spelled.
        'business' => 2,
        'businesses' => 3,
        'chocolate' => 2,
        'different' => 2,
        'evening' => 2,
        'every' => 2,
        'everything' => 3,
        'interesting' => 3,
        'rhythm' => 2,
        'wednesday' => 2,

        // Adjacent vowels that are pronounced apart.
        'area' => 3,
        'areas' => 3,
        'audio' => 3,
        'create' => 2,
        'created' => 3,
        'creating' => 3,
        'experience' => 4,
        'idea' => 3,
        'ideas' => 3,
        'lion' => 2,
        'maybe' => 2,
        'piano' => 3,
        'poem' => 2,
        'quiet' => 2,
        'quietly' => 3,
        'radio' => 3,
        'recipe' => 3,
        'recipes' => 3,
        'science' => 2,
        'scientific' => 4,
        'video' => 3,
        'videos' => 3,
    ],
];
