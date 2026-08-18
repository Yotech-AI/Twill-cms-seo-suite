<?php

namespace TwillSeo\Tests\Unit\Analysis\Language\En;

use TwillSeo\Analysis\Language\En\EnglishLanguagePack;
use TwillSeo\Analysis\Language\PassiveVoiceDetector;

function englishPassive(): PassiveVoiceDetector
{
    return (new EnglishLanguagePack)->passiveVoice();
}

it('reads a periphrastic passive as passive', function (string $sentence) {
    expect(englishPassive()->isPassive($sentence))->toBeTrue();
})->with([
    'be plus irregular participle' => ['The ball was thrown by John.'],
    'no agent named' => ['Mistakes were made.'],
    'progressive passive' => ['The house is being built.'],
    'get passive' => ['He got promoted last year.'],
    'perfect passive' => ['The results have been published.'],
    'modal passive' => ['The report will be reviewed tomorrow.'],
    'present passive' => ['Our data is encrypted at rest.'],
    'past passive with a time phrase' => ['The window was broken during the storm.'],
    'plural past passive' => ['These photos were taken in 1998.'],
    'modal passive with an agent' => ['The form must be signed by both parties.'],
    'progressive passive with a longer subject' => ['The old bridge is being replaced.'],
    'passive with an indefinite subject' => ['Nothing was said about the delay.'],
    'passive of an irregular verb' => ['The keys were found under the seat.'],
    'habitual get passive' => ['The package gets delivered every Friday.'],
    'passive with a manner phrase' => ['The song was written in one afternoon.'],
    // "weed" is on the non-participle list; its inflected form is not, and must
    // still read as a participle.
    'inflected form of a listed non participle' => ['The garden was carefully weeded.'],
    'passive with a manner adverb in between' => ['The prices were quietly raised.'],
    'perfect passive with a prepositional phrase' => ['The bug has been fixed in the latest release.'],
    'passive with a following clause' => ['Access is granted after approval.'],
    'passive of a phrasal verb' => ['The tree was cut down last week.'],
    // The documented ruling: an adjectival participle counts as passive unless
    // a degree adverb grades it. "tired" here is scored passive, "very tired"
    // is not — see docs/analysis.md.
    'a bare adjectival participle still counts' => ['He was tired after the run.'],
]);

it('leaves an active or merely descriptive sentence alone', function (string $sentence) {
    expect(englishPassive()->isPassive($sentence))->toBeFalse();
})->with([
    'be plus adjective' => ['He was happy.'],
    'be plus adverb' => ['She was there yesterday.'],
    'be plus adjective after a noun that looks like a participle' => ['The bed was comfortable.'],
    // "hundred" ends in -ed and is not a participle.
    'a number that ends in ed' => ['They were three hundred people.'],
    // A degree adverb marks the participle as an adjective: nobody is "very
    // built", so nothing is being done to him.
    'a graded participle is an adjective' => ['He was very excited to start.'],
    'a noun that ends in ed' => ['There is a need for more testing.'],
    'an adverb that ends in ed' => ['The result is indeed impressive.'],
    'an active sentence with no auxiliary' => ['We built the house ourselves.'],
    'a present tense active sentence' => ['The team publishes a report every quarter.'],
    // A determiner in front of the candidate means it is a noun, not a verb.
    'a noun that doubles as a participle' => ['There was a wound on his arm.'],
    'a participle used as a noun modifier' => ['It is a mixed bag.'],
    'an adjective with no ed at all' => ['The soup was too salty.'],
    'an adjective formed from a noun' => ['She is extremely talented.'],
    'a plain state of being' => ['The children were quiet all morning.'],
    // "wrote" is a past tense, never a participle, so it cannot form a passive.
    'a past tense verb in a relative clause' => ['He is the one who wrote it.'],
    'a participle shaped noun before the verb' => ['The garden shed is behind the house.'],
    'a participle shaped noun after a determiner' => ['There is a shed in the garden.'],
    // "is that …" opens a new clause; whatever follows is its own subject and
    // verb, not the complement of the auxiliary.
    'a complement clause' => ['The naked truth is that he lied.'],
    'another complement clause' => ['The problem is that nobody checked.'],
    'an adjective phrase' => ['We are ready to launch.'],
    'an ed adjective in the subject' => ['The rugged coastline is beautiful.'],
    'a graded adjective from a noun' => ['He was a talented speaker.'],
    'an intransitive past tense' => ['Sales increased by ten percent.'],
    'a bare copula' => ['The meeting is at three.'],
    'a possessive complement' => ['They are our best customers.'],
    'get with an object' => ['We got a new laptop.'],
]);

it('does not carry an auxiliary across a clause boundary', function () {
    // The auxiliary belongs to the first clause and the participle to the
    // second; reading them together would invent a passive that is not there.
    expect(englishPassive()->isPassive('Although he was late, we finished the work.'))->toBeFalse()
        ->and(englishPassive()->isPassive('He was happy, excited and ready.'))->toBeFalse();
});

it('finds a passive in any clause of a sentence', function () {
    expect(englishPassive()->isPassive('We arrived late, but the room was already cleaned.'))->toBeTrue();
});

it('reads an empty sentence as not passive', function () {
    expect(englishPassive()->isPassive(''))->toBeFalse()
        ->and(englishPassive()->isPassive('   '))->toBeFalse();
});
