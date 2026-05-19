<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_logs', function (Blueprint $table) {
            $table->string('intent')->nullable()->after('bot_response');
            $table->string('major_name')->nullable()->after('intent');
            $table->integer('admission_year')->nullable()->after('major_name');
            $table->float('response_time')->nullable()->after('admission_year');
        });
    }

    public function down(): void
    {
        Schema::table('chat_logs', function (Blueprint $table) {
            $table->dropColumn([
                'intent',
                'major_name',
                'admission_year',
                'response_time',
            ]);
        });
    }
};