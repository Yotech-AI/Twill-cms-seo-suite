<?php

/*
 * Dutch abbreviations that end in a dot without ending a sentence.
 *
 * Without these, "Dhr. Jansen kwam binnen." counts as two sentences, which
 * throws off every per-sentence percentage the readability analysis reports.
 *
 * Only single-token forms are listed. Dutch writes several abbreviations with
 * internal dots (o.a., d.w.z., a.u.b., z.g.), and the sentence tokenizer reads
 * the word in front of the terminator, so those never match — exactly as the
 * English list's "e.g." never matches. They are left out rather than listed
 * inertly, because a list that quietly does nothing invites the next reader to
 * add more of the same.
 *
 * Every entry is a form that is never a Dutch word on its own, so treating it
 * as an abbreviation can only ever join a sentence that was one to begin with.
 *
 * Hand-compiled — see docs/lang-data-sources.md.
 */

return [
    // Aanspreekvormen en titels.
    'dhr', 'mevr', 'mw', 'mr', 'drs', 'ir', 'ing', 'prof', 'dr', 'bc',

    // Verwijzingen en opsommingen.
    'bijv', 'enz', 'etc', 'blz', 'nr', 'zgn', 'incl', 'excl', 'evt', 'resp',
    'ong', 'ca', 'vgl',

    // Maten, bedrijven en adressen.
    'tel', 'min', 'max', 'afd', 'str', 'plv', 'nv', 'bv',
];
