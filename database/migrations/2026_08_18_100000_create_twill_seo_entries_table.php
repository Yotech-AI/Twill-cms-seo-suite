<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('twill_seo_entries', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Not $table->morphs(): morphs() gives a plain (non-unique) index,
            // but a host record can only ever have one SeoEntry — the unique
            // composite index is what makes seoEntry() a MorphOne rather than
            // a MorphMany, and what firstOrCreate() in HandleSeo relies on.
            $table->string('seoable_type');
            $table->unsignedBigInteger('seoable_id');

            $table->boolean('cornerstone')->default(false);
            $table->boolean('robots_noindex')->default(false);
            $table->boolean('robots_nofollow')->default(false);
            $table->string('schema_type_override')->nullable();

            $table->timestamps();

            $table->unique(['seoable_type', 'seoable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('twill_seo_entries');
    }
};
