<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Backs TwillSeo\Tests\Fixtures\Models\PlainModel — deliberately NOT built
 * via createDefaultTableFields() (no `published`, no soft deletes, no
 * publish_start_date/publish_end_date): this fixture exists specifically to
 * prove SitemapBuilder tolerates a registered model with none of Twill's
 * publish/visibility columns at all (see SitemapTest's regression test for
 * the crash this used to cause).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plain_models', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plain_models');
    }
};
