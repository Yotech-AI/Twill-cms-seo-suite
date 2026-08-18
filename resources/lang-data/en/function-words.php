<?php

/*
 * English function words.
 *
 * Words that carry grammar rather than meaning. Stripping them is what turns a
 * keyphrase into the words a text actually has to contain: "the best dog food"
 * is about dog food, not about "the" and "best of".
 *
 * Hand-compiled from standard English grammar categories — see
 * docs/lang-data-sources.md. Two rules were applied throughout:
 *
 * 1. A word is only listed when it is a function word in *every* reading. Words
 *    that double as a content word an author might really be targeting are left
 *    out on purpose: "whole" (whole grain bread), "half" (half marathon),
 *    "past" (past life regression), "like" (like button), "lot" (parking lot),
 *    "pretty" (pretty dresses). Stripping those would silently widen the
 *    keyphrase to something the author never asked for.
 * 2. A keyphrase made of nothing but these words falls back to matching every
 *    word (KeyphraseMatcher::contentWords), so an "about us" or "how to" phrase
 *    still matches something sensible.
 */

return [
    // Articles and other determiners.
    'a', 'an', 'the', 'this', 'that', 'these', 'those', 'such', 'same', 'another', 'other', 'others',

    // Personal, possessive and reflexive pronouns.
    'i', 'me', 'my', 'mine', 'myself',
    'you', 'your', 'yours', 'yourself', 'yourselves',
    'he', 'him', 'his', 'himself',
    'she', 'her', 'hers', 'herself',
    'it', 'its', 'itself',
    'we', 'us', 'our', 'ours', 'ourselves',
    'they', 'them', 'their', 'theirs', 'themselves',
    'ones', 'oneself',

    // Relative, interrogative and indefinite pronouns.
    'who', 'whom', 'whose', 'which', 'what', 'whatever', 'whichever', 'whoever', 'whomever',
    'anybody', 'anyone', 'anything', 'everybody', 'everyone', 'everything',
    'nobody', 'nothing', 'somebody', 'someone', 'something',

    // Prepositions.
    'about', 'above', 'across', 'after', 'against', 'along', 'amid', 'among', 'amongst', 'around',
    'as', 'at', 'before', 'behind', 'below', 'beneath', 'beside', 'besides', 'between', 'beyond',
    'by', 'concerning', 'despite', 'down', 'during', 'except', 'for', 'from',
    'in', 'inside', 'into', 'near', 'of', 'off', 'on', 'onto', 'out', 'outside', 'over',
    'per', 'regarding', 'since', 'through', 'throughout', 'till', 'to', 'toward', 'towards',
    'under', 'underneath', 'unlike', 'until', 'unto', 'up', 'upon',
    'versus', 'vs', 'via', 'with', 'within', 'without',

    // Coordinating and subordinating conjunctions.
    'and', 'but', 'or', 'nor', 'yet', 'so', 'because', 'although', 'though', 'while', 'whereas',
    'unless', 'if', 'than', 'whether', 'once', 'lest', 'plus',

    // Auxiliaries and modals.
    'be', 'am', 'is', 'are', 'was', 'were', 'been', 'being',
    'have', 'has', 'had', 'having',
    'do', 'does', 'did', 'doing',
    'can', 'could', 'will', 'would', 'shall', 'should', 'may', 'might', 'must', 'ought',

    // Quantifiers.
    'all', 'any', 'both', 'each', 'either', 'enough', 'every', 'few', 'fewer', 'fewest',
    'least', 'less', 'little', 'many', 'more', 'most', 'much', 'neither', 'none', 'several',
    'some', 'various',

    // Cardinal numbers one to twenty.
    'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten',
    'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen',
    'nineteen', 'twenty',

    // Ordinal numbers first to tenth.
    'first', 'second', 'third', 'fourth', 'fifth', 'sixth', 'seventh', 'eighth', 'ninth', 'tenth',

    // Interjections and answer words.
    'oh', 'ah', 'well', 'yes', 'no', 'hey', 'hi', 'hello', 'wow', 'oops', 'ouch', 'hmm', 'huh',

    // Adverbs of degree, frequency and stance: they qualify a claim rather than
    // make one, so they are never the subject of a page.
    'very', 'really', 'quite', 'rather', 'too', 'just', 'almost', 'nearly', 'hardly', 'barely',
    'scarcely', 'somewhat', 'fairly', 'extremely', 'absolutely', 'completely', 'totally',
    'entirely', 'simply', 'only', 'even', 'still', 'already', 'again', 'ever', 'never',
    'always', 'often', 'sometimes', 'usually', 'rarely', 'seldom', 'now', 'then', 'here',
    'there', 'also', 'indeed', 'perhaps', 'maybe', 'probably', 'certainly', 'definitely',
    'surely', 'actually', 'generally', 'mainly', 'mostly', 'particularly', 'truly', 'merely',
    'largely', 'not',
];
