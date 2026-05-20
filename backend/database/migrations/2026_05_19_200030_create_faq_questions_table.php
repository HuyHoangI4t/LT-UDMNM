<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faq_questions', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->string('category')->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('knowledge_base_id')->nullable();
            $table->timestamps();

            $table->unique(['question', 'category']);
            $table->index('category');
            $table->index('source_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faq_questions');
    }
};