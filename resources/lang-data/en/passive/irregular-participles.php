<?php

/*
 * English irregular past participles.
 *
 * Regular participles are recognised by their -ed ending; these are the ones
 * that end in anything else. Without them "The ball was thrown" reads as
 * active, which is the single most visible way a passive check can be wrong.
 *
 * Many of these double as a noun ("a cut", "the cost", "a run"). The detector
 * skips a candidate that is directly preceded by a determiner, which is what
 * keeps "There was a wound on his arm" out of the passive count, so those words
 * are safe to list here as the participles they also are.
 *
 * Hand-compiled from the standard irregular verb paradigms — see
 * docs/lang-data-sources.md.
 */

return [
    'arisen', 'awoken',
    'beaten', 'become', 'begun', 'bent', 'bet', 'bidden', 'bitten', 'bled', 'blown', 'born',
    'borne', 'bought', 'bound', 'bred', 'broadcast', 'broken', 'brought', 'built', 'burnt', 'burst',
    'cast', 'caught', 'chosen', 'clung', 'come', 'cost', 'crept', 'cut',
    'dealt', 'done', 'drawn', 'dreamt', 'driven', 'drunk', 'dug', 'dwelt',
    'eaten',
    'fallen', 'fed', 'felt', 'fled', 'flown', 'flung', 'forbidden', 'forecast', 'foregone',
    'foreseen', 'forgiven', 'forgotten', 'forsaken', 'fought', 'found', 'frozen',
    'given', 'gone', 'ground', 'grown',
    'heard', 'held', 'hewn', 'hidden', 'hit', 'hung', 'hurt',
    'kept', 'knelt', 'knit', 'known',
    'laid', 'lain', 'leant', 'leapt', 'learnt', 'led', 'left', 'lent', 'let', 'lit', 'lost',
    'made', 'meant', 'met', 'mislaid', 'misled', 'mistaken', 'misunderstood', 'mown',
    'outdone', 'outgrown', 'overcome', 'overdone', 'overheard', 'overrun', 'overseen',
    'overtaken', 'overthrown', 'overwritten',
    'paid', 'proven', 'put',
    'quit',
    'read', 'rebuilt', 'redone', 'retold', 'rewritten', 'ridden', 'risen', 'run', 'rung',
    'said', 'sat', 'sawn', 'seen', 'sent', 'set', 'sewn', 'shaken', 'shed', 'shone', 'shot',
    'shown', 'shrunk', 'shut', 'slain', 'slept', 'slid', 'slit', 'smelt', 'sold', 'sought',
    'sown', 'spat', 'sped', 'spelt', 'spent', 'spilt', 'split', 'spoilt', 'spoken', 'spread',
    'sprung', 'spun', 'stolen', 'stood', 'struck', 'strung', 'stuck', 'stung', 'stunk',
    'sung', 'sunk', 'sworn', 'swept', 'swollen', 'swum', 'swung',
    'taken', 'taught', 'thought', 'thrown', 'thrust', 'told', 'torn', 'trodden',
    'undergone', 'understood', 'undertaken', 'undone', 'unwound', 'upheld', 'upset',
    // "went" is deliberately absent: it is the past tense of go, never a
    // participle ("has gone", not "has went"), so it cannot follow an auxiliary.
    'wed', 'wept', 'withdrawn', 'withheld', 'withstood', 'woken', 'won', 'worn',
    'wound', 'woven', 'written', 'wrung',
];
