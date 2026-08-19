# Language data: where every list came from

This package is MIT licensed and must stay that way. The word lists under
`resources/lang-data/` are therefore **original compilations**: they were
written from standard grammar knowledge for this package, entry by entry. No
list was copied, translated, converted or diffed from another SEO plugin, its
source tree, its distribution files or its documentation.

What *is* taken from published analysis research is the set of **thresholds**
(percentages, word counts, score bands). Those are facts about how a text is
judged, not creative expression, and they are implemented in the assessments
rather than stored here.

Every message an editor reads is likewise original wording, written for this
package in `resources/lang/en/analysis.php`.

## Method

Each list below was compiled by working through the grammatical category it
covers and writing out its members, then reading the result back with two
questions:

1. **Is every entry really a member of this category?** A wrong entry does not
   merely add noise, it ships wrong analysis — an over-eager function word
   silently rewrites the author's keyphrase, and a missing participle reports
   passive prose as active.
2. **Does the entry do harm in its other readings?** English words carry
   several parts of speech at once, so anything ambiguous was judged on which
   reading a CMS author is more likely to have meant, and the decision was
   written down next to the list.

The lists were then checked against the unit tests in
`tests/Unit/Analysis/Language/{En,Nl,De}/`, which pin the behaviour each list
exists to produce rather than the list itself.

The Dutch and German lists were compiled the same way and under the same two
questions, from standard Dutch and German grammar categories. Two habits carried
across from the English pass and are worth stating, because they explain why the
lists are the size they are:

- **Nothing is listed that a rule already covers.** The Dutch and German
  participle recognisers work from the shape of the word (a prefix, a stem, an
  ending), so a list that repeated what the shape rule derives would only rot
  away from it. What is listed is what falls outside the shape — and, in the
  non-participle files, only the words the shape rule would actually catch.
  "gewoon", "gezellig", "gerade" and "gemeinsam" end in letters no participle
  ending allows, so they are safe already and are deliberately absent, exactly
  as the English non-participle list leaves out the three-letter words its own
  length rule excludes.
- **A judgement call is written next to the entry it decided.** Where a word
  could reasonably go either way — `bekend`, `bereid`, `verbaasd`, `geleden`,
  `bereit` — the reasoning is in the data file's header and, at length, in
  `docs/analysis.md`.

Compiled by the package authors, August 2026.

## The lists

