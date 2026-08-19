<?php

/*
 * Dutch past participles that the shape rule cannot derive.
 *
 * The detector recognises a participle by its shape: an optional separable
 * prefix, then ge-, then the stem, then -d, -t or -en ("gemaakt", "aangepast",
 * "opgelost", "geschreven"). Every regular participle and every strong
 * participle built with ge- is therefore already covered and is deliberately
 * NOT repeated here — a list that restates a rule only rots away from it.
 *
 * Two kinds of participle fall outside that shape and are listed instead:
 *
 *  1. Strong participles of verbs with an inseparable prefix (be-, ver-, ont-,
 *     her-, er-). Those verbs take no ge-, and their participle ends in -en,
 *     which the shape rule reserves for ge- words — otherwise every "verkopen"
 *     and "vertellen" in the copy would read as a participle. "verloren",
 *     "vergeten" and "ontvangen" are here for that reason.
 *  2. Participles of verbs whose prefix (onder-, over-, voor-, aan-, om-, vol-,
 *     achter-, weer-) is written exactly like a separable one but is not: they
 *     take no ge- either, so the shape rule looks straight past them.
 *     "Het contract is ondertekend" and "aan de eisen is voldaan" are ordinary
 *     Dutch passives, and each one is listed rather than ruled — widening the
 *     rule to those prefixes would swallow "onderhoud", "overzicht",
 *     "achtergrond" and "opdracht" along with them.
 *  3. Participles that end in -aan, which no ending in the rule allows:
 *     "gedaan", "verstaan".
 *
 * Several of these are spelled exactly like their own infinitive ("vervangen",
 * "verlaten", "ontvangen", "vergeten"). Dutch cannot tell the two apart by
 * form, and unlike German it has no future auxiliary to sharpen the question:
 * "zullen" carries the Dutch future and is not a passive auxiliary, so an
 * infinitive rarely stands directly behind "worden" or "zijn" to begin with.
 *
 * Participles that only ever build a perfect and never a passive ("gekomen",
 * "gebleven", "gestorven", "verschenen", "bezweken") are NOT here — they are a
 * separate guard in DutchPassiveVoiceDetector, and listing them as passive
 * markers would be the opposite of what they are. Every entry below was checked
 * against one question: can this verb take an object? A few are transitive in
 * one reading and unaccusative in another ("bevroren", "verdronken"); those stay
 * here, because the transitive passive they build is real ("de tegoeden werden
 * bevroren") and a guard is all or nothing.
 *
 * Hand-compiled from the standard Dutch strong verb paradigms — see
 * docs/lang-data-sources.md.
 */

return [
    // be-
    'bedrogen', 'bedwongen', 'begraven', 'behouden', 'bekeken', 'belogen',
    'beschoten', 'beschreven', 'besloten', 'besproken', 'bestreden', 'betrokken',
    'bevangen', 'bevonden', 'bevroren', 'bewezen', 'bezeten', 'begrepen',

    // ver-
    'verbannen', 'verboden', 'verbonden', 'verbroken', 'verdreven', 'verdronken',
    'vergeleken', 'vergeten', 'vergeven', 'verheven', 'verholpen', 'verkozen',
    'verkregen', 'verlaten', 'verloren', 'vernomen', 'verraden',
    'verschoven', 'verslagen', 'versleten', 'verstaan',
    'vervangen', 'verweven', 'verworven', 'verzonden', 'verzonnen', 'verzwegen',

    // ont-
    'ontbroken', 'ontheven', 'ontnomen', 'ontslagen', 'ontvangen',
    'onthouden', 'ontworpen',

    // her- en er-
    'hernomen', 'herschreven', 'herzien', 'ervaren',

    // Verbs whose prefix is written like a separable one but is not: they take
    // no ge- either, so the shape rule cannot see them. "Het contract is
    // ondertekend" and "aan de eisen is voldaan" are ordinary Dutch passives.
    'aanvaard', 'achterhaald', 'doorstaan', 'omgeven', 'omschreven',
    'onderbroken', 'ondergaan', 'onderhouden', 'onderscheiden', 'ondersteund',
    'ondertekend', 'ondervonden', 'onderzocht', 'overhandigd', 'overtroffen',
    'overwogen', 'overwonnen', 'voldaan', 'volbracht', 'voltooid', 'voorspeld',
    'voorzien', 'weerlegd', 'weersproken',

    // Participles ending in -aan, which no shape rule ending allows.
    'gedaan', 'begaan',
];
