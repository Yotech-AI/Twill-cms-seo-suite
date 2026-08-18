<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            createDefaultTableFields($table);
            $table->string('title')->nullable();

            // The collision fixture: a real column sharing its name with the
            // package's own seo_title FORM field (see HandleSeoSaveTest).
            $table->string('seo_title')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
