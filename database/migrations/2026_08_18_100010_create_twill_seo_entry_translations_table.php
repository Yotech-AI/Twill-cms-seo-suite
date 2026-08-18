<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('twill_seo_entry_translations', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->foreignId('twill_seo_entry_id')
                ->constrained('twill_seo_entries')
                ->cascadeOnDelete();

            $table->string('locale', 7)->index();

            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('focus_keyphrase')->nullable()->index();
            $table->string('canonical_url')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('twitter_title')->nullable();
            $table->text('twitter_description')->nullable();

            // Populated by the analysis engine (a later task) — nullable
            // because a translation can exist here before it's ever analyzed.
            $table->unsignedTinyInteger('seo_score')->nullable();
            $table->unsignedTinyInteger('readability_score')->nullable();
            $table->json('analysis_summary')->nullable();
            $table->timestamp('analyzed_at')->nullable();

            $table->timestamps();

            $table->unique(['twill_seo_entry_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('twill_seo_entry_translations');
    }
};
