<?php

/*
 * German function words.
 *
 * Words that carry grammar rather than meaning. Stripping them is what turns a
 * keyphrase into the words a text actually has to contain: "das beste Futter
 * für den Hund" is about Futter and Hund, not about "das" and "für".
 *
 * German declines almost everything, so this list is longer than the English
 * one by construction: every article, demonstrative and possessive appears in
 * each of the case forms an author might type. Missing one form would leave a
 * keyphrase silently wider than the author asked for.
 *
 * Hand-compiled from standard German grammar categories — see
 * docs/lang-data-sources.md. The two rules the English list was compiled under
 * apply here as well:
 *
 * 1. A word is only listed when it is a function word in *every* reading.
 *    Words that double as a content word an author might really be targeting
 *    are deliberately absent: "recht" (Recht auf Auskunft), "halb" (Halbmarathon),
 *    "gleich" (Gleichstrom), "wert" (Wertpapier), "arm", "voll".
 * 2. A keyphrase made of nothing but these words falls back to matching every
 *    word, so an "über uns" phrase still matches something sensible.
 *
 * Capitalisation is irrelevant: the list is matched case-insensitively, which
 * is why "sie" covers "Sie" and "ihr" covers "Ihr".
 */

return [
    // Definite and indefinite articles, in every case form.
    'der', 'die', 'das', 'den', 'dem', 'des',
    'ein', 'eine', 'einen', 'einem', 'einer', 'eines',
    'kein', 'keine', 'keinen', 'keinem', 'keiner', 'keines',

    // Demonstratives and their declensions.
    'dieser', 'diese', 'dieses', 'diesen', 'diesem',
    'jener', 'jene', 'jenes', 'jenen', 'jenem',
    'derselbe', 'dieselbe', 'dasselbe', 'denselben', 'demselben',
    'solcher', 'solche', 'solches', 'solchen', 'solchem',

    // Personal pronouns.
    'ich', 'mich', 'mir', 'du', 'dich', 'dir',
    'er', 'ihn', 'ihm', 'sie', 'ihr', 'ihnen', 'es',
    'wir', 'uns', 'euch', 'man', 'einander', 'sich', 'selbst',

    // Possessives, in every case form.
    'mein', 'meine', 'meinen', 'meinem', 'meiner', 'meines',
    'dein', 'deine', 'deinen', 'deinem', 'deiner', 'deines',
    'sein', 'seine', 'seinen', 'seinem', 'seiner', 'seines',
    'ihre', 'ihren', 'ihrem', 'ihrer', 'ihres',
    'unser', 'unsere', 'unseren', 'unserem', 'unserer', 'unseres',
    'euer', 'eure', 'euren', 'eurem', 'eurer', 'eures',

    // Relative, interrogative and indefinite pronouns.
    'welcher', 'welche', 'welches', 'welchen', 'welchem',
    'wer', 'wen', 'wem', 'wessen', 'was', 'wo', 'wohin', 'woher',
    'wann', 'warum', 'wieso', 'weshalb', 'wozu', 'womit', 'wofür',
    'jemand', 'niemand', 'etwas', 'nichts', 'alles', 'jeder', 'jede', 'jedes',
    'jeden', 'jedem', 'alle', 'allen', 'allem', 'aller', 'beide', 'beiden',

    // Prepositions.
    'an', 'auf', 'aus', 'bei', 'bis', 'durch', 'entlang', 'für', 'gegen',
    'gegenüber', 'gemäß', 'hinter', 'in', 'innerhalb', 'jenseits', 'mit',
    'nach', 'neben', 'ohne', 'seit', 'statt', 'trotz', 'über', 'um', 'unter',
    'von', 'vor', 'während', 'wegen', 'wider', 'zu', 'zwischen', 'außer',
    'außerhalb', 'oberhalb', 'unterhalb', 'anstatt', 'binnen', 'dank', 'laut',
    'mittels', 'nahe', 'per', 'pro', 'samt', 'seitens', 'zufolge', 'zwecks',

    // The contracted prepositions an author types as one word.
    'am', 'ans', 'beim', 'im', 'ins', 'vom', 'zum', 'zur', 'aufs', 'fürs',
    'durchs', 'ums',

    // Coordinating and subordinating conjunctions.
    'und', 'oder', 'aber', 'sondern', 'denn', 'doch', 'sowie', 'sowohl',
    'weder', 'entweder', 'dass', 'ob', 'weil', 'da', 'obwohl', 'obgleich',
    'wenn', 'falls', 'sobald', 'solange', 'seitdem', 'bevor', 'nachdem',
    'damit', 'sodass', 'indem', 'als', 'wie', 'zwar', 'je', 'desto', 'umso',

    // Forms of sein, haben and werden.
    'bin', 'bist', 'ist', 'sind', 'seid', 'war', 'warst', 'waren', 'wart',
    'gewesen', 'sei', 'seien', 'wäre', 'wären',
    'habe', 'hast', 'hat', 'haben', 'habt', 'hatte', 'hattest', 'hatten',
    'hattet', 'gehabt', 'hätte', 'hätten',
    'werde', 'wirst', 'wird', 'werden', 'werdet', 'wurde', 'wurdest', 'wurden',
    'wurdet', 'worden', 'geworden', 'würde', 'würden',

    // Modals.
    'kann', 'kannst', 'können', 'könnt', 'konnte', 'konnten', 'könnte',
    'könnten', 'gekonnt',
    'muss', 'musst', 'müssen', 'müsst', 'musste', 'mussten', 'müsste',
    'müssten', 'gemusst',
    'soll', 'sollst', 'sollen', 'sollt', 'sollte', 'sollten', 'gesollt',
    'will', 'willst', 'wollen', 'wollt', 'wollte', 'wollten', 'gewollt',
    'darf', 'darfst', 'dürfen', 'dürft', 'durfte', 'durften', 'dürfte',
    'dürften', 'gedurft',
    'mag', 'magst', 'mögen', 'mögt', 'mochte', 'mochten', 'möchte', 'möchten',

    // Quantifiers.
    'viel', 'viele', 'vielen', 'vieler', 'vieles', 'wenig', 'wenige', 'wenigen',
    'mehr', 'meist', 'meiste', 'meisten', 'weniger', 'wenigsten', 'genug',
    'manche', 'manchen', 'mancher', 'einige', 'einigen', 'einiger', 'mehrere',
    'mehreren', 'kaum', 'lauter', 'sämtliche',

    // Cardinals to twenty and the round numbers above them.
    'eins', 'zwei', 'drei', 'vier', 'fünf', 'sechs', 'sieben', 'acht', 'neun',
    'zehn', 'elf', 'zwölf', 'dreizehn', 'vierzehn', 'fünfzehn', 'sechzehn',
    'siebzehn', 'achtzehn', 'neunzehn', 'zwanzig', 'dreißig', 'vierzig',
    'fünfzig', 'hundert', 'tausend',

    // Ordinals to tenth.
    'erste', 'ersten', 'zweite', 'zweiten', 'dritte', 'dritten', 'vierte',
    'fünfte', 'sechste', 'siebte', 'achte', 'neunte', 'zehnte', 'letzte',
    'letzten',

    // Adverbs of degree. "zu" grades an adjective too ("zu groß") but is
    // already listed above as the preposition it also is.
    'sehr', 'ganz', 'ziemlich', 'äußerst', 'besonders', 'echt', 'total',
    'höchst', 'überaus', 'ungemein', 'allzu', 'so', 'weitaus', 'derart',
    'einigermaßen',

    // Adverbs of time and frequency.
    'immer', 'nie', 'niemals', 'manchmal', 'oft', 'häufig', 'selten', 'meistens',
    'ständig', 'jetzt', 'nun', 'damals', 'später', 'früher', 'zuerst',
    'schon', 'noch', 'wieder', 'bald', 'heute', 'gestern', 'morgen',
    'sofort', 'endlich', 'bereits', 'stets',

    // Adverbs of stance, focus and negation. "da" is already listed above as
    // the subordinating conjunction it also is.
    // "sicher", "natürlich" and "gerade" are deliberately absent: each is an
    // adverb in one reading and an adjective an author might really be
    // targeting in another (sichere Verbindung, natürlich abnehmen, gerade
    // Linie).
    'nicht', 'nein', 'ja', 'auch', 'sogar', 'nur', 'bloß', 'eben', 'halt',
    'wohl', 'vielleicht', 'wirklich', 'überhaupt', 'eigentlich',
    'gern', 'her', 'hin', 'hier', 'dort',
];
