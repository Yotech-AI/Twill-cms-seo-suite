<?php

namespace TwillSeo\Tests\Unit\Analysis\Language\Nl;

use TwillSeo\Analysis\Language\Nl\DutchLanguagePack;
use TwillSeo\Analysis\Language\PassiveVoiceDetector;

function dutchPassive(): PassiveVoiceDetector
{
    return (new DutchLanguagePack)->passiveVoice();
}

it('reads a dutch passive as passive', function (string $sentence) {
    expect(dutchPassive()->isPassive($sentence))->toBeTrue();
})->with([
    // The worden-passive, in every tense an author reaches for.
    'present worden passive' => ['De ramen worden elke maand gewassen.'],
    'past worden passive' => ['De brief werd gisteren verstuurd.'],
    'impersonal worden passive' => ['Er wordt hard gewerkt aan de nieuwe versie.'],
    'worden passive with a named agent' => ['Het rapport werd door de commissie geschreven.'],
    'worden passive with an infinitive auxiliary' => ['Het huis zal worden verkocht.'],
    'plural past worden passive' => ['De kinderen werden door hun ouders opgehaald.'],
    'worden passive of a strong verb' => ['De oude brug wordt vervangen.'],
    'worden passive with a frequency phrase' => ['De prijzen worden jaarlijks verhoogd.'],
    'worden passive with a time phrase' => ['De vergadering wordt volgende week gehouden.'],

    // The zijn-passive: the actor is gone, the deed is done.
    'zijn passive' => ['Het huis is verkocht.'],
    'plural zijn passive' => ['De resultaten zijn gepubliceerd.'],
    'zijn passive with a year' => ['Dit boek is in 1998 geschreven.'],
    'zijn passive of a her verb' => ['Alle fouten zijn hersteld.'],
    'zijn passive of a be verb' => ['De gegevens worden veilig bewaard.'],
    'zijn passive of a strong verb' => ['De foto\'s zijn in 1998 genomen.'],

    // Separable verbs put the ge- in the middle, which is where most of the
    // real passives in Dutch copy live.
    'separable participle with aan' => ['De regels zijn onlangs aangepast.'],
    'separable participle with op' => ['Het probleem werd snel opgelost.'],
    'separable participle with uit' => ['De taak is uitgevoerd volgens het plan.'],
    'separable participle with af' => ['Het pakket werd bij de buren afgeleverd.'],
    'separable participle with uit and a strong stem' => ['Ze werd voor het examen uitgenodigd.'],

    // Verbs whose prefix looks separable but is not: no ge- anywhere.
    'an inseparable onder verb' => ['Het contract is door beide partijen ondertekend.'],
    'an inseparable vol verb' => ['Aan alle eisen is voldaan.'],
    'an inseparable over verb' => ['Dat voorstel werd zorgvuldig overwogen.'],

    'passive in the second clause' => ['We kwamen laat aan, maar de kamer was al opgeruimd.'],
    'passive in a subordinate clause' => ['Ik denk dat het huis vorig jaar verkocht is.'],
    'nothing was said' => ['Er werd niets gezegd over de vertraging.'],

    // The documented ruling, inherited from the English pack: a bare adjectival
    // participle counts as passive; only a degree adverb settles it as an
    // adjective. See docs/analysis.md.
    'a bare adjectival participle still counts' => ['Ze was verbaasd over het nieuws.'],
]);

it('leaves an active or merely descriptive dutch sentence alone', function (string $sentence) {
    expect(dutchPassive()->isPassive($sentence))->toBeFalse();
})->with([
    // ge- words that were never participles.
    'a ge word that is an adjective' => ['Hij is gewoon aardig.'],
    'a ge word that is a noun' => ['Het gezin was groot.'],
    'a ge noun ending in d' => ['Het geluid was hard.'],
    'a ge noun ending in t' => ['Het gewicht is te hoog.'],
    'a ge word for money' => ['Er is geen geld meer over.'],
    'a ge adjective ending in d' => ['Het resultaat was gemiddeld.'],
    'a be noun ending in t' => ['Hij was blij met het bericht.'],
    'a ver present participle' => ['Het nieuws was verrassend.'],
    'a ver adjective ending in d' => ['De uitkomsten zijn verschillend.'],
    'a voor noun ending in d' => ['Dit is een goed voorbeeld.'],

    // A degree adverb grades an adjective; a verbal passive cannot be graded.
    'a graded participle is an adjective' => ['Ze was erg verbaasd.'],
    'another graded participle' => ['Hij was heel benieuwd naar het antwoord.'],

    // zijn also builds the perfect of verbs that have no passive at all.
    'a perfect of an intransitive verb' => ['De vergadering is begonnen.'],
    'a perfect of a verb of motion' => ['Hij is naar huis gekomen.'],
    'a perfect of a verb of change' => ['De prijzen zijn gestegen.'],
    'a perfect of the verb to be' => ['Wij zijn hier vorig jaar geweest.'],
    'a participle that is really the word ago' => ['Dat was twee jaar geleden.'],

    // hebben marks the perfect, never the passive.
    'an active perfect with hebben' => ['Wij hebben het huis zelf gebouwd.'],
    'an active present tense' => ['Het team publiceert elk kwartaal een rapport.'],

    // The auxiliary and the participle belong to different clauses.
    'an auxiliary that cannot reach across a comma' => ['Hoewel hij laat was, hebben we het werk afgemaakt.'],
    'an auxiliary that cannot reach across dat' => ['Het probleem is dat niemand het heeft gecontroleerd.'],
    'an auxiliary that cannot reach across en' => ['Het gebouw is groot en veel mensen hebben het bezocht.'],

    'a plain copula' => ['De winkel is open.'],
    'a plain state' => ['De kinderen waren de hele ochtend stil.'],
    'a short word that only looks like a participle' => ['Jij bent de beste van de klas.'],
    'a comparative that starts like a participle' => ['Het huis is best groot.'],
]);

it('finds a dutch passive in any clause of a sentence', function () {
    expect(dutchPassive()->isPassive('We waren op tijd, maar de deur was al gesloten.'))->toBeTrue();
});

it('reads an empty dutch sentence as not passive', function () {
    expect(dutchPassive()->isPassive(''))->toBeFalse()
        ->and(dutchPassive()->isPassive('   '))->toBeFalse();
});

it('keeps its verdict when the sentence is shouted', function () {
    expect(dutchPassive()->isPassive('HET HUIS IS VERKOCHT.'))->toBeTrue()
        ->and(dutchPassive()->isPassive('HIJ IS GEWOON AARDIG.'))->toBeFalse();
});
