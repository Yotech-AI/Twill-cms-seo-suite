<?php

/*
 * German transition words and phrases.
 *
 * A sentence that opens with one of these tells the reader how it relates to
 * the one before it, which is what the transition assessment measures.
 *
 * Hand-compiled per rhetorical function — see docs/lang-data-sources.md.
 * Multi-word entries are matched as whole phrases on word boundaries, so
 * "vor allem" only counts when both words really are next to each other.
 *
 * A phrase whose first word is already listed on its own is left out: it could
 * never fire that the single word did not already catch.
 */

return [
    // Additiv: dies und das.
    'außerdem', 'zudem', 'ebenfalls', 'ferner', 'weiterhin', 'ebenso',
    'gleichermaßen', 'obendrein', 'überdies',
    'darüber hinaus', 'nicht zuletzt', 'genauso wie', 'zum einen', 'zum anderen',

    // Gegensatz: dies, aber das.
    'jedoch', 'dennoch', 'allerdings', 'hingegen', 'dagegen', 'trotzdem',
    'gleichwohl', 'vielmehr', 'stattdessen', 'andererseits', 'einerseits',
    'umgekehrt', 'obwohl', 'obgleich', 'wohingegen',
    'im gegensatz dazu', 'auf der anderen seite', 'auf der einen seite',
    'anstelle dessen', 'trotz allem',

    // Ursache und Wirkung: dies, also das.
    'deshalb', 'deswegen', 'daher', 'darum', 'folglich', 'somit', 'also',
    'infolgedessen', 'demzufolge', 'demnach', 'weil', 'denn', 'sodass', 'damit',
    'aufgrund dessen', 'aus diesem grund', 'als ergebnis', 'dank dessen',

    // Reihenfolge: dies, und danach das.
    'erstens', 'zweitens', 'drittens', 'zunächst', 'zuerst', 'anschließend',
    'danach', 'daraufhin', 'schließlich', 'letztendlich', 'abschließend',
    'währenddessen', 'inzwischen', 'mittlerweile', 'seither',
    'zum schluss', 'als erstes', 'als nächstes', 'im anschluss',

    // Beispiel und Betonung: dies, zum Beispiel das.
    'beispielsweise', 'etwa', 'nämlich', 'insbesondere', 'namentlich',
    'tatsächlich', 'freilich', 'offensichtlich', 'zugegeben',
    'zum beispiel', 'vor allem', 'unter anderem', 'das heißt',
    'genauer gesagt', 'in der tat',

    // Zusammenfassung: darauf läuft es hinaus.
    'zusammenfassend', 'insgesamt', 'letztlich', 'schlussendlich',
    'kurz gesagt', 'mit anderen worten', 'im großen und ganzen',
    'alles in allem', 'im wesentlichen', 'im grunde',

    // Bedingung: dies, wenn das.
    'wenn', 'falls', 'sofern', 'andernfalls', 'ansonsten', 'notfalls',
    'in diesem fall', 'unter der voraussetzung', 'angenommen dass',

    // Zeit: dies, wenn das geschieht.
    'während', 'sobald', 'bevor', 'nachdem', 'seitdem', 'solange', 'bislang',
    'gleichzeitig', 'zwischenzeitlich', 'damals', 'künftig',
    'bis dahin', 'von nun an', 'in der zwischenzeit',
];
