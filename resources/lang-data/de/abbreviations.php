<?php

/*
 * German abbreviations that end in a dot without ending a sentence.
 *
 * Without these, "Dr. Schmidt kam an." counts as two sentences, which throws
 * off every per-sentence percentage the readability analysis reports.
 *
 * Only single-token forms are listed. German writes many of its abbreviations
 * with internal dots (z. B., d. h., u. a., i. d. R.), and the sentence
 * tokenizer reads the word in front of the terminator, so those never match —
 * exactly as the English list's "e.g." never matches. They are left out rather
 * than listed inertly, because a list that quietly does nothing invites the
 * next reader to add more of the same.
 *
 * Every entry is a form that is never a German word on its own, so treating it
 * as an abbreviation can only ever join a sentence that was one to begin with.
 * "Art." is deliberately left out for that reason: a sentence ending in the
 * noun "Art" is far more common in web copy than a numbered article, and
 * listing it would glue two real sentences together.
 *
 * Hand-compiled — see docs/lang-data-sources.md.
 */

return [
    // Titel und Anreden.
    'dr', 'prof', 'dipl', 'ing', 'hr', 'fr',

    // Verweise und Aufzählungen.
    'bzw', 'ca', 'evtl', 'ggf', 'inkl', 'exkl', 'sog', 'usw', 'vgl', 'zzgl',
    'abzgl', 'einschl', 'insb', 'bspw', 'ggü',

    // Maße, Zahlen und Adressen.
    'nr', 'str', 'abs', 'bd', 'max', 'min', 'mio', 'mrd', 'tsd', 'jhd',
];
