# The content analysis

## How scoring works

Every check an assessment runs produces one `AssessmentResult`: a 0-9 score
(plus two sentinels — `-1` when the check could not run at all, `0` when it
has feedback rather than a verdict), a derived `Rating`
(`bad`/`ok`/`good`, or the neutral `feedback`/`error`) and a `ResultCategory`
the editor panel groups by (`problems`/`improvements`/`good`, plus
`feedback`/`errors`). Rating and category are always *derived* from the
score, never accepted independently — a result carrying a "good" rating next
to a score of 3 is not representable.

The traffic-light color for a single check follows its `Rating` directly:
red for `bad`, orange for `ok`, green for `good`; `feedback` and `error`
both read as neutral grey, since neither is a judgement on the content —
`countsTowardScore` is `false` for both, and they never move the aggregate.

The two section scores shown at the top (SEO, readability) are each a
`ScoreSection.score` out of 100, computed and banded differently:

- **SEO** (`SeoScoreAggregator`): the average of every counted result against
  a 9-point maximum, as a percentage, floored at 1 (never 0 — see below) and
  capped at 100. Banded exactly like a single check: `<=40` bad/red,
  `<=70` ok/orange, `>70` good/green.
- **Readability** (`ReadabilityPenaltyAggregator`): *not* an average. Every
  counted result that is `ok` adds a penalty of 2, every `bad` result adds
  3, and `good` adds nothing — a single badly failing check has to visibly
  move the needle, which averaging over a dozen checks would not do. The
  total penalty then picks one of exactly three fixed scores: `<=4` → **90**
  (good), `<=6` → **60** (ok), otherwise → **30** (bad). A readability score
  is therefore always 30, 60 or 90 — never any other number.

**A score of `0` means "not available", not "the worst possible score".**
The engine reserves it: `SeoScoreAggregator` floors every real score at 1
specifically so 0 stays unreachable there, and
`ReadabilityPenaltyAggregator` returns "not available" outright whenever
fewer than two of its checks could run at all — a title with no body content
yet hits this on every save, which is the ordinary state of a freshly
created item, not a rare edge case. Both a `0` score and a genuinely
un-analyzed `null` (nothing cached yet) render as the same neutral grey
dot, worded differently ("Not available" vs "Not analyzed") — coloring a
fresh, not-yet-written page's `0` red would tell its author it had already
failed. `TwillSeo\Support\ScoreRating` is the one place these boundaries and
that grey-zero rule live for anything reading a *cached* score (listing
columns, the form sidebar chip); the Vue editor panel mirrors the same
numbers from a live report's own `rating` string rather than re-deriving a
color from the bare score, so the two can never disagree.

## Working with the report as JSON

`AnalysisReport::jsonSerialize()` returns a plain PHP array with real
`float` values intact — nothing is JSON-encoded yet at that layer. Every
assessment's `params` (`percentage`, `density`, `fleschScore`, and more,
depending on the check) alongside `insights.fleschScore` can legitimately
land on a whole number — a passive-voice `percentage` of exactly `0` is a
float `0.0`, not an int, and a Flesch score can round to a whole one too.
`ScoreCache` separately stores a smaller, derived shape on save
(`analysis_summary`: a red/orange/green tally per section plus one
surviving float, `insights.flesch`), subject to the same fact below.

**Plain `json_encode()` narrows a whole float to a bare integer**
(`0.0` becomes `0`) — and this package's own two encoding points do not
prevent that: the analyze endpoint's `response()->json(...)` (Symfony's
`JsonResponse::DEFAULT_ENCODING_OPTIONS` is just the four `JSON_HEX_*`
flags, no `JSON_PRESERVE_ZERO_FRACTION`) and `analysis_summary`'s Eloquent
`array` cast (a plain `json_encode()` under the hood —
`HasAttributes::asJson()` defaults its flags to `0`) both already lose a
whole-number float's fractional zero in the JSON text they produce. There is no way to
recover that distinction from the transmitted text or the stored column
afterward — this is invisible to the JavaScript editor panel (no int/float
type distinction exists in JS) and is an accepted trade-off there, but it
matters for anyone working with the PHP data directly: a host calling
`AnalysisRunner::analyze()` in-process (bypassing this package's HTTP layer
entirely) and encoding the raw report itself, or any host-side code
re-serializing an already-received report for its own strict-typed storage
or API. Either one should pass `JSON_PRESERVE_ZERO_FRACTION` at that
encoding call to keep the distinction. The golden-file report tests
(`tests/Unit/Analysis/Report/GoldenReportTest.php`) apply it for exactly
this reason: pinning the report's real types, not just its numbers.

