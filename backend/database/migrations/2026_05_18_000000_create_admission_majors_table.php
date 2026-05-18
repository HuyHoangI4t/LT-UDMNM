<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_majors', function (Blueprint $table) {
            $table->id();

            $table->integer('year')->nullable();
            $table->string('major_name');
            $table->string('major_code')->nullable();
            $table->json('subject_groups')->nullable();

            $table->decimal('score_thpt', 5, 2)->nullable();
            $table->decimal('score_hoc_ba', 5, 2)->nullable();
            $table->decimal('score_dgnl', 8, 2)->nullable();

            $table->integer('quota')->nullable();
            $table->string('tuition_fee')->nullable();

            $table->longText('description')->nullable();
            $table->longText('career_paths')->nullable();

            $table->string('source_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_majors');
    }
};
