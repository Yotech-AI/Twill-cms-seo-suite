<?php

namespace TwillSeo\Analysis\Score;

/**
 * The traffic light for a whole section. Separate from the per-result Rating
 * because a section can also be grey: NotAvailable means the section produced
 * nothing to judge, which is not the same as judging it badly.
 */
enum OverallRating: string
{
    case NotAvailable = 'not-available';
    case Bad = 'bad';
    case Ok = 'ok';
    case Good = 'good';
}