| File | What it is | Size | Notes on judgement calls |
| --- | --- | --- | --- |
| `en/function-words.php` | Words that carry grammar rather than meaning, removed from a keyphrase to leave the words a text must actually contain. | 288 | Compiled per category: articles and determiners, personal/possessive/reflexive/relative/indefinite pronouns, prepositions, conjunctions, auxiliaries and modals, quantifiers, cardinals one–twenty, ordinals first–tenth, interjections, and adverbs of degree, frequency and stance. Words that double as a plausible subject were deliberately excluded — `whole` (whole grain bread), `half` (half marathon), `past` (past life regression), `like` (like button), `lot` (parking lot), `pretty` (pretty dresses). `well`, `no` and `yes` are included as the interjections they mostly are, at the known cost of `well drilling`. |
| `en/transition-words.php` | Words and phrases that signal how a sentence relates to the one before it. | 140 | Compiled per rhetorical function: additive, contrast, cause and effect, sequence, example and emphasis, summary, condition, time. Multi-word entries are stored as phrases and matched on word boundaries, so `in addition` cannot fire on `in this addition`. |
| `en/two-part-transitions.php` | Correlative conjunctions, whose halves sit apart in the sentence. | 10 | Stored as ordered pairs; the second half only counts when it follows the first, so `dogs and cats are both welcome` is not the `both … and` construction. |
| `en/first-word-exceptions.php` | Words stepped over when comparing how consecutive sentences begin. | 25 | Articles, demonstratives, possessive determiners and the small cardinals — the words that open a sentence without being what it is about. |
| `en/abbreviations.php` | Words that end in a dot without ending a sentence. | 29 | Titles, references, measurements, company and address forms. `no.` was deliberately left out: a sentence ending in the word "no" is far more common in web copy than a numbered reference, and treating it as an abbreviation would glue two real sentences together. |
| `en/passive/auxiliaries.php` | The verbs that carry an English periphrastic passive. | 13 | Forms of *be* and *get*. `have/has/had` are excluded on purpose — they mark the perfect, not the passive; `The results have been published` is caught through `been`. |
| `en/passive/irregular-participles.php` | Past participles that do not end in -ed. | 186 | Written out from the standard irregular verb paradigms, including the British `-t` forms (burnt, learnt, spelt). Past-tense-only forms were checked for and removed: `went` is not a participle and would have made every "was" before a "go" clause read as passive. |
| `en/passive/non-participles.php` | Words ending in -ed that are not participles. | 39 | Two groups: base forms and nouns (`need`, `indeed`, `speed`, `breed`, `hundred`), and adjectives that were never verbs, whether ancient (`naked`, `wicked`, `sacred`) or formed from a noun (`talented`, `skilled`). Three-letter words (`bed`, `red`, `led`, `fed`) are absent because the detector's four-letter minimum already excludes them — and `led`/`fed`/`wed` are genuine participles that have to keep matching. Participles that also work as adjectives (`tired`, `bored`, `excited`, `married`) are absent too: they are participles, and their adjectival use is handled by the degree-adverb rule instead. |
| `en/syllables.php` | Words whose spelling misstates how many syllables are spoken. | 32 | Collected by running the vowel-group counter over ordinary English and writing down what it got wrong, in both directions: words spoken with fewer beats than they are spelled (`business`, `every`, `chocolate`), and adjacent vowels that are pronounced apart (`idea`, `video`, `create`). Inflections need their own entry because the map is looked up on the exact word. |
| `nl/function-words.php` | As above, for Dutch. | 374 | Compiled per category: articles and demonstratives, the full pronoun sets, the pro-adverbs Dutch builds out of `er`/`daar`/`hier`, prepositions, conjunctions, every form of *zijn*, *hebben* and *worden*, the modals, quantifiers, cardinals and ordinals, and adverbs of degree, time and stance. Words that double as a plausible subject are excluded — `vrij` (vrij parkeren), `half` (halve marathon), `gaan` (leren gaan), `recht`, `licht`. Forms written with a leading apostrophe (`'t`, `'n`, `z'n`) are absent: the word tokenizer only joins an apostrophe between two letter runs, so they never reach the list as written. |
| `nl/transition-words.php` | As above, for Dutch. | 124 | Same eight rhetorical functions as the English list. A phrase whose first word is already listed on its own was dropped rather than kept — `kort gezegd` could never fire that `kortom` did not already catch. |
| `nl/two-part-transitions.php` | As above, for Dutch. | 12 | `zowel … als`, `enerzijds … anderzijds`, `niet alleen … maar ook`, `hetzij … hetzij`, `hoe … hoe`, and the rest. |
| `nl/first-word-exceptions.php` | As above, for Dutch. | 26 | Articles, demonstratives, possessive determiners and the small cardinals, plus the existential `er`, which opens a Dutch sentence the way "there" opens an English one. |
| `nl/abbreviations.php` | As above, for Dutch. | 31 | Titles, references, measurements and company forms. Multi-dot forms (`o.a.`, `d.w.z.`, `a.u.b.`) are left out rather than listed inertly: the tokenizer reads the word in front of the terminator, so they could never match, and a list that quietly does nothing invites the next reader to add more of the same. Every entry is a form that is never a Dutch word on its own. |
| `nl/passive/auxiliaries.php` | The verbs that carry a Dutch passive. | 13 | Forms of *worden* (the deed as it happens) and of *zijn* (the state it left behind). *hebben* is excluded — it marks the perfect. |
| `nl/passive/irregular-participles.php` | Dutch participles the shape rule cannot derive. | 82 | Three groups: strong participles of the inseparable verbs (`verloren`, `ontvangen`, `begrepen`), which end in -en where the shape rule reserves that ending for ge- words; verbs whose prefix only looks separable (`ondertekend`, `voldaan`, `overwogen`), which take no ge- either; and the -aan participles (`gedaan`, `verstaan`) that no ending in the rule allows. Regular and ge- participles are deliberately absent — the shape rule already derives every one of them. Every entry was checked against one question: can this verb take an object? Those that cannot (`verschenen`, `bezweken`, `ontgaan`) build a perfect and never a passive, so they sit in the detector's guard instead; the ones that are unaccusative in one reading and transitive in another (`bevroren`, `verdronken`) stay here, because the transitive passive they build is real. |
| `nl/passive/non-participles.php` | Dutch words that look like a participle and are not. | 71 | Four groups: ge-/be-/ver- nouns ending in -d or -t (`gebied`, `geluid`, `beeld`, `voorbeeld`), including the compounds of a separable prefix with a ge- noun or adjective that are spelled exactly like a separable participle (`overgewicht`, `leeggewicht`, `goedgezind`); ge- noun plurals in -en (`gedachten`, `gevolgen`); verb forms that are not participles (`geeft`, `gelden`, `gebeuren`); and the present participles in -end (`verrassend`, `vervelend`, `beslissend`), which Dutch spells exactly like the past participle of a verb whose stem ends in -en (`geopend`, `getekend`) — so the difference cannot be ruled and the common ones are listed. `geleden` is a judgement call: a real participle of *lijden*, but overwhelmingly the word "ago" in web copy. `bekend`, `bereid`, `verbaasd` and `verkeerd` are deliberately absent — see `docs/analysis.md`. |
| `nl/syllables.php` | As above, for Dutch. | 21 | Two groups. The first is small, because the counter splits a vowel run wherever Dutch does not spell that pair as one sound and reads the diaeresis as the syllable break Dutch writes it to be; what is left is the opposite case, where `eu` or `ieu` really is one sound everywhere except here (`mu-se-um`, `se-ri-eus`, `in-ge-ni-eur`). The second is the English words Dutch has taken whole and still says the English way (`website`, `online`, `team`, `software`), where no spelling rule can help and which turn up on nearly every page a CMS holds. |
| `de/function-words.php` | As above, for German. | 435 | The longest list by construction, because German declines almost everything: every article, demonstrative and possessive appears in each case form an author might type, and missing one would leave a keyphrase silently wider than asked for. Also the contracted prepositions (`am`, `im`, `zum`, `zur`, `ins`), every form of *sein*, *haben* and *werden*, and all six modals. Words that double as a plausible subject are excluded — `recht` (Recht auf Auskunft), `halb`, `gleich`, `wert`, `voll`. |
| `de/transition-words.php` | As above, for German. | 119 | Same eight rhetorical functions as the English list. |
| `de/two-part-transitions.php` | As above, for German. | 11 | `sowohl … als auch`, `entweder … oder`, `weder … noch`, `nicht nur … sondern auch`, `je … desto`, `zwar … aber`, and the rest. |
| `de/first-word-exceptions.php` | As above, for German. | 37 | Longer than the English list for the same reason the function words are: a sentence opening with `Dem` is exactly as uninformative as one opening with `Der`, so every case form is listed. |
| `de/abbreviations.php` | As above, for German. | 31 | Titles, references, measurements and address forms. Multi-dot forms (`z. B.`, `d. h.`, `u. a.`) are left out for the same reason as in Dutch. `Art.` was deliberately excluded: a sentence ending in the noun *Art* is far more common in web copy than a numbered article. |
| `de/passive/auxiliaries.php` | The verbs that carry a German passive. | 27 | Forms of *werden* (including `worden`, the participle a perfect passive takes) and of *sein* (the Zustandspassiv). *haben* is excluded — it marks the perfect. Bare `sein` is excluded too: it is also the possessive determiner, and listing it would read "Sein Vater hat das Auto gekauft" as passive. |
| `de/passive/irregular-participles.php` | German participles the shape rule cannot derive. | 100 | Written out from the standard strong verb paradigms for the inseparable prefixes (`verstanden`, `bekommen`, `empfohlen`, `unterschrieben`). This list exists because of *werden*: the shape rule cannot simply allow "ver- … -en", since that is also every infinitive those verbs have, and *werden* is the German future auxiliary. Also the verbs whose prefix only looks separable (`umfasst`, `durchsucht`, `vollendet`) and `getan`, which ends in -n. Several entries are spelled identically to their own infinitive (`bekommen`, `erhalten`, `verlassen`); German offers no way to tell those apart by form, and the cost is written down in `docs/analysis.md`. |
| `de/passive/non-participles.php` | German words that look like a participle and are not. | 63 | Three groups: ge- nouns and adjectives ending in -t (`Gebiet`, `Gerät`, `Geschäft`, `gesamt`); ge- verbs whose infinitive ends in -en and is not a participle (`gelten`, `gehören`, `gewinnen`, `gelingen`), together with the ge- noun plurals that land on the same shape (`Gedanken`, `Gebäuden`); and inseparable-prefix nouns and adverbs ending in -t (`Bericht`, `Verlust`, `Übersicht`, `überhaupt`). `geben`, `gehen` and `gegen` need no entry — after ge- they have no room left for a stem of two letters plus an ending, so the shape rule never reaches them. `bereit` is here and Dutch `bereid` is not, for a reason about the two languages that is set out in `docs/analysis.md`. |
| `de/syllables.php` | As above, for German. | 30 | Two groups, as in Dutch. The rule-derived part stays small because the vowel-run split handles the whole `-tion` family; what is left is `eu`/`ee`/`ie` in the words where they are two beats — `Mu-se-um`, `I-de-en`, and the `-ien` plurals of nouns ending in -ie (`Fa-mi-li-en`, `Fe-ri-en`). Those plurals cannot be ruled: a blanket "-ien is two beats" would break `schien`, `erschien` and `Wien`. The second group is the English loanwords German says the English way (`Website`, `online`, `Team`, `Software`). |

