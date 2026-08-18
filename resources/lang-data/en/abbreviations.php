<?php

/*
 * English abbreviations that end in a dot without ending a sentence.
 *
 * Without these, "Dr. Smith arrived." counts as two sentences, which throws off
 * every per-sentence percentage the readability analysis reports.
 *
 * Deliberately left out: "no." (number). A sentence that genuinely ends in the
 * word "no" is far more common in web copy than "No. 5", and treating it as an
 * abbreviation would glue two real sentences together.
 *
 * Single-letter initials ("J. Doe") are handled by the tokenizer itself, which
 * is also why the entries containing internal dots (e.g., i.e.) never actually
 * fire — they are listed for completeness of the language data.
 *
 * Hand-compiled — see docs/lang-data-sources.md.
 */

return [
    // Titles.
    'mr', 'mrs', 'ms', 'mx', 'dr', 'prof', 'sr', 'jr', 'st',

    // Reference and measurement.
    'vs', 'etc', 'e.g', 'i.e', 'cf', 'al', 'pp', 'vol', 'fig', 'approx', 'est', 'min', 'max',

    // Organisations and addresses.
    'dept', 'inc', 'ltd', 'co', 'corp', 'ave', 'blvd',
];
