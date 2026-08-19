<?php

/*
 * German past participles that the shape rule cannot derive.
 *
 * The detector recognises a participle by its shape: an optional separable
 * prefix, then ge-, then the stem, then -t or -en ("gebaut", "geschrieben",
 * "durchgeführt", "eingeladen"); or one of the inseparable prefixes with -t
 * ("bezahlt", "verkauft", "erhöht"). Everything that shape covers is
 * deliberately NOT repeated here — a list that restates a rule only rots away
 * from it.
 *
 * Two groups fall outside it. The first exists because of werden:
 *
 *   Strong participles of the inseparable verbs end in -en and carry no ge-
 *   ("verstanden", "bekommen", "empfohlen"). The shape rule cannot simply
 *   allow "ver- … -en", because that is also the shape of every infinitive
 *   those verbs have — and werden is the German FUTURE auxiliary, so "er wird
 *   bezahlen" and "er wird verstehen" would both read as passive. Listing the
 *   strong participles one by one is what keeps "wird bezahlt" apart from
 *   "wird bezahlen".
 *
 * The second is the verbs whose prefix (um-, durch-, voll-, wider-, hinter-) is
 * written exactly like a separable one but is not, so they carry no ge- either:
 * "umfasst", "durchsucht", "vollendet". Each is listed rather than ruled —
 * widening the rule to those prefixes would swallow "Durchschnitt" and "Umwelt"
 * along with them.
 *
 * "getan" is here for a smaller reason: it ends in -n, which no ending in the
 * shape rule allows.
 *
 * Several of these are spelled exactly like their own infinitive ("bekommen",
 * "erhalten", "verlassen", "vergessen"). German cannot tell those two apart by
 * form at all, so "er wird das Paket bekommen" is counted as passive. That is a
 * known and bounded cost, written down in docs/analysis.md: the alternative is
 * to miss every real passive those verbs build.
 *
 * Participles that only ever form a perfect and never a passive ("gekommen",
 * "gefahren", "verschwunden", "entstanden") are NOT here — they are a separate
 * guard in GermanPassiveVoiceDetector, and listing them as passive markers
 * would be the opposite of what they are.
 *
 * Hand-compiled from the standard German strong verb paradigms — see
 * docs/lang-data-sources.md.
 */

return [
    // be-
    'befohlen', 'begonnen', 'begraben', 'begriffen', 'behalten', 'behoben',
    'bekommen', 'beraten', 'beschlossen', 'beschossen', 'beschrieben',
    'besessen', 'besprochen', 'bestanden', 'bestiegen', 'bestochen', 'betreten',
    'betroffen', 'betrogen', 'bewiesen', 'bewogen', 'beworben', 'bezogen',

    // ver-
    'verboten', 'verbunden', 'vergeben', 'vergessen', 'vergriffen', 'verglichen',
    'vergossen', 'verhalten', 'verlassen', 'verloren', 'verschlossen',
    'verschoben', 'verschrieben', 'versprochen', 'verstanden', 'verstoßen',
    'versunken', 'vertragen', 'vertrieben', 'verwiesen', 'verworfen', 'verzogen',

    // er-
    'erfahren', 'erfunden', 'ergeben', 'ergriffen', 'erhalten', 'erlassen',
    'erschaffen', 'erschossen', 'ertragen', 'erwiesen', 'erwogen', 'erworben',

    // ent-, zer-, emp- und miss-
    'enthalten', 'entlassen', 'entnommen', 'entschieden', 'entsprochen',
    'entworfen', 'entzogen',
    'zerbrochen', 'zerrissen', 'zerschnitten',
    'empfangen', 'empfohlen', 'empfunden',
    'missverstanden',

    // über- und unter-
    'überflogen', 'übergeben', 'übernommen', 'übersehen', 'überstanden',
    'übertragen', 'übertroffen', 'überwiesen', 'überwunden', 'überzogen',
    'unterbrochen', 'untergraben', 'unterhalten', 'unterlassen',
    'unterschieden', 'unterschrieben', 'unternommen', 'unterworfen',

    // Verbs whose prefix is written like a separable one but is not: they take
    // no ge- either, so the shape rule cannot see them.
    'durchdacht', 'durchsucht', 'hinterlassen', 'hinterlegt', 'umfasst',
    'umgangen', 'umgeben', 'umschrieben', 'vollendet', 'widerlegt',
    'widersprochen',

    // A participle that ends in -n, which no shape rule ending allows.
    'getan',
];
