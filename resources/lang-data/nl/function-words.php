<?php

/*
 * Dutch function words.
 *
 * Words that carry grammar rather than meaning. Stripping them is what turns a
 * keyphrase into the words a text actually has to contain: "de beste voeding
 * voor honden" is about voeding and honden, not about "de" and "voor".
 *
 * Hand-compiled from standard Dutch grammar categories — see
 * docs/lang-data-sources.md. The two rules the English list was compiled under
 * apply here as well:
 *
 * 1. A word is only listed when it is a function word in *every* reading.
 *    Words that double as a content word an author might really be targeting
 *    are deliberately absent: "vrij" (vrij parkeren), "half" (halve marathon),
 *    "gaan" (leren gaan), "recht" (recht op uitkering), "licht" (licht ontbijt),
 *    "even" is kept because its content reading ("even getal") is rare enough
 *    next to the adverb.
 * 2. A keyphrase made of nothing but these words falls back to matching every
 *    word, so an "over ons" phrase still matches something sensible.
 *
 * Spelling note: forms written with an apostrophe ('t, 'n, z'n, d'r) are left
 * out. The word tokenizer only joins an apostrophe *between* two letter runs
 * ("auto's"), so a leading apostrophe never reaches this list as written.
 */

return [
    // Articles, demonstratives and other determiners.
    'de', 'het', 'een', 'deze', 'die', 'dit', 'dat', 'zulke', 'zulk',
    'dezelfde', 'hetzelfde', 'zelfde', 'ander', 'andere', 'anderen',
    'welk', 'welke', 'ieder', 'iedere', 'elk', 'elke', 'alle', 'allen', 'allemaal',
    'sommige', 'sommigen', 'enkele', 'enige', 'geen', 'beide', 'beiden',

    // Personal, possessive and reflexive pronouns.
    'ik', 'mij', 'me', 'mijn', 'mijne', 'mezelf', 'mijzelf',
    'jij', 'je', 'jou', 'jouw', 'jouwe', 'jezelf', 'jullie',
    'u', 'uw', 'uwe', 'uzelf',
    'hij', 'hem', 'zijn', 'zijne', 'zich', 'zichzelf',
    'zij', 'ze', 'haar', 'hare', 'haarzelf',
    'wij', 'we', 'ons', 'onze', 'onszelf',
    'hun', 'hen', 'hunne', 'hemzelf', 'henzelf',

    // Indefinite, relative and interrogative pronouns.
    'men', 'iemand', 'niemand', 'iets', 'niets', 'elkaar', 'elkaars',
    'iedereen', 'alles', 'wie', 'wiens', 'wier', 'wat', 'welken',
    'hoe', 'hoeveel', 'waarom', 'waar', 'wanneer', 'waarmee', 'waarvan', 'waarop',
    'waarin', 'waarbij', 'waardoor', 'waarnaar', 'waartoe', 'waarvoor',

    // Pro-adverbs and place adverbs that stand in for a noun phrase.
    'er', 'hier', 'daar', 'ergens', 'nergens', 'overal', 'hierbij', 'hiervan',
    'daarbij', 'daarvan', 'daarmee', 'ervan', 'ermee', 'erop', 'erin', 'eruit',

    // Prepositions.
    'aan', 'achter', 'behalve', 'beneden', 'betreffende', 'bij', 'binnen', 'boven',
    'buiten', 'door', 'gedurende', 'in', 'inzake', 'jegens', 'krachtens', 'langs',
    'met', 'middels', 'na', 'naar', 'naast', 'namens', 'om', 'omstreeks', 'omtrent',
    'ondanks', 'onder', 'op', 'over', 'per', 'rond', 'rondom', 'sinds', 'te',
    'tegen', 'tegenover', 'tijdens', 'tot', 'totdat', 'tussen', 'uit', 'van',
    'vanaf', 'vanuit', 'vanwege', 'via', 'volgens', 'voor', 'voorbij', 'wegens',
    'zonder', 'ten', 'ter', 'des',

    // Coordinating and subordinating conjunctions.
    'en', 'maar', 'of', 'want', 'dus', 'noch', 'omdat', 'doordat', 'aangezien',
    'terwijl', 'hoewel', 'ofschoon', 'zodat', 'opdat', 'indien', 'tenzij', 'mits',
    'als', 'dan', 'alsof', 'voordat', 'nadat', 'zodra', 'naarmate', 'zoals',
    'hetzij', 'zowel', 'enerzijds', 'anderzijds',

    // Forms of zijn, hebben and worden.
    'ben', 'bent', 'is', 'was', 'waren', 'geweest', 'wezen',
    'hebben', 'heb', 'hebt', 'heeft', 'had', 'hadden', 'gehad',
    'worden', 'word', 'wordt', 'werd', 'werden', 'geworden',

    // Modals and the light verbs that behave like them.
    'zullen', 'zal', 'zult', 'zou', 'zouden',
    'kunnen', 'kan', 'kunt', 'kon', 'konden', 'gekund',
    'moeten', 'moet', 'moest', 'moesten',
    'mogen', 'mag', 'mocht', 'mochten',
    'willen', 'wil', 'wilt', 'wilde', 'wilden', 'wou', 'gewild',
    'doen', 'doe', 'doet', 'deed', 'deden',
    'laten', 'laat', 'liet', 'lieten', 'gelaten',

    // Quantifiers.
    'veel', 'weinig', 'meer', 'meest', 'meeste', 'minder', 'minst', 'minste',
    'genoeg', 'voldoende', 'zoveel', 'evenveel', 'paar', 'stuk', 'aantal',

    // Cardinals to twenty and the round numbers above them. "een" is already
    // listed as the indefinite article; only the accented numeral is added here.
    'één', 'twee', 'drie', 'vier', 'vijf', 'zes', 'zeven', 'acht', 'negen',
    'tien', 'elf', 'twaalf', 'dertien', 'veertien', 'vijftien', 'zestien',
    'zeventien', 'achttien', 'negentien', 'twintig', 'dertig', 'veertig', 'vijftig',
    'honderd', 'duizend',

    // Ordinals to tenth.
    'eerste', 'tweede', 'derde', 'vierde', 'vijfde', 'zesde', 'zevende', 'achtste',
    'negende', 'tiende', 'laatste',

    // Adverbs of degree.
    // "te" grades an adjective too ("te groot") but is already listed above as
    // the preposition it also is.
    'heel', 'hele', 'erg', 'zeer', 'nogal', 'best', 'behoorlijk',
    'ontzettend', 'enorm', 'bijzonder', 'tamelijk', 'redelijk', 'uiterst',
    'vreselijk', 'hartstikke', 'zo', 'zulks', 'allerminst', 'nauwelijks',

    // Adverbs of time and frequency.
    'altijd', 'nooit', 'soms', 'vaak', 'dikwijls', 'zelden', 'meestal', 'steeds',
    'nu', 'toen', 'straks', 'later', 'eerder', 'eerst', 'net', 'pas', 'al',
    'alweer', 'weer', 'nog', 'reeds', 'ooit', 'eens', 'even', 'meteen', 'direct',
    'gisteren', 'vandaag', 'morgen', 'voortaan', 'inmiddels',

    // Adverbs of stance, focus and negation.
    'niet', 'wel', 'ja', 'nee', 'ook', 'zelfs', 'juist', 'alleen', 'slechts',
    'enkel', 'vooral', 'misschien', 'wellicht', 'zeker', 'echt', 'toch', 'immers',
    'trouwens', 'overigens', 'sowieso', 'eigenlijk', 'gewoon', 'graag',
];
