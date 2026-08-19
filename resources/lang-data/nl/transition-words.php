<?php

/*
 * Dutch transition words and phrases.
 *
 * A sentence that opens with one of these tells the reader how it relates to
 * the one before it, which is what the transition assessment measures.
 *
 * Hand-compiled per rhetorical function — see docs/lang-data-sources.md.
 * Multi-word entries are matched as whole phrases on word boundaries, so
 * "ten slotte" only counts when both words really are next to each other.
 *
 * A phrase whose first word is already listed on its own is left out: "kort
 * gezegd" would never fire that "kortom" did not already catch, and a longer
 * entry that can never win is dead weight in a list this size.
 */

return [
    // Additief: dit en dat.
    'bovendien', 'daarnaast', 'tevens', 'ook', 'eveneens', 'evenals', 'evenzo',
    'verder', 'voorts', 'sterker nog', 'daar komt bij dat',
    'op dezelfde manier', 'net als', 'wat meer is',

    // Tegenstelling: dit, maar dat.
    'echter', 'toch', 'desondanks', 'niettemin', 'nochtans', 'daarentegen',
    'integendeel', 'hoewel', 'ofschoon', 'alhoewel', 'weliswaar', 'omgekeerd',
    'anderzijds', 'enerzijds',
    'aan de andere kant', 'aan de ene kant', 'in tegenstelling tot',
    'in plaats daarvan', 'ondanks dat', 'ook al',

    // Oorzaak en gevolg: dit, dus dat.
    'daarom', 'daardoor', 'dus', 'bijgevolg', 'derhalve', 'immers', 'namelijk',
    'omdat', 'doordat', 'aangezien', 'zodat', 'opdat', 'vandaar', 'hierdoor',
    'dankzij', 'vanwege', 'wegens', 'te wijten aan',
    'als gevolg van', 'met als gevolg', 'om die reden',

    // Volgorde: dit, en daarna dat.
    'eerst', 'vervolgens', 'daarna', 'uiteindelijk', 'tenslotte', 'nadien',
    'ondertussen', 'intussen', 'inmiddels', 'later', 'eerder',
    'ten eerste', 'ten tweede', 'ten derde', 'ten slotte', 'om te beginnen',
    'in de eerste plaats', 'in de tweede plaats', 'als laatste',

    // Voorbeeld en nadruk: dit, bijvoorbeeld dat.
    'bijvoorbeeld', 'zoals', 'vooral', 'voornamelijk', 'inderdaad', 'uiteraard',
    'met name', 'in het bijzonder', 'onder andere', 'onder meer',
    'ter illustratie', 'om precies te zijn', 'dat wil zeggen', 'zo blijkt',

    // Samenvatting: dit is waar het op neerkomt.
    'kortom', 'samengevat', 'concluderend', 'bovenal', 'per saldo',
    'al met al', 'met andere woorden', 'in het kort', 'in het algemeen',
    'over het geheel genomen', 'tot slot', 'alles bij elkaar',

    // Voorwaarde: dit, als dat.
    'als', 'indien', 'tenzij', 'mits', 'anders', 'zo niet',
    'in dat geval', 'op voorwaarde dat', 'stel dat',

    // Tijd: dit, wanneer dat.
    'terwijl', 'zodra', 'wanneer', 'voordat', 'nadat', 'totdat', 'sindsdien',
    'tegelijkertijd', 'gelijktijdig', 'destijds', 'voortaan',
    'op dat moment', 'in de tussentijd', 'tot nu toe', 'van tevoren',
];
