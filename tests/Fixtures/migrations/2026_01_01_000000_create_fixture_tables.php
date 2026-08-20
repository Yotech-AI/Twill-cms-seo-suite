<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            createDefaultTableFields($table);
            $table->integer('position')->unsigned()->nullable();
        });

        Schema::create('article_translations', function (Blueprint $table) {
            createDefaultTranslationsTableFields($table, 'article');
            $table->string('title', 200)->nullable();
            $table->text('description')->nullable();
            // A retired hand-rolled SEO column, the shape older host sites
            // carry — deliberately NOT in the model's translatedAttributes.
            // twill-seo:migrate-legacy's tests copy it into the suite tables.
            $table->string('seo_title')->nullable();
        });

        Schema::create('article_slugs', function (Blueprint $table) {
            createDefaultSlugsTableFields($table, 'article');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_slugs');
        Schema::dropIfExists('article_translations');
        Schema::dropIfExists('articles');
    }
};
