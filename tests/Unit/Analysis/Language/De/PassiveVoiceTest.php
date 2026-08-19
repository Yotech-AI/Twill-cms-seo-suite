<?php

namespace TwillSeo\Tests\Unit\Analysis\Language\De;

use TwillSeo\Analysis\Language\De\GermanLanguagePack;
use TwillSeo\Analysis\Language\PassiveVoiceDetector;

function germanPassive(): PassiveVoiceDetector
{
    return (new GermanLanguagePack)->passiveVoice();
}

it('reads a german passive as passive', function (string $sentence) {
    expect(germanPassive()->isPassive($sentence))->toBeTrue();
})->with([
    // The werden-passive, in every tense an author reaches for.
    'past werden passive' => ['Das Haus wurde im Jahr 1990 gebaut.'],
    'present werden passive' => ['Der Brief wird morgen geschrieben.'],
    'plural present werden passive' => ['Die Ergebnisse werden nächste Woche veröffentlicht.'],
    'werden passive with no agent' => ['Mein Fahrrad wurde gestohlen.'],
    'werden passive with a named agent' => ['Die Rechnung wird von der Firma bezahlt.'],
    'werden passive of a weak verb' => ['Die Daten werden sicher gespeichert.'],
    'werden passive of a strong verb' => ['Das Problem wurde von uns gelöst.'],
    'werden passive with a frequency phrase' => ['Die Preise werden jedes Jahr erhöht.'],
    'nothing was said' => ['Nichts wurde über die Verspätung gesagt.'],
    'perfect passive through worden' => ['Der Bericht ist gestern veröffentlicht worden.'],

    // Separable verbs put the ge- in the middle of the word, which is where
    // most of the real passives in German copy live.
    'separable participle with durch' => ['Das Projekt wurde erfolgreich durchgeführt.'],
    'separable participle with ein' => ['Die neuen Regeln werden bald eingeführt.'],
    'separable participle with ab' => ['Die Kinder wurden von ihren Eltern abgeholt.'],
    'separable participle with ab and a strong stem' => ['Das Paket wurde beim Nachbarn abgegeben.'],
    'separable participle with ein and a strong stem' => ['Sie wurde zum Gespräch eingeladen.'],

    // Strong participles of the inseparable verbs, which carry no ge- at all.
    'inseparable strong participle' => ['Der Vertrag ist von beiden Seiten unterschrieben.'],
    'another inseparable strong participle' => ['Der Fehler wurde schnell behoben.'],
    'a recommendation' => ['Der Vorschlag wird vom Team empfohlen.'],

    // A borrowed -ieren verb builds its participle without any ge- at all,
    // which is easy to miss and very common in German business copy.
    'a borrowed verb in -iert' => ['Das System wurde nie ordentlich dokumentiert.'],
    'another borrowed verb in -iert' => ['Die Kunden werden per E-Mail informiert.'],
    'a borrowed verb with a prefix' => ['Die Konferenz wurde von uns organisiert.'],

    // Verbs whose prefix looks separable but is not: no ge- anywhere.
    'an inseparable um verb' => ['Der Bericht wird von einer Zusammenfassung umfasst.'],
    'an inseparable voll verb' => ['Die Arbeit ist endlich vollendet.'],

    // A werden form has no perfect to be confused with, so the perfect-only
    // guard must not reach it: "ein Ticket eskalieren" takes an object, and
    // this is the everyday shape of it in business German.
    'an intransitive verb that does take an object' => ['Das Problem wurde eskaliert.'],
    'the same verb in the plural' => ['Die Tickets werden automatisch eskaliert.'],
    // The mirror of the negative below: two auxiliaries of different kinds in
    // one clause, and each participle pairs with the nearer one. "gestiegen"
    // stays a perfect; "unterschrieben" is the passive that makes this one.
    'two auxiliaries, each with its own participle' => ['Die Preise sind gestiegen seit der Vertrag unterschrieben wurde.'],

    // A zu-marked infinitive governs the participle inside its own phrase, and
    // "zu werden" behind one is an everyday German passive.
    'a passive infinitive after hoffen' => ['Er hofft gewählt zu werden.'],
    'a passive infinitive after scheinen' => ['Die Daten scheinen gelöscht zu werden.'],
    // The comma German would normally write is left out on purpose: headline
    // and bullet copy drops it, and that is where this has to hold.
    'a passive infinitive opening the sentence' => ['Um gefunden zu werden muss der Titel stimmen.'],

    // The Zustandspassiv: sein plus a participle, describing the state a deed
    // left behind.
    'a state left behind' => ['Das Geschäft ist seit acht Uhr geöffnet.'],
    'a door that was shut' => ['Die Tür war verschlossen.'],
    'a text someone wrote' => ['Der Text ist gut geschrieben.'],

    'passive in the second clause' => ['Wir kamen spät an, aber das Zimmer war schon aufgeräumt.'],
    'passive in a subordinate clause' => ['Ich glaube, dass das Haus letztes Jahr verkauft wurde.'],
    'passive with an infinitive auxiliary' => ['Das Haus soll noch dieses Jahr verkauft werden.'],

    // The documented ruling, inherited from the English pack: a bare adjectival
    // participle counts as passive; only a degree adverb settles it as an
    // adjective. See docs/analysis.md.
    'a bare adjectival participle still counts' => ['Er war von der Nachricht überrascht.'],
]);

