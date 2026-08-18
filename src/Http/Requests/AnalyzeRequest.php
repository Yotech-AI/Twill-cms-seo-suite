<?php

namespace TwillSeo\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use TwillSeo\Services\ModelRegistry;

/**
 * Validates a POST /seo/analyze call. `type` must be a registry key, never a
 * class name — see ModelRegistry's own note on why the endpoint accepts no
 * other shape for it. Route middleware (twill_auth) already gates who may
 * call this at all, so authorize() has nothing further to check.
 */
class AnalyzeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', function (string $attribute, mixed $value, Closure $fail): void {
                if (! app(ModelRegistry::class)->has((string) $value)) {
                    $fail('Unknown content type.');
                }
            }],
            'id' => ['required', 'integer'],
            'locale' => ['required', 'string', 'max:10'],
            'fields' => ['sometimes', 'array'],
            'fields.title' => ['nullable', 'string'],
            'fields.seo_title' => ['nullable', 'string'],
            'fields.seo_description' => ['nullable', 'string'],
            'fields.keyphrase' => ['nullable', 'string'],
            'fields.slug' => ['nullable', 'string'],
            'fields.content_override' => ['nullable', 'string'],
        ];
    }
}
