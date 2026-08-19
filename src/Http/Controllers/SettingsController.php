<?php

namespace TwillSeo\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use TwillSeo\Http\Requests\SettingsUpdateRequest;
use TwillSeo\Models\SeoSetting;
use TwillSeo\Services\Settings\SeoSettings;
use TwillSeo\Services\Settings\SettingsPayload;

/**
 * GET/PUT {admin}/seo/settings — the settings admin UI's JSON API. Both
 * actions return the exact same shape (see SettingsPayload), so a successful
 * PUT can replace the client's local state with the fresh, saved values
 * directly rather than needing a follow-up GET.
 */
class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingsPayload $payload,
        private readonly SeoSettings $settings,
    ) {}

    public function show(): JsonResponse
    {
        return response()->json($this->payload->build());
    }

    /**
     * Each of the four sections present in the request wholesale-replaces
     * that section of the stored row (see SettingsUpdateRequest's own doc
     * comment on why a partial per-key merge is not needed); any section
     * absent from the request is left untouched.
     */
    public function update(SettingsUpdateRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $row = SeoSetting::current();

        foreach (['general', 'content_types', 'features', 'advanced'] as $section) {
            if (array_key_exists($section, $validated)) {
                $row->{$section} = $validated[$section];
            }
        }

        if ($row->isDirty()) {
            $row->save();
        }

        // Must run even when nothing was dirty (an empty-but-valid PUT is a
        // legal no-op call) — cheap, and keeps this endpoint's behavior
        // uniform rather than conditional on whether anything changed.
        $this->settings->refresh();

        return response()->json($this->payload->build());
    }
}