## Heuristics that live in code rather than in data

Some word sets are constants in the passive detectors rather than data files,
because they encode the detection heuristic instead of describing the language.
They are original compilations on the same terms as everything above.

All three detectors carry two: the determiners that mark a candidate as a noun
(`a wound`, `het gewicht`, `das Gebiet`), and the degree adverbs that mark it as
an adjective (`very excited`, `erg verbaasd`, `sehr begeistert`). The English
list is in `En/EnglishPassiveVoiceDetector.php`, the Dutch in
`Nl/DutchPassiveVoiceDetector.php`, the German in
`De/GermanPassiveVoiceDetector.php`.

The Dutch and German detectors carry two more, for the two things English does
not have to decide:

- the clause starters, which cut a sentence into the clauses one auxiliary may
  govern. These are the subordinating and coordinating conjunctions, and
  deliberately **not** the subject pronouns the English list includes — both
  languages put the verb second, so the subject regularly follows its own
  auxiliary;
- the participles whose verb has a perfect with zijn/sein that would otherwise
  read as a state passive (`gekomen`, `verschenen`, `afgelopen`; `gefahren`,
  `versunken`, `ausgefallen`). They are guards rather than data entries because
  they really are participles: the detector recognises them and then declines to
  count them, which is not the same as pretending they are nouns. The guard
  applies only behind a zijn/sein auxiliary — `worden`/`werden` has no perfect
  to suppress — which is why each detector also carries the split of its own
  auxiliary list into the worden/werden forms (`PROCESS_AUXILIARIES`). A unit
  test pins that no word ever sits in both this guard and the
  irregular-participle list: the two answer opposite questions, so a word in
  both is a straight contradiction.

Both are explained at length in `docs/analysis.md`.
