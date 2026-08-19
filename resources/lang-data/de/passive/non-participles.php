<?php

/*
 * German words that look like a past participle and are not one.
 *
 * The detector reads any word of five letters or more that is spelled like a
 * participle — an optional separable prefix plus ge- plus -t or -en, or an
 * inseparable prefix plus -t — as one. That rule is right far more often than
 * it is wrong, but German builds a great many ordinary nouns out of exactly
 * those pieces, and without this list "Das Gebiet ist groß" and "Das Gerät ist
 * neu" would both read as passive.
 *
 * Three groups are collected:
 *
 *  1. Nouns and adjectives built with ge- that end in -t: Gebiet, Gerät,
 *     Gesicht, Geschäft, Gesellschaft, Gesundheit, gesamt.
 *  2. ge- verbs whose infinitive ends in -en and is not a participle: gelten,
 *     gehören, gewinnen, gelingen. Their participles are spelled differently
 *     (gegolten, gehört, gewonnen, gelungen), so listing the infinitive costs
 *     nothing and stops "er wird gewinnen" reading as a passive. Plural and
 *     dative forms of ge- nouns land on the same shape and are listed with
 *     them; the determiner guard already covers most of their uses ("in den
 *     Gebäuden"), so only the common ones are here.
 *  3. Nouns and adverbs built with an inseparable prefix that end in -t:
 *     Bericht, Verlust, Übersicht, Unterricht, überhaupt.
 *
 * Only words the shape rule would actually catch are listed. "gerade",
 * "genau", "gemeinsam", "gering", "gesund", "gelb", "geheim", "Gemüse",
 * "Gebäude", "Gebiet"'s neighbours that end in anything other than -t or -en
 * are already safe and are left out on purpose, exactly as the English list
 * leaves out the three-letter words its own length rule already excludes.
 * "geben", "gehen", "gegen" and every separable compound of them ("aufgeben",
 * "ausgehen") are safe for a subtler reason: after ge- they have no room left
 * for a stem of two letters plus an ending, so the shape rule never reaches
 * them.
 *
 * Deliberately absent: "bekannt", "beliebt", "berühmt", "bewusst", "verwandt",
 * "verkehrt", "geeignet". They ARE participles that also work as adjectives,
 * and the detector handles their adjectival use through the degree-adverb rule
 * instead ("er war sehr begeistert" is not passive, "er war von der Nachricht
 * überrascht" is). This mirrors the English list, which leaves out "tired" and
 * "excited" for exactly the same reason.
 *
 * "bereit" is the one that goes the other way: it looks like be- plus a stem,
 * but there is no verb it is the participle of — "bereiten" makes "bereitet".
 * Dutch "bereid" really is a participle (of bereiden), which is why the two
 * lists disagree about the same-looking word.
 *
 * Hand-compiled — see docs/lang-data-sources.md.
 */

return [
    // Substantive und Adjektive mit ge- auf -t.
    'gebet', 'gebiet', 'geburt', 'gedicht', 'gegenwart', 'gehalt', 'geist',
    'gelegenheit', 'gemeinschaft', 'gerät', 'gericht', 'gerücht', 'gesamt',
    'geschäft', 'geschlecht', 'geschwindigkeit', 'gescheit', 'gesellschaft',
    'gesicht', 'gestalt', 'gesundheit', 'gewalt', 'gewicht', 'gewohnheit',
    'angebot',

    // ge-Verben auf -en, die kein Partizip sind.
    'gebären', 'gebrauchen', 'gedeihen', 'gedenken', 'gefährden', 'gehorchen',
    'gehören', 'angehören', 'gelingen', 'gelten', 'genehmigen', 'genießen',
    'gestalten', 'gestehen', 'gewinnen', 'gewöhnen', 'gefällt',

    // Mehrzahl- und Dativformen von ge-Substantiven.
    'gebäuden', 'gebieten', 'gebühren', 'gedanken', 'gefühlen', 'gemeinden',
    'geräten', 'gerichten', 'geschäften', 'geschichten', 'gesetzen', 'gewissen',

    // Substantive und Adverbien mit untrennbarer Vorsilbe auf -t.
    'bereit', 'bericht', 'besonderheit', 'ernst', 'überhaupt', 'übersicht',
    'unterricht', 'vergangenheit', 'verlust',
];
