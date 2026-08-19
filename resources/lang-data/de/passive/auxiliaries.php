<?php

/*
 * The verbs that carry a German passive.
 *
 * Two of them, because German builds two passives:
 *
 *  - the Vorgangspassiv with werden describes the deed as it happens: "der
 *    Brief wird geschrieben";
 *  - the Zustandspassiv with sein describes the state it left behind: "die Tür
 *    ist verschlossen".
 *
 * Both read as passive to a reader, so both are here — which also keeps German
 * consistent with the English and Dutch packs.
 *
 * "haben" is deliberately absent: it marks the perfect, not the passive. "Wir
 * haben das Haus gebaut" is as active as a sentence gets.
 *
 * Two things about this list do real work elsewhere in the detector:
 *
 *  - werden is also the FUTURE auxiliary, so "er wird kommen" has an auxiliary
 *    and no passive at all. That is why the participle rule refuses a bare
 *    -en ending unless the word carries ge- or is a listed strong participle;
 *  - sein also builds the perfect of verbs that describe a change rather than
 *    a deed ("er ist gekommen"), which no auxiliary can distinguish. The
 *    participles that never form a passive are a guard in
 *    GermanPassiveVoiceDetector — see docs/analysis.md.
 *
 * "worden" is the participle werden takes in a perfect passive ("ist gebaut
 * worden") and is listed as the passive marker it is.
 *
 * Hand-compiled — see docs/lang-data-sources.md.
 */

return [
    // Formen von "werden".
    'werde', 'wirst', 'wird', 'werden', 'werdet', 'wurde', 'wurdest', 'wurden',
    'wurdet', 'worden', 'geworden', 'würde', 'würdest', 'würden', 'würdet',

    // Formen von "sein".
    'bin', 'bist', 'ist', 'sind', 'seid', 'war', 'warst', 'waren', 'wart',
    'gewesen', 'wäre', 'wären',
];
