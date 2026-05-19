<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_bases', function (Blueprint $table) {
            $table->id();

            $table->string('category')->nullable();

            $table->string('source_type')->nullable();

            $table->string('title')->nullable();

            $table->text('url')->nullable();

            $table->longText('content')->nullable();

            $table->json('pdf_links')->nullable();

            $table->json('image_links')->nullable();

            $table->timestamp('published_at')->nullable();

            $table->longText('embedding')->nullable();

            $table->timestamps();

            $table->index('category');
            $table->index('source_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_bases');
    }
};