<?php

namespace TwillSeo\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use TwillSeo\Models\Behaviors\HasSeo;

/**
 * A registered SEO model that does NOT extend A17\Twill\Models\Model — proof
 * that HasSeo's own documented contract ("attaches Twill SEO storage to any
 * host Eloquent model, Twill module or not") actually holds for
 * SitemapBuilder too. Twill's published()/visible() local scopes live on
 * A17\Twill\Models\Model (scopePublished()/scopeVisible()), not on HasSeo or
 * on bare Eloquent — a model composing only HasSeo has neither, and
 * SitemapBuilder::eligibleQuery() must not assume otherwise just because a
 * model is SEO-registered (see SitemapTest's dedicated regression test).
 */
class PlainModel extends Model
{
    use HasSeo;

    protected $table = 'plain_models';

    protected $fillable = ['title'];
}
