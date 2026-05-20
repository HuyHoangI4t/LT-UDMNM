<?php

namespace Tests\Feature;

use App\Models\ChatLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_overview_counts_questions_and_top_intent(): void
    {
        ChatLog::create([
            'session_id' => 's1',
            'platform' => 'web',
            'user_query' => 'Điểm chuẩn CNTT?',
            'bot_response' => 'Test',
            'intent' => 'diem_chuan',
            'major_name' => 'Công nghệ thông tin',
            'admission_method' => 'thpt',
            'province' => 'Đắk Lắk',
            'response_time' => 0.5,
        ]);

        ChatLog::create([
            'session_id' => 's2',
            'platform' => 'web',
            'user_query' => 'Học phí?',
            'bot_response' => 'Test',
            'intent' => 'hoc_phi',
            'response_time' => 0.7,
        ]);

        ChatLog::create([
            'session_id' => 's3',
            'platform' => 'web',
            'user_query' => 'Điểm chuẩn Y khoa?',
            'bot_response' => 'Test',
            'intent' => 'diem_chuan',
            'major_name' => 'Y khoa',
            'response_time' => 0.6,
        ]);

        $response = $this->getJson('/api/dashboard/overview');

        $response
            ->assertOk()
            ->assertJsonPath('total_questions', 3)
            ->assertJsonPath('today_questions', 3)
            ->assertJsonPath('top_intent.intent', 'diem_chuan')
            ->assertJsonPath('top_intent.total', 2);
    }

    public function test_chat_logs_index_supports_per_page_limit(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            ChatLog::create([
                'session_id' => 's' . $i,
                'platform' => 'web',
                'user_query' => 'Question ' . $i,
                'bot_response' => 'Answer ' . $i,
            ]);
        }

        $response = $this->getJson('/api/chat-logs?per_page=5');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.per_page', 5)
            ->assertJsonPath('data.total', 12);
    }

    public function test_dashboard_overview_supports_date_range(): void
    {
        ChatLog::create([
            'session_id' => 'old',
            'platform' => 'web',
            'user_query' => 'Old question',
            'bot_response' => 'Old answer',
            'intent' => 'hoc_phi',
        ])->forceFill([
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ])->save();

        ChatLog::create([
            'session_id' => 'new',
            'platform' => 'web',
            'user_query' => 'New question',
            'bot_response' => 'New answer',
            'intent' => 'diem_chuan',
        ])->forceFill([
            'created_at' => now(),
            'updated_at' => now(),
        ])->save();

        $response = $this->getJson('/api/dashboard/overview?from=' . now()->subDay()->toDateString() . '&to=' . now()->toDateString());

        $response
            ->assertOk()
            ->assertJsonPath('total_questions', 1)
            ->assertJsonPath('top_intent.intent', 'diem_chuan');
    }

    public function test_dashboard_extended_analytics(): void
    {
        ChatLog::create([
            'session_id' => 's1',
            'platform' => 'web',
            'user_query' => 'Điểm chuẩn CNTT theo THPT ở Đắk Lắk?',
            'bot_response' => 'Test',
            'intent' => 'diem_chuan',
            'major_name' => 'Công nghệ thông tin',
            'admission_method' => 'thpt',
            'province' => 'Đắk Lắk',
        ]);

        $this->getJson('/api/dashboard/admission-methods')
            ->assertOk()
            ->assertJsonPath('0.admission_method', 'thpt')
            ->assertJsonPath('0.total', 1);

        $this->getJson('/api/dashboard/province-heatmap')
            ->assertOk()
            ->assertJsonPath('0.province', 'Đắk Lắk')
            ->assertJsonPath('0.total', 1);

        $this->getJson('/api/dashboard/platforms')
            ->assertOk()
            ->assertJsonPath('0.platform', 'web')
            ->assertJsonPath('0.total', 1);
    }
}