it('leaves an active or merely descriptive german sentence alone', function (string $sentence) {
    expect(germanPassive()->isPassive($sentence))->toBeFalse();
})->with([
    // The trap that makes German different: werden is also the future
    // auxiliary, so a bare infinitive behind it is not a participle.
    'the future of a strong verb' => ['Er wird kommen.'],
    'the future of an inseparable verb' => ['Er wird das Buch bezahlen.'],
    'the future of another inseparable verb' => ['Sie wird uns morgen verstehen.'],
    'the future of a ge verb' => ['Es wird Regen geben.'],
    'the future of another ge verb' => ['Wir werden nach Hause gehen.'],
    'the future of a third ge verb' => ['Er wird das Spiel gewinnen.'],
    'the future of a separable verb' => ['Der Wettbewerb wird nächstes Jahr stattfinden.'],
    'the future of a separable verb with a ge stem' => ['Sie wird das Angebot annehmen.'],
    'the future of a borrowed verb' => ['Wir werden die Konferenz selbst organisieren.'],
    // werden also means "become", and a noun behind it is a complement.
    'becoming something' => ['Sie wird Ärztin.'],

    // ge- words that were never participles.
    'a ge noun that ends in t' => ['Das Gebiet ist sehr groß.'],
    'another ge noun that ends in t' => ['Das Gesicht war blass.'],
    'a ge noun for a device' => ['Das Gerät ist neu.'],
    'a ge noun for a company' => ['Die Gesellschaft ist alt.'],
    'a ge adjective' => ['Das Gemüse ist gesund.'],
    'the preposition gegen' => ['Das Argument ist gegen jede Vernunft.'],

    // A degree adverb grades an adjective; a verbal passive cannot be graded.
    'a graded participle is an adjective' => ['Er war sehr begeistert.'],
    'another graded participle' => ['Er ist ziemlich überrascht.'],
    'a graded separable participle' => ['Der Junge ist ganz aufgeregt.'],

    // sein also builds the perfect of verbs that have no passive at all.
    'a perfect of a verb of motion' => ['Er ist gestern nach Berlin gefahren.'],
    'a perfect of a verb of change' => ['Die Preise sind gestiegen.'],
    'a perfect of another verb of motion' => ['Sie ist um acht Uhr gekommen.'],
    'a perfect of a verb of pleasing' => ['Das Buch ist ihm sehr gefallen.'],
    // Verbs that take no object at all, so the participle they build can only
    // ever be a perfect.
    'a perfect of a verb of sinking' => ['Das Schiff ist versunken.'],
    'a perfect of a verb of being cancelled' => ['Das Konzert ist ausgefallen.'],
    'a perfect of a verb of arriving' => ['Die Ware ist gestern eingetroffen.'],
    'a perfect of a borrowed verb in -iert' => ['Die Lage ist eskaliert.'],

    // A werden form that belongs to a purpose clause governs nothing outside
    // it. The comma German would normally write before "um ... zu" is left out
    // on purpose: headline and bullet copy drops it, and that is where this
    // has to hold.
    'a perfect in front of a zu-marked infinitive' => ['Die Firma ist gewachsen um Marktführer zu werden.'],
    // Both kinds of auxiliary in one clause: "gestiegen" pairs with the "sind"
    // beside it, not with the "wurde" four words away.
    'a perfect that keeps its own auxiliary' => ['Die Preise sind gestiegen seit er Direktor wurde.'],

    // haben marks the perfect, never the passive.
    'an active perfect with haben' => ['Wir haben das Haus selbst gebaut.'],
    'an active present tense' => ['Das Team veröffentlicht jedes Quartal einen Bericht.'],

    // The auxiliary and the participle belong to different clauses.
    'an auxiliary that cannot reach across a comma' => ['Obwohl er spät war, haben wir die Arbeit beendet.'],
    'an auxiliary that cannot reach across dass' => ['Das Problem ist, dass niemand geprüft hat.'],
    'an auxiliary that cannot reach across und' => ['Das Gebäude ist groß und viele Leute haben es besucht.'],

    'a plain copula' => ['Das Treffen ist um drei.'],
    'a plain state' => ['Die Kinder waren den ganzen Morgen still.'],
]);

it('finds a german passive in any clause of a sentence', function () {
    expect(germanPassive()->isPassive('Wir waren pünktlich, aber die Tür war schon geschlossen.'))->toBeTrue();
});

it('reads an empty german sentence as not passive', function () {
    expect(germanPassive()->isPassive(''))->toBeFalse()
        ->and(germanPassive()->isPassive('   '))->toBeFalse();
});

it('keeps its verdict when the german sentence is shouted', function () {
    // Umlauts have to survive the lowercasing, or "GEPRÜFT" would stop
    // matching the participle rule halfway through the word.
    expect(germanPassive()->isPassive('DER BERICHT WURDE GEPRÜFT.'))->toBeTrue()
        ->and(germanPassive()->isPassive('ER WIRD KOMMEN.'))->toBeFalse();
});
