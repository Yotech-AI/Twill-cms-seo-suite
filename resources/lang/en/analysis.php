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
