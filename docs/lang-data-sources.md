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
`tests/Unit/Analysis/Language/En/`, which pin the behaviour each list exists to
produce rather than the list itself.

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

## Heuristics that live in code rather than in data

Two English-specific word sets are constants in
`src/Analysis/Language/En/EnglishPassiveVoiceDetector.php` rather than data
files, because they encode the detection heuristic instead of describing the
language: the determiners that mark a candidate as a noun (`a wound`), and the
degree adverbs that mark it as an adjective (`very excited`). They are original
compilations on the same terms as everything above.
