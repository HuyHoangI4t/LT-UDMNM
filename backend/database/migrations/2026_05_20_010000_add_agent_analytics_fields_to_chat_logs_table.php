<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_logs', function (Blueprint $table) {
            $table->string('admission_method')->nullable()->after('admission_year');
            $table->decimal('score', 5, 2)->nullable()->after('admission_method');
            $table->string('province')->nullable()->after('score');
            $table->json('entities')->nullable()->after('province');
            $table->json('agent_steps')->nullable()->after('entities');
            $table->json('retrieval_summary')->nullable()->after('agent_steps');

            $table->index(['admission_method', 'created_at'], 'chat_logs_method_created_at_index');
            $table->index(['province', 'created_at'], 'chat_logs_province_created_at_index');
            $table->index(['platform', 'created_at'], 'chat_logs_platform_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('chat_logs', function (Blueprint $table) {
            $table->dropIndex('chat_logs_method_created_at_index');
            $table->dropIndex('chat_logs_province_created_at_index');
            $table->dropIndex('chat_logs_platform_created_at_index');

            $table->dropColumn([
                'admission_method',
                'score',
                'province',
                'entities',
                'agent_steps',
                'retrieval_summary',
            ]);
        });
    }
};