## Divergences

Where this engine deliberately judges a text differently from the analysis it
takes its thresholds from. Each one is a decision, not an accident, and each is
pinned by a test.

### A link's scope covers more than mailto, tel and javascript

`internalLinks`, `externalLinks` and `textCompetingLinks` all read a link's
`LinkScope` to decide whether it addresses another web page at all — a
fragment (`#section`), a `mailto:`, or a `tel:` link counts toward neither
an internal nor an external link, since none of them takes the reader
anywhere the keyphrase's own competition lives.

This engine widens that set by two: `sms:` (a text-message link, the exact
same non-page shape as `tel:`) and `data:` (an inline data URI, never a
fetchable page of its own) are excluded on the same reasoning. Both are
uncommon in body copy, but a page that has one should not have it silently
miscounted as a real outbound link just because the original set of
excluded schemes only named three.

Tested in `HtmlParserTest` ("classifies link scope and nofollow").

### Analysis feedback renders in the admin's locale, not the paper's

A Dutch or German page's `text` feedback strings — "68.2 percent of the
sentences use a transition word", say — render in whichever locale the
*admin* is currently using, not the locale of the content being analyzed.
The `id`, `score`, `rating` and `params` (the raw numbers behind a message)
are unaffected either way; only the human-readable sentence is admin-locale.

This is a deliberate, Yoast-matching choice, not an oversight: an editor
who writes in Dutch is not necessarily reading the admin in Dutch, and the
alternative — feedback in the CONTENT's language — would leave an
English-speaking editor of a multilingual site unable to read the advice on
their own Dutch or German pages at all.
`TwillSeo\Support\TranslatorMessageRenderer` (the renderer actually wired
up in the service provider) resolves every `messageKey` through Laravel's
own translator under `app()->getLocale()`, which is exactly the admin's
locale during an authenticated admin request — never the paper's `locale`
field, which `AnalysisRunner` only ever uses to pick which *engine* (word
lists, thresholds) analyzes the text.

### A section starts at the top of the page, not at the first subheading

`subheadingsTooLong` measures how far a reader gets between one subheading and
the next. It splits the sequence of paragraphs at every H2 and H3, and counts
**every** resulting run as a section — including the run before the first
subheading.

The usual reading is that sections begin at the first subheading, so an
unbroken opening of any length is not measured at all. That is the wrong way
round for the reader: an 800 word wall of text is exactly as hard to get
through at the top of the page as it is in the middle, and it is the part
everyone reads first.

The practical effect is that this check is slightly stricter. A page whose
introduction runs past 300 words before its first subheading is told so, where
the original would say nothing.

Tested in `SubheadingsTooLongAssessmentTest`
("it counts the run before the first subheading as a section of its own").

### An adjectival participle counts as passive unless a degree adverb grades it

`passiveVoice` reports "He was tired after the run" as passive, and "He was
very tired after the run" as not.

English has no formal boundary between a verbal passive ("the house was built")
and an adjectival one ("he was tired"): the same word does both jobs, and
telling them apart needs the meaning of the sentence. Excluding adjectival
participles wholesale would silently drop "the report was published" alongside
"he was bored", because there is no way to know which is which from the form.

So the detector counts them, with one linguistic test as the exception. A
verbal passive cannot be graded — "the house was very built" is not English —
so a degree adverb (`very`, `quite`, `too`, `extremely`, ...) directly in front
of the participle settles it as an adjective. That test is precise where a word
list would be a guess.

