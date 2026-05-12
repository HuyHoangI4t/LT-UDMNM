<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up()
{
    Schema::create('admissions', function (Blueprint $table) {
        $table->id();
        $table->string('name');      // Phải có dòng này
        $table->string('code');      // Phải có dòng này
        $table->string('group');     // Phải có dòng này
        $table->integer('quota');    // Phải có dòng này
        $table->string('tuition');   // Phải có dòng này
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admissions');
    }
};
