<?php

/*
 * English transition words and phrases.
 *
 * A sentence that opens with one of these tells the reader how it relates to
 * the one before it, which is what the transition assessment measures.
 *
 * Hand-compiled per rhetorical function — see docs/lang-data-sources.md.
 * Multi-word entries are matched as whole phrases on word boundaries, so
 * "in addition" only counts when both words really are next to each other.
 */

return [
    // Additive: this and that.
    'additionally', 'furthermore', 'moreover', 'also', 'besides', 'similarly', 'likewise',
    'in addition', 'as well as', 'not to mention', 'what is more', 'coupled with',
    'in the same way', 'in the same vein', 'equally important',

    // Contrast: this, but that.
    'however', 'nevertheless', 'nonetheless', 'conversely', 'instead', 'alternatively',
    'although', 'though', 'whereas', 'despite', 'notwithstanding', 'regardless',
    'on the other hand', 'in contrast', 'by contrast', 'on the contrary', 'in spite of',
    'even so', 'even though', 'at the same time', 'in any case', 'all the same',

    // Cause and effect: this, so that.
    'therefore', 'consequently', 'thus', 'hence', 'accordingly', 'thereby', 'because', 'since',
    'as a result', 'as a consequence', 'because of', 'due to', 'owing to', 'so that',
    'for this reason', 'in order to', 'that is why',

    // Sequence: this, then that.
    'first', 'firstly', 'second', 'secondly', 'third', 'thirdly', 'next', 'then',
    'finally', 'lastly', 'afterwards', 'afterward', 'meanwhile', 'subsequently',
    'eventually', 'previously', 'earlier', 'later', 'simultaneously',
    'in the meantime', 'to begin with', 'at first', 'at last', 'for now',

    // Example and emphasis: this, for instance.
    'specifically', 'notably', 'particularly', 'especially', 'indeed', 'certainly',
    'obviously', 'clearly', 'importantly', 'namely', 'undoubtedly', 'surely',
    'for example', 'for instance', 'in particular', 'above all', 'in fact', 'such as',
    'that is', 'to illustrate', 'as an illustration',

    // Summary: this is what it came to.
    'altogether', 'overall', 'ultimately', 'in conclusion', 'in short', 'in summary',
    'to summarize', 'to sum up', 'on the whole', 'in brief', 'all in all', 'in the end',
    'in other words', 'to put it another way', 'for the most part', 'in general',

    // Condition: this, if that.
    'if', 'unless', 'otherwise', 'provided that', 'in case', 'as long as', 'even if',
    'only if', 'in that case', 'if so', 'if not',

    // Time: this, when that.
    'before', 'after', 'during', 'while', 'until', 'when', 'whenever', 'immediately',
    'currently', 'as soon as', 'by the time', 'up to now', 'so far', 'at present',
];