Two further guards keep the count honest, both about words that only look like
participles:

- a candidate directly behind a determiner is a noun, not a verb: "there was a
  wound on his arm", "it is a mixed bag";
- clauses are scanned separately, so "although he was late, we finished the
  work" cannot pair an auxiliary in one clause with a participle in another.

The consequence to be aware of: a page describing feelings ("readers were
delighted", "the team was excited") scores as more passive than a strict verbal
reading would call it. That is the intended trade — the wording is still what
the assessment is asking the author to look at.

Tested in `PassiveVoiceTest`, whose forty-five curated sentences pin both sides
of the line.

## Passive voice in Dutch and German

The English ruling above — an adjectival participle counts unless a degree
adverb grades it — carries over to both packs unchanged. "Ze was verbaasd" and
"er war überrascht" are passive; "ze was erg verbaasd" and "er war sehr
überrascht" are not. What follows is only where the two languages needed a
decision English did not pose.

### A clause holds both, in either order

The English detector looks a few words ahead of its auxiliary. Neither Dutch nor
German lets it: both push the participle to the end of its clause ("de brief
werd gisteren door de secretaresse geschreven", "der Bericht wurde von der
Abteilung erstellt"), and in a subordinate clause the finite verb moves behind
it again ("… dat het huis verkocht is", "… dass das Haus verkauft wurde"). No
window and no direction describes that.

So both detectors ask a different question: **does one clause hold both an
auxiliary and a participle?** That is only honest if the clause is cut small
first, and it is cut three ways:

- on punctuation, as in English;
- on the conjunctions that open a subordinate clause. German writes a comma
  before those anyway, so this mostly matters for Dutch, which does not: "ik
  denk dat het klaar is" has no comma at all;
- on the coordinating conjunctions that join two whole sentences (`en`/`und`,
  `maar`/`aber`, `of`/`oder`, `want`/`denn`). Without this last cut, "het gebouw
  is groot en veel mensen hebben het bezocht" would pair the auxiliary of the
  first half with the participle of the second.

Subject pronouns are **not** clause starters here, though they are in English.
Both languages put the finite verb second, so the subject regularly follows its
own auxiliary — "gisteren werd het huis verkocht" — and breaking there would
throw the passive away rather than find it.

The cost of the looser pairing is a sentence where a comma-free relative clause
puts a past tense next to an unrelated auxiliary ("de man die het huis verkocht
is mijn buurman"). That is rare, and the alternative is missing most real Dutch
and German passives.

### zijn and sein also build a perfect, and only the verb knows which

Both packs count the state passive: "de brief is geschreven", "die Tür ist
verschlossen". That is what a reader hears, and it keeps all three languages
consistent.

The price is that `zijn`/`sein` is also the perfect auxiliary of every verb that
describes a change rather than a deed: "hij is gekomen", "de prijzen zijn
gestegen", "er ist gefahren", "die Preise sind gestiegen". Those are not
passive, no auxiliary can say so, and no guard about the *shape* of the word can
either — the difference is that the verb takes no object.

So each detector carries a list of the participles that never form a passive
(`PERFECT_ONLY_PARTICIPLES`). It sits next to the determiner and degree-adverb
guards rather than in `passive/non-participles.php`, because these words really
are participles: the detector recognises them and then declines to count them,
which is not the same as pretending they are nouns.

**The guard is auxiliary-aware, and has to be.** It exists to suppress the
*perfect* reading, and only `zijn`/`sein` has a perfect — `worden`/`werden` has
none. So it applies behind a zijn/sein form and never behind a worden/werden
one. Without that, the guard reaches past what it is for and kills genuine
passives: "das Problem wurde eskaliert" is a textbook Vorgangspassiv (German
lets *ein Ticket eskalieren* take an object, and ITSM copy says so daily), and
suppressing it because *eskalieren* also has an intransitive perfect would be
the guard answering a question nobody asked.

**The pairing is per participle, not per clause.** "Which auxiliary" is only a
meaningful question about a particular verb phrase, so each participle is paired
with the nearest auxiliary in its clause and that one decides. Asking it once
for the whole clause instead — does this clause contain a worden form anywhere?
— lets an unrelated token resurrect a guarded participle: "het bedrijf is
gegroeid om marktleider te worden" would pair the *worden* of a purpose clause
with a *gegroeid* that plainly belongs to *is*, and report a perfect as a
passive. German has the same shape, usually hidden behind the comma it writes
before "um … zu" and fully exposed the moment headline or bullet copy drops it.

Nearest, rather than a direction, because both languages put the finite verb
before its participle in a main clause and after it in a subordinate one:
distance says more than direction does. Ties go to the auxiliary in front.

Nearest-pairing is the whole rule, and it is enough on its own. In the example
above, *gegroeid* sits one word from *is* and four from *worden*, so it keeps
the auxiliary it belongs to without any further help. An infinitive is an
auxiliary like any other here, marked with *te*/*zu* or not: "te worden" cannot
govern a participle outside its own phrase, but it governs the one inside it
perfectly well, and those are among the commonest passives either language
writes — "het formulier dient te worden ingevuld", "hij hoopt om gekozen te
worden", "er hofft gewählt zu werden", "die Daten scheinen gelöscht zu werden".
Excluding marked infinitives from pairing would throw the phrase out along with
the boundary.

The membership test is therefore narrower than "can this verb take an object":
it is whether the verb has a perfect with zijn/sein that would otherwise read as
a state passive. *Verschijnen*, *bezwijken*, *aflopen*, *versinken*, *ausfallen*
and *eintreffen* do, so "het artikel is verschenen" and "das Konzert ist
ausgefallen" are perfects — and "is verschenen" is everyday publishing copy,
exactly the register this package reads.

Being auxiliary-aware is also what makes an ambitransitive verb safe to list:
the entry costs nothing on the worden/werden side, so "de bladzijde werd
omgeslagen", "de situatie werd verslechterd", "das Auto wurde gefahren" and
"die Tickets werden eskaliert" all still count. Verbs whose two readings are
both common behind *zijn*/*sein* (*bevriezen*, *verdrinken*, *zerbrechen*) stay
out of the guard entirely, because there it would have to choose: "de tegoeden
zijn bevroren" and "de rivier is bevroren" are the same four words.

Because the guard and `passive/irregular-participles.php` answer opposite
questions, a word in both is a straight contradiction — one asserting the word
marks a passive and the other that it never can. A unit test per language pins
that the two sets do not overlap.

Dutch and German disagree about *beginnen*, and correctly: Dutch takes *zijn*
for it ("de film is begonnen"), so `begonnen` is in the Dutch guard, while
German takes *haben* ("der Film hat begonnen"), so `begonnen` never follows a
German passive auxiliary and stays an ordinary participle there.

`sein` itself is deliberately **not** in the German auxiliary list, only its
inflected forms. Bare "sein" is also the possessive determiner, and listing it
would read "Sein Vater hat das Auto gekauft" as passive.

### German: werden is the future auxiliary too

This is the one place where a rule that works in English and Dutch actively
misleads in German. `werden` builds the passive *and* the future, so "er wird
kommen", "er wird bezahlen" and "sie wird uns verstehen" all carry a passive
auxiliary and are not passive at all. Worse, "sie wird Ärztin" uses it as
"become".

The only thing separating those from "er wird bezahlt" is the shape of the
second verb, so the German participle rule is deliberately narrow about `-en`:
it counts only when the word carries `ge-` (which no infinitive has) or appears
in `passive/irregular-participles.php`. An inseparable prefix plus `-en` —
"bezahlen", "verstehen", "erklären" — is an infinitive, and an infinitive after
`wird` is a future.

The residue is real and bounded: a handful of verbs spell their infinitive and
their participle identically ("bekommen", "erhalten", "verlassen", "vergessen"),
so "er wird das Paket bekommen" is counted as passive. German offers no way to
tell those apart by form, and the alternative — dropping them from the list —
would miss every real passive those verbs build.

### German: three participle shapes, not one

German writes its participle three ways, and all three had to be recognised:

- `ge-` with `-t` or `-en`, optionally behind a separable prefix that puts the
  `ge-` in the middle of the word: "gebaut", "geschrieben", "durchgeführt",
  "eingeladen";
- an inseparable prefix with `-t`: "bezahlt", "verkauft", "erhöht";
- a verb borrowed into `-ieren`, which takes no `ge-` at all: "informiert",
  "organisiert", "dokumentiert".

That third shape is easy to forget and covers a great deal of modern German
business copy — a paragraph saying "wurde nie ordentlich dokumentiert" would
otherwise read as active. It is safe against the future trap for the same reason
the others are: the infinitive ends in `-ieren`, not `-iert`.

Dutch needs no equivalent rule, because its borrowed verbs keep the `ge-`
("georganiseerd", "gepubliceerd").

### Dutch separable verbs are built on adjectives too

Both packs take an optional separable prefix in front of the `ge-`, because that
is where most real passives live: "aangepast", "opgelost", "uitgevoerd";
"durchgeführt", "eingeführt", "abgeholt".

Dutch needs more than the particles for that. It also builds separable verbs on
an **adjective** — *goedkeuren*, *schoonmaken*, *vrijgeven*, *leegmaken*,
*volboeken*, *kapotmaken*, *stopzetten* — and those behave identically: "de
begroting is goed-ge-keurd", "de kamer werd schoon-ge-maakt". Leaving them out
cost a class of everyday passive, so `goed`, `schoon`, `vrij`, `leeg`, `vol`,
`kapot` and `stop` are prefixes as well.

They are safe to add because the rule still demands a `ge-` and a stem of at
least two letters behind the prefix, so an ordinary compound cannot reach it:
"goederen", "vrijheid", "volgend", "volwassen", "schoonheid" and "stopcontact"
all fail on the part that follows, and each is pinned as a negative.

What *does* reach it is a prefix in front of a ge- noun or adjective —
"goedgezind", "leeggewicht", the same shape as the "overgewicht" that was
already there. Those are spelled exactly like a separable participle and are
not one, so they join the ge- nouns in `passive/non-participles.php`.

### Where a prefix only looks separable

Both languages have verbs whose prefix is spelled exactly like a separable one
and behaves like an inseparable one, so they take no `ge-` anywhere:
"ondertekend", "onderzocht", "voldaan", "overwogen"; "umfasst", "durchsucht",
"vollendet". Each is listed in `passive/irregular-participles.php` rather than
ruled, because widening the prefix rule to `onder-`/`over-`/`um-`/`durch-` would
swallow "onderhoud", "overzicht", "achtergrond", "Umwelt" and "Durchschnitt"
along with them.

### Which participial adjectives stay participles

`bekend`, `bereid`, `verbaasd`, `verkeerd` (nl) and `bekannt`, `beliebt`,
`bewusst`, `verwandt`, `geeignet` (de) are all left **out** of the
non-participle lists. Each is a genuine participle — of *bekennen*, *bereiden*,
*verbazen*, *verkeren*, *bekennen*, *belieben*, *eignen* — that also works as an
adjective, and their adjectival use is handled by the degree-adverb rule,
exactly as English handles "tired" and "excited". Listing them would make the
degree-adverb guard untestable and would silently drop "het is bekend" alongside
"hij is erg bekend".

German `bereit` is the one that goes the other way, and it is worth naming
because it looks identical to the Dutch word: there is no verb it is the
participle of (*bereiten* makes *bereitet*), so it **is** on the German
non-participle list. Dutch `bereid` really is the participle of *bereiden*, so
it is not on the Dutch one. The same-looking word gets opposite treatment for a
reason that is about the language, not about consistency.

`geleden` (nl) is a third kind of call. It is a real participle of *lijden*, but
in web copy it is overwhelmingly the word "ago" ("twee jaar geleden"), while the
passive of *lijden* is vanishingly rare — so it is listed as a non-participle.

Tested in `Language/Nl/PassiveVoiceTest` and `Language/De/PassiveVoiceTest`,
each of which pins both directions with sixty-odd curated sentences.

## Counting syllables in Dutch and German

Neither language needs English's silent-e rule: the final -e of "mode",
"gemeente", "Katze" and "Sprache" is spoken, and counting it is simply right.
Both counters instead add a rule English has no use for.

### A vowel run splits where the language does not spell it as one sound

"oe", "ij", "aai" and "eeu" are single Dutch sounds; "eo", "ea", "ua" and "io"
never are. "ei", "au", "eu" and "ie" are single German sounds; "io", "ea" and
"ua" never are. So each counter walks a run of vowels taking the longest cluster
its language really spells, and counts every leftover vowel on its own.

That one rule is what makes "the-a-ter", "ja-nu-a-ri", "vi-de-o", "si-tu-a-tie",
"Na-ti-on", "The-a-ter" and "Si-tu-a-ti-on" come out right with no word list at
all. In particular the whole German `-tion` family is handled by rule rather
than by enumeration, which is the difference between a counter that stays right
and one that rots.

The deviation lists are correspondingly small, and hold two things. The opposite
case — a pair the language really does spell as one sound, in a word where it
happens to be two: "mu-se-um", "se-ri-eus", "I-de-en", "Fa-mi-li-en". And the
English words both languages have taken whole and still say the English way:
"website", "online", "team", "software" are one or two beats, where the Dutch
and German reading of their vowels would give three. No spelling rule can tell
a loanword from a native one, and those particular words appear on nearly every
page a CMS holds. Compounds built on them ("klantenservice", "Kundenservice")
are not covered, because the map is looked up on the exact word.

### Dutch reads the diaeresis as the syllable break it is

Dutch writes ë, ï, ö and ü precisely to say "a new syllable starts here", which
is the only reason "ideeën", "patiënt", "ruïne" and "coördinatie" carry one. The
Dutch counter splits a vowel run at any diaeresis that follows a vowel and then
drops the mark, so "beëindigen" reads as be-ëin-di-gen. German has no diaeresis
— ä, ö and ü are ordinary vowels there — so the German counter does no such
thing.

### Two small shared rules

`qu` is one consonant plus the vowel after it, not two vowels: "Quel-le" is two
beats, not three. And `y` in front of a vowel is the consonant it sounds like
("yo-ga", "Bay-ern") while it is a vowel everywhere else ("systeem", "A-na-ly-se").

### Everything is mb-safe

Both counters work through `preg_*` with `/u` and `mb_substr`/`mb_strlen` on
whole strings. There is no byte indexing anywhere, because "ruïne", "Häuser" and
"Straße" have to survive being counted. (The English counter still indexes by
byte in two places; it only ever sees ASCII endings, but the newer counters
deliberately do not copy the pattern.)

Tested in `Language/Nl/SyllableCounterTest` and `Language/De/SyllableCounterTest`,
each pinning every word of a hand-verified fixture of over a hundred words.

## Reading ease per language

Each language scores on its own published adaptation, because the constants are
fitted to how much a language says per word and per sentence:

| Language | Formula | Source |
| --- | --- | --- |
| English | 206.835 − 1.015 × ASL − 84.6 × syllables per word | Flesch, 1948 |
| Dutch | 206.84 − 0.77 × syllables per 100 words − 0.93 × ASL | Douma, 1960 |
| German | 180 − ASL − 58.5 × syllables per word | Amstad, 1978 |

All three land on the same hundred-point scale and are banded by the same
`FleschBand`, so a Dutch page and an English page reading "easy" mean the same
thing to an editor. Each is pinned by a fixture whose words and syllables are
counted by hand in the test's own comment.
