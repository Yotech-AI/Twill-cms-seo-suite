<?php

/*
 * Dutch words that look like a past participle and are not one.
 *
 * The detector reads any word of five letters or more that starts with ge-,
 * be-, ver-, ont-, her- or er- (optionally behind a separable prefix) and ends
 * in -d, -t or -en as a participle. That rule is right far more often than it
 * is wrong, but Dutch builds a great many ordinary nouns and adjectives out of
 * exactly those pieces, and without this list "Er is geen geluid" and "Dit is
 * een goed voorbeeld" would both read as passive.
 *
 * Four groups are collected:
 *
 *  1. Nouns built with ge-, be- or ver- that end in -d or -t: gebied, geluid,
 *     gewicht, bericht, beeld, beleid, verband, verstand, voorbeeld.
 *  2. Nouns built with ge- whose plural ends in -en: gedachten, gevolgen,
 *     gebouwen, geluiden. The determiner guard already covers most of their
 *     uses ("de gebouwen"), so only the common ones are listed.
 *  3. Verb forms that are not participles but hit the shape anyway: "geeft" is
 *     the present tense of geven, "gelden" and "gebeuren" are infinitives.
 *  4. Present participles and -end adjectives. Dutch forms them from the bare
 *     infinitive plus -d ("verrassend", "vervelend"), which lands on the same
 *     shape as a past participle of a verb whose stem ends in -en ("geopend",
 *     "getekend"). Those two really are spelled alike, so the difference cannot
 *     be ruled — the common present participles are listed instead.
 *
 * Only words the shape rule would actually catch are listed. "gewoon",
 * "gezellig", "gevaar", "belangrijk" and the rest of the ge-/be-/ver- words
 * that end in anything other than -d, -t or -en are already safe and are left
 * out on purpose, exactly as the English list leaves out the three-letter words
 * its own length rule already excludes. Words of four letters or fewer (geld,
 * geen, best, bent) are safe for the same reason.
 *
 * Deliberately absent: "bekend", "bereid", "verbaasd", "verkeerd", "beroemd",
 * "gericht". They ARE participles — of bekennen, bereiden, verbazen, verkeren,
 * beroemen, richten — that also work as adjectives, and the detector handles
 * their adjectival use through the degree-adverb rule instead ("ze was erg
 * verbaasd" is not passive, "ze was verbaasd over het nieuws" is). This mirrors
 * the English list, which leaves out "tired" and "excited" for the same reason.
 *
 * "geleden" is here on a judgement call. It is a real participle of lijden, but
 * in web copy it is overwhelmingly the word "ago" ("twee jaar geleden"), while
 * the passive of lijden is vanishingly rare. Counting it would cost far more
 * than it gains.
 *
 * Hand-compiled — see docs/lang-data-sources.md.
 */

return [
    // Zelfstandige naamwoorden met ge-, be- of ver- op -d of -t.
    'gebied', 'gebit', 'gedicht', 'gelegenheid', 'geluid', 'gerecht',
    'gerechtigheid', 'gezicht', 'gezondheid', 'gewicht',
    'beeld', 'beleid', 'bericht', 'bezit', 'ernst', 'herfst',
    'verband', 'verstand', 'voorbeeld', 'aangelegenheid', 'overgewicht',

    // Bijvoeglijke naamwoorden op -d die nooit een deelwoord waren.
    'gemiddeld', 'gezond',

    // Meervouden op -en van zelfstandige naamwoorden met ge-.
    'gebouwen', 'gebieden', 'gebreken', 'gedachten', 'gedichten', 'geluiden',
    'gemeenten', 'gerechten', 'gevolgen', 'gewichten', 'gezichten', 'gezinnen',
    'geheugen',

    // Werkwoordsvormen die geen deelwoord zijn.
    'geeft', 'gelden', 'gebeuren', 'geloven', 'gebruiken', 'genieten', 'geraken',
    'getuigen',

    // Tegenwoordige deelwoorden en -end bijvoeglijke naamwoorden.
    'beangstigend', 'bedreigend', 'beledigend', 'belastend', 'bemoedigend',
    'beslissend', 'bestaand', 'betreffend', 'bevredigend',
    'ontbrekend', 'ontroerend', 'ontspannend',
    'verbazend', 'verbluffend', 'verhelderend', 'vermoeiend', 'verontrustend',
    'verrassend', 'verschillend', 'verstorend', 'vervelend', 'verwarrend',
    'geldend', 'geruststellend',

    // Het woord "geleden" in zijn gewone betekenis.
    'geleden',
];
