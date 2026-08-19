<?php

namespace TwillSeo\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use TwillSeo\Models\Behaviors\HasSeo;

/**
 * A doctor-only negative fixture (see DoctorCommandTest): declares
 * $translatedAttributes as a TYPED property with no default, which PHP
 * leaves uninitialized until first assigned. Reading it from outside the
 * class — exactly what DoctorCommand::checkTranslatedAttributes() does —
 * throws `Error: Typed property ... must not be accessed before
 * initialization` (verified empirically; a merely protected/private
 * untyped property does NOT throw the same way, since Eloquent's own
 * __get() intercepts that case and returns null instead). Proves the
 * check degrades to a WARN rather than crashing the whole doctor run.
 *
 * Extends bare Eloquent Model, not A17\Twill\Models\Model — Twill's own
 * base class already declares $translatedAttributes itself (untyped), and
 * PHP does not allow a child class to redeclare an inherited property with
 * a narrower (typed) declaration; PlainModel's own doc comment establishes
 * the same bare-Eloquent pattern as a legitimate HasSeo host.
 *
 * Reuses the `articles` table for the same reason
 * BrokenTranslatedAttributesArticle does — see its own doc comment.
 */
class UninitializedTranslatedAttributesArticle extends Model
{
    use HasSeo;

    protected $table = 'articles';

    public array $translatedAttributes;
}
