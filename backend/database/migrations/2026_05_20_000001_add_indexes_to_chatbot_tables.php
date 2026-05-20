<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_logs', function (Blueprint $table) {
            $table->index(['created_at', 'intent'], 'chat_logs_created_at_intent_index');
            $table->index(['major_name', 'created_at'], 'chat_logs_major_created_at_index');
            $table->index(['session_id', 'created_at'], 'chat_logs_session_created_at_index');
        });

        Schema::table('knowledge_bases', function (Blueprint $table) {
            $table->index(['category', 'source_type'], 'knowledge_bases_category_source_type_index');
            $table->index('published_at', 'knowledge_bases_published_at_index');
        });

        Schema::table('admission_majors', function (Blueprint $table) {
            $table->index(['year', 'major_code'], 'admission_majors_year_code_index');
            $table->index(['major_name', 'year'], 'admission_majors_name_year_index');
        });

        Schema::table('faq_questions', function (Blueprint $table) {
            $table->index('knowledge_base_id', 'faq_questions_knowledge_base_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('faq_questions', function (Blueprint $table) {
            $table->dropIndex('faq_questions_knowledge_base_id_index');
        });

        Schema::table('admission_majors', function (Blueprint $table) {
            $table->dropIndex('admission_majors_year_code_index');
            $table->dropIndex('admission_majors_name_year_index');
        });

        Schema::table('knowledge_bases', function (Blueprint $table) {
            $table->dropIndex('knowledge_bases_category_source_type_index');
            $table->dropIndex('knowledge_bases_published_at_index');
        });

        Schema::table('chat_logs', function (Blueprint $table) {
            $table->dropIndex('chat_logs_created_at_intent_index');
            $table->dropIndex('chat_logs_major_created_at_index');
            $table->dropIndex('chat_logs_session_created_at_index');
        });
    }
};
