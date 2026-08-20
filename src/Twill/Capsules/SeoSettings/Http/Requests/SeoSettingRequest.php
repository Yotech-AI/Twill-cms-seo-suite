<?php

namespace TwillSeo\Twill\Capsules\SeoSettings\Http\Requests;

use A17\Twill\Http\Requests\Admin\Request;

/**
 * Twill resolves this class BY NAME on every singleton update
 * (ModuleController::validateFormRequest() -> App::make("...Http\Requests\
 * SeoSettingRequest")) — without it the first Update press throws a
 * BindingResolutionException before any repository code runs. No rules on
 * purpose: every settings field is optional, and the repository's
 * writeSettings() already trims, splits and casts each value (empty ones
 * fall back to config/registry defaults rather than being invalid).
 */
class SeoSettingRequest extends Request
{
    public function rulesForCreate()
    {
        return [];
    }

    public function rulesForUpdate()
    {
        return [];
    }
}
