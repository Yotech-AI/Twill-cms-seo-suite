<?php

/*
 * The verbs that carry an English periphrastic passive.
 *
 * "be" builds the ordinary passive ("was written"), "get" the colloquial one
 * ("got promoted"). Both are here because both are passive to a reader.
 *
 * "have/has/had" are deliberately absent: they mark the perfect, not the
 * passive. "The team has published the results" is active, while
 * "The results have been published" is caught through "been", which is here.
 *
 * Hand-compiled — see docs/lang-data-sources.md.
 */

return [
    // Forms of "to be".
    'am', 'is', 'are', 'was', 'were', 'be', 'been', 'being',

    // Forms of "to get".
    'get', 'gets', 'got', 'gotten', 'getting',
];
