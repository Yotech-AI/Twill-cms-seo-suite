<?php

namespace TwillSeo\Support;

use A17\Twill\Models\Media;
use A17\Twill\Services\MediaLibrary\ImageService;
use Throwable;

/**
 * The one point of contact with Twill's media/image service — investigated
 * once in vendor/area17/twill (READ-ONLY reference), wrapped here so nothing
 * else in this package needs to know how Twill turns a media role or a raw
 * media id into a URL. Two call sites need it (SeoResolver's OG image
 * cascade — HasSeo role, registry image_role, settings default share media —
 * and OrganizationPiece/PersonPiece's settings-driven entity logo), so it is
 * a small shared class rather than one private method duplicated in two
 * places touching the same vendor internals.
 *
 * What was found in vendor/area17/twill/src/Models/Behaviors/HasMedias.php:
 *  - imageAsArray($role, $crop) already returns exactly {src, width, height,
 *    alt, caption, video} for a role/crop attached to a model — width/height
 *    fall back to the media's own natural dimensions when no crop pivot
 *    narrows them (`$media->pivot->crop_w ?? $media->width`), which is
 *    exactly the {url, width, height} shape this package's image tags need.
 *  - Both imageAsArray() and a raw ImageService::getUrlWithCrop() call
 *    bottom out in the same place: the configured ImageServiceInterface
 *    (Glide by default — vendor/area17/twill/src/Services/MediaLibrary/
 *    Glide.php), whose getUrl()/getUrlWithCrop() only build a URL string via
 *    League\Glide\Urls\UrlBuilder — no filesystem or network I/O at call
 *    time (storage is only ever touched for a handful of special-cased
 *    source extensions, e.g. '.svg', which media uploaded through Twill's
 *    own library will not be for a photo/OG-image use case).
 *  - That service is bound under the container key 'imageService' only when
 *    config('twill.enabled.media-library') is true (default true) — a host
 *    that disables the media library entirely has nothing bound there, and
 *    resolving the ImageService facade would throw.
 *
 * Both methods here degrade to null (never throw) when a URL cannot be
 * produced — a disabled media-library feature, a model without HasMedias, a
 * media id that does not exist, or anything else going wrong — so a caller
 * always has a clean "omit the image tag" signal instead of a broken page.
 */
final class TwillMedia
{
    /**
     * @return ?array{url: string, width: int, height: int}
     */
    public static function fromRole(object $model, string $role, string $crop = 'default'): ?array
    {
        if (! method_exists($model, 'imageAsArray')) {
            return null;
        }

        try {
            $image = $model->imageAsArray($role, $crop);
        } catch (Throwable $e) {
            report($e);

            return null;
        }

        if (empty($image['src'])) {
            return null;
        }

        return [
            'url' => (string) $image['src'],
            'width' => (int) ($image['width'] ?? 0),
            'height' => (int) ($image['height'] ?? 0),
        ];
    }

    /**
     * No $crop parameter (unlike fromRole()): a raw media id from a settings
     * picker has no mediable pivot row to carry a named crop, so there is
     * nothing here for a crop name to select between — always the media's
     * own natural dimensions.
     *
     * @return ?array{url: string, width: int, height: int}
     */
    public static function fromMediaId(int|string|null $mediaId): ?array
    {
        if ($mediaId === null || $mediaId === '') {
            return null;
        }

        try {
            $media = Media::query()->find($mediaId);

            if ($media === null) {
                return null;
            }

            $url = ImageService::getUrlWithCrop((string) $media->uuid, []);
        } catch (Throwable $e) {
            report($e);

            return null;
        }

        return [
            'url' => (string) $url,
            'width' => (int) $media->width,
            'height' => (int) $media->height,
        ];
    }
}
