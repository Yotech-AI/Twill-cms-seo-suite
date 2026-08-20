<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use TwillSeo\Twill\Capsules\SeoSettings\Models\SeoSetting;

/**
 * Upgrades the single-row settings table into a Twill singleton module's
 * backing table (published flag + soft deletes), and moves the two media-id
 * JSON keys onto real Twill media roles so the settings form can use the
 * native media library. The four JSON columns stay exactly as they are —
 * every SeoSettings accessor keeps reading the same storage.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('twill_seo_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('twill_seo_settings', 'published')) {
                $table->boolean('published')->default(true);
            }

            if (! Schema::hasColumn('twill_seo_settings', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        $this->promoteMediaIdsToRoles();
    }

    public function down(): void
    {
        Schema::table('twill_seo_settings', function (Blueprint $table): void {
            $table->dropColumn(['published', 'deleted_at']);
        });
    }

    /**
     * Existing installs picked the logo/default share image through the old
     * custom picker, which stored bare media ids in the general JSON blob.
     * Attach those medias to the singleton row under the new roles; the ids
     * stay in the JSON as a read-fallback for anything not yet migrated.
     */
    private function promoteMediaIdsToRoles(): void
    {
        $mediablesTable = config('twill.mediables_table', 'twill_mediables');

        if (! Schema::hasTable($mediablesTable)) {
            return;
        }

        $row = DB::table('twill_seo_settings')->find(1);

        if ($row === null) {
            return;
        }

        $general = (array) json_decode($row->general ?? '[]', true);

        foreach (['logo_media_id' => 'logo', 'default_share_media_id' => 'default_share'] as $jsonKey => $role) {
            $mediaId = $general[$jsonKey] ?? null;

            if ($mediaId === null || $mediaId === '') {
                continue;
            }

            $exists = DB::table($mediablesTable)
                ->where('mediable_type', SeoSetting::class)
                ->where('mediable_id', $row->id)
                ->where('role', $role)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table($mediablesTable)->insert([
                'media_id' => (int) $mediaId,
                'mediable_id' => $row->id,
                'mediable_type' => SeoSetting::class,
                'role' => $role,
                'crop' => 'default',
                'metadatas' => '{}',
                'locale' => (string) config('twill.locale', 'en'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
};
