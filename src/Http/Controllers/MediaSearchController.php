<?php

namespace TwillSeo\Http\Controllers;

use A17\Twill\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use TwillSeo\Support\TwillMedia;

/**
 * GET {admin}/seo/media?q=&page= — backs the settings UI's MediaPickerModal
 * (logo, default share image). Searches Twill's own `medias` table directly
 * rather than going through Twill's media-library controllers, which are
 * built around its own Vue picker UI and JSON shape, not this package's;
 * reuses TwillMedia (the one place this package knows how to turn a media id
 * into a URL) for the thumbnail rather than re-deriving one.
 */
class MediaSearchController extends Controller
{
    private const PER_PAGE = 24;

    public function __invoke(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));

        $paginator = Media::query()
            ->when($query !== '', fn ($q) => $q->where('filename', 'like', '%'.$query.'%'))
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE, ['*'], 'page', $page);

        return response()->json([
            'data' => $paginator->getCollection()->map(fn (Media $media) => [
                'id' => $media->id,
                'name' => (string) $media->filename,
                'thumbnail' => TwillMedia::fromMediaId($media->id)['url'] ?? null,
            ])->all(),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
