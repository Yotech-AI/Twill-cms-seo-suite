<?php

namespace TwillSeo\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Install-wide SEO settings (a single row, id 1). Only the migration and this
 * model land in this task — the accessor service that applies these on top
 * of config('twill-seo') defaults, and the settings admin UI, are later work.
 *
 * @property array|null $general
 * @property array|null $content_types
 * @property array|null $features
 * @property array|null $advanced
 */
class SeoSetting extends Model
{
    protected $table = 'twill_seo_settings';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'general' => 'array',
            'content_types' => 'array',
            'features' => 'array',
            'advanced' => 'array',
        ];
    }

    /**
     * The single settings row, created on first access. No secrets live on
     * this model (unlike e.g. the AI package's TwillAiSetting), so none of
     * the above need an encrypted cast.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1]);
    }
}
