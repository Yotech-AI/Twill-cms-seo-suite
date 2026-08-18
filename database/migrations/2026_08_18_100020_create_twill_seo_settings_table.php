<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Single-row table (see TwillSeo\Models\SeoSetting::current()) — no
        // foreign key, just one settings blob per install.
        Schema::create('twill_seo_settings', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->json('general')->nullable();
            $table->json('content_types')->nullable();
            $table->json('features')->nullable();
            $table->json('advanced')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('twill_seo_settings');
    }
};
