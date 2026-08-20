<?php

namespace TwillSeo\Twill\Capsules\SeoSettings\Database\Seeds;

use Illuminate\Database\Seeder;
use TwillSeo\Twill\Capsules\SeoSettings\Models\SeoSetting;

/**
 * Twill runs this on the singleton's first visit when no row exists yet.
 * current() creates the canonical id-1 row — existing installs (which have
 * carried that row since the storage migration) are left untouched.
 */
class SeoSettingSeeder extends Seeder
{
    public function run(): void
    {
        if (SeoSetting::withoutGlobalScopes()->count() > 0) {
            return;
        }

        SeoSetting::current()->forceFill(['published' => true])->save();
    }
}
