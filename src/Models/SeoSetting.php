<?php

namespace TwillSeo\Models;

use TwillSeo\Twill\Capsules\SeoSettings\Models\SeoSetting as CapsuleSeoSetting;

/**
 * Backwards-compatible name: the settings model moved into the SeoSettings
 * Twill capsule when the settings screen became a native singleton module.
 * Same table, same columns, same current() — existing imports (the
 * SeoSettings accessor's callers, host code, tests) keep working unchanged.
 */
class SeoSetting extends CapsuleSeoSetting {}
