<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('knowledge_bases', function (Blueprint $table) {
            $table->id();

            $table->string('category')->nullable();
            $table->string('title')->nullable();
            $table->string('url')->unique();

            $table->longText('content')->nullable();
            $table->json('pdf_links')->nullable();
            $table->json('image_links')->nullable();

            $table->longText('embedding')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_bases');
    }
};

