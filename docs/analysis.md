# The content analysis

## Divergences

Where this engine deliberately judges a text differently from the analysis it
takes its thresholds from. Each one is a decision, not an accident, and each is
pinned by a test.

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
