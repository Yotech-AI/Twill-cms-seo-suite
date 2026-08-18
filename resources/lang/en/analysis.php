<?php

/*
 * Feedback shown next to each assessment result.
 *
 * House style: one sentence saying what was found, one saying what to do
 * about it. A good result gets only the first — there is nothing to fix, and a
 * second sentence would just be noise in a panel full of green bullets.
 *
 * Keys are addressed as twill-seo::analysis.<group>.<branch>, where <group> is
 * the snake_case form of the assessment identifier.
 */

return [
    'introduction_keyword' => [
        'good' => 'The keyphrase appears in the first paragraph, in one piece.',
        'spread' => 'The words of the keyphrase all appear in the first paragraph, but not together. Put the phrase itself in one sentence so the opening states the subject outright.',
        'none' => 'The keyphrase does not appear in the first paragraph. Say what the page is about in the opening lines, in the reader\'s words.',
    ],

    'keyphrase_length' => [
        'missing' => 'No keyphrase is set, so most of this analysis has nothing to measure against. Enter the phrase you want this page to be found for.',
        'good' => 'The keyphrase is :count content words long, which is the length people actually search with.',
        'slightly_long' => 'The keyphrase is :count content words long, a little over the recommended :recommendedMax. Shorter phrases match more of what people type.',
        'too_long' => 'The keyphrase is :count content words long. Nobody searches for a phrase that specific — cut it back to :recommendedMax words or so.',
    ],

    'meta_description_keyword' => [
        'missing_description' => 'There is no meta description for the keyphrase to appear in. Write one, and work the phrase into it.',
        'good' => 'The keyphrase appears :count times in the meta description, where a searcher will see it in bold.',
        'none' => 'The keyphrase does not appear in the meta description. Work it in, so the search result shows the searcher their own words.',
        'too_many' => 'The keyphrase appears :count times in the meta description. Once is enough — use the space to tell the reader why to click.',
    ],

    'meta_description_length' => [
        'missing' => 'No meta description is set. Write one of up to :max characters so search engines show your summary instead of guessing at one.',
        'too_short' => 'The meta description is :length characters, well short of the :max available. Expand it so the search result uses the whole space.',
        'good' => 'The meta description is :length characters, which fits the space a search result gives it.',
        'too_long' => 'The meta description is :length characters and will be cut off after roughly :max. Shorten it so the whole summary stays visible.',
    ],

    'text_length' => [
        'good' => 'The text is :words words long, at or above the recommended minimum of :recommended.',
        'slightly_short' => 'The text is :words words long, just under the recommended :recommended. Add a little more detail.',
        'short' => 'The text is :words words long, well under the recommended :recommended. Cover the topic in more depth.',
        'very_short' => 'The text is :words words long, far below the recommended :recommended. Write substantially more before publishing.',
        'far_too_short' => 'The text is :words words long, too little for a search engine to judge. Write at least :recommended words.',
    ],

    'keyword_density' => [
        'none' => 'The keyphrase does not appear in the text at all. Work it into the copy where it fits, up to around :recommendedMax times.',
        'under' => 'The keyphrase appears :count times, a density of :density percent. Use it a few more times, up to around :recommendedMax.',
        'good' => 'The keyphrase appears :count times, a density of :density percent, which is about right for this length.',
        'over' => 'The keyphrase appears :count times, a density of :density percent. That is more often than reads naturally — aim for around :recommendedMax uses.',
        'way_over' => 'The keyphrase appears :count times, a density of :density percent. That is far too often — rewrite most of them away, aiming for around :recommendedMax uses.',
    ],

    'text_competing_links' => [
        'good' => 'No link in the text competes with this page for the keyphrase.',
        'competing' => ':count link in the text uses the keyphrase as its anchor text. Those pages now compete with this one for the same search — reword the anchor or drop the link.',
    ],

    'image_keyphrase' => [
        'missing_input' => 'Add an image and set a keyphrase, and this check will look at whether the alt text describes it.',
        'good' => ':count of the :total images describe the keyphrase in their alt text.',
        'too_few' => 'Only :count of the :total images mention the keyphrase in their alt text. Describe a few more of them in the words the page is about.',
        'too_many' => ':count of the :total images repeat the keyphrase in their alt text. Alt text describes the picture first — vary it where the picture shows something else.',
        'none_match' => 'None of the :total images mention the keyphrase in their alt text. Where an image really does show the subject, say so.',
        'no_alts' => 'None of the :total images have alt text. Describe each one, both for search engines and for readers who cannot see it.',
    ],

    'keyphrase_in_seo_title' => [
        'missing_input' => 'Set both an SEO title and a keyphrase, and this check will look at whether the two match.',
        'good_start' => 'The SEO title opens with the keyphrase, which is where a searcher reads first.',
        'good_not_start' => 'The SEO title contains the keyphrase, though not at the front. Moving it earlier makes the result easier to scan.',
        'all_words' => 'The words of the keyphrase are all in the SEO title, but not as the phrase itself. Use the phrase as written where the wording allows.',
        'not_found' => 'The keyphrase does not appear in the SEO title. Put it in, as near the front as the wording allows.',
    ],

    'subheadings_keyword' => [
        'missing_input' => 'Set a keyphrase and write some text, and this check will look at whether your subheadings match it.',
        'none_long_text' => 'A text this long has no subheadings. Break it into sections and name them, working the keyphrase into some of those names.',
        'none_short_text' => 'The text is short enough to read without subheadings.',
        'good' => ':count of the :total subheadings contain the keyphrase, which is about the right share.',
        'too_few' => 'Only :count of the :total subheadings contain the keyphrase. Work it into a few more of them so the structure of the page reflects its subject.',
        'too_many' => ':count of the :total subheadings contain the keyphrase. Repeating it in nearly every heading reads as written for a search engine — vary the wording.',
        'none' => 'None of the :total subheadings contain the keyphrase. Work it into some of them so the structure of the page reflects its subject.',
    ],

    'images' => [
        'none' => 'The text contains no images. Add at least one relevant image or video so the page is not a wall of text.',
        'good' => 'The text is illustrated with at least one image.',
    ],

    'single_h1' => [
        'good' => 'The text uses at most one H1 heading.',
        'multiple' => 'The text contains :count H1 headings. Keep one H1 for the page title and demote the rest to H2 or lower.',
    ],

    'title_width' => [
        'missing' => 'No SEO title is set. Write one so search engines show your wording rather than picking their own.',
        'good' => 'The SEO title is around :width pixels wide and fits the :max pixels a search result shows.',
        'too_wide' => 'The SEO title is around :width pixels wide and will be cut off after :max. Shorten it so the whole title stays visible.',
    ],

    'slug_keyword' => [
        'missing_input' => 'Set a slug and a keyphrase, and this check will look at whether the URL matches the subject.',
        'good' => 'The slug contains the keyphrase, so the URL itself says what the page is about.',
        'some' => 'The slug contains :count of the :total keyphrase words. Work the rest in, as long as the URL stays readable.',
    ],

    'function_words_in_keyphrase' => [
        'only_function_words' => 'The keyphrase is made up entirely of common words, so it cannot single this page out. Add the word for what the page is actually about.',
    ],

    'previously_used_keyphrase' => [
        'missing_keyphrase' => 'No keyphrase is set, so there is nothing to compare with the rest of the site.',
        'unique' => 'No other page targets this keyphrase.',
        'used_once' => 'One other page already targets this keyphrase. Two pages after the same search get in each other\'s way — consider narrowing one of them.',
        'used_multiple' => 'This keyphrase is already used on :count other pages. They will compete with each other in the results — give each page a phrase of its own.',
    ],

    'sentence_length' => [
        'good' => ':percentage percent of the sentences are longer than :limit words, which is within the comfortable range.',
        'some_long' => ':percentage percent of the sentences are longer than :limit words. Shorten a few of them to keep the text moving.',
        'too_many_long' => ':percentage percent of the sentences are longer than :limit words. Split the longest ones so a reader can follow them in one pass.',
    ],

    'paragraph_too_long' => [
        'good' => 'The longest paragraph is :words words, within the :max that reads comfortably.',
        'slightly_long' => 'The longest paragraph is :words words, just over the :max that reads comfortably. Consider splitting it.',
        'too_long' => 'The longest paragraph is :words words, well over the :max that reads comfortably. Break it up at the turns in the argument.',
    ],

    'subheadings_too_long' => [
        'short_text' => 'The text is short enough to read without subheadings.',
        'none' => 'A text of :words words has no subheadings at all. Add them so a reader can find the part they came for.',
        'good' => 'The longest stretch between subheadings is :words words, within the :max a reader will follow.',
        'long_section' => 'The longest stretch between subheadings is :words words, just over the :max a reader will follow. Consider another subheading.',
        'too_long_section' => 'The longest stretch between subheadings is :words words, over the :max a reader will follow. Add a subheading where the subject turns.',
    ],

    'sentence_beginnings' => [
        'varied' => 'The sentences do not all start the same way.',
        'repeated' => ':count sentences in a row start with ":word". Vary the openings so the text does not read as a list.',
    ],

    'transition_words' => [
        'short_text' => 'The text is short enough to follow without signposting.',
        'good' => ':percentage percent of the sentences use a transition word, which is enough to follow the argument.',
        'some' => ':percentage percent of the sentences use a transition word. A few more would make the thread easier to follow.',
        'few' => 'Only :percentage percent of the sentences use a transition word. Signpost how one sentence follows from the last, so the text reads as an argument rather than a list.',
    ],

    'passive_voice' => [
        'good' => ':percentage percent of the sentences are in the passive voice, which is within the normal range.',
        'some' => ':percentage percent of the sentences are in the passive voice. Rewriting a few of them in the active voice would read more directly.',
        'too_many' => ':percentage percent of the sentences are in the passive voice. Say who does what in at least some of them — active sentences are shorter and easier to follow.',
    ],

    'text_presence' => [
        'too_little' => 'There is not enough text on this page to judge how it reads. Write a few paragraphs and the readability analysis will have something to work with.',
    ],

    'internal_links' => [
        'none' => 'The text links to none of your other pages. Add internal links so readers and search engines can reach related content.',
        'all_nofollow' => 'Every internal link in the text is nofollow. Remove nofollow from the links that should pass value to your own pages.',
        'some_nofollow' => ':nofollow of the :total internal links are nofollow. Check that each of those is deliberate.',
        'good' => 'The text links to your own pages, and none of those links are nofollow.',
    ],

    'external_links' => [
        'none' => 'The text links to no other sites. Link out to a source or reference where it helps the reader.',
        'all_nofollow' => 'Every external link in the text is nofollow. Remove nofollow where you do mean to endorse the source.',
        'some_nofollow' => ':nofollow of the :total external links are nofollow. Check that each of those is deliberate.',
        'good' => 'The text links out to other sites, and none of those links are nofollow.',
    ],
];
