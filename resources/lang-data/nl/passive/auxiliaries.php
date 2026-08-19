<?php

/*
 * The verbs that carry a Dutch passive.
 *
 * Two of them, because Dutch builds two passives:
 *
 *  - the worden-passive describes the deed as it happens: "de brief wordt
 *    geschreven";
 *  - the zijn-passive describes the state it left behind: "de brief is
 *    geschreven".
 *
 * Both read as passive to a reader, so both are here.
 *
 * "hebben" is deliberately absent: it marks the perfect, not the passive. "Wij
 * hebben het huis gebouwd" is as active as a sentence gets.
 *
 * "zijn" also builds the perfect of verbs that describe a change rather than a
 * deed ("hij is gekomen"). Those cannot be told apart by their auxiliary, only
 * by the verb, so the detector carries a list of the participles that never
 * form a passive — see DutchPassiveVoiceDetector and docs/analysis.md.
 *
 * Hand-compiled — see docs/lang-data-sources.md.
 */

return [
    // Vormen van "worden".
    'word', 'wordt', 'worden', 'werd', 'werden', 'geworden',

    // Vormen van "zijn".
    'ben', 'bent', 'is', 'zijn', 'was', 'waren', 'geweest',
];
