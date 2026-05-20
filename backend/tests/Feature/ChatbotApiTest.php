<?php

namespace Tests\Feature;

use App\Models\FaqQuestion;
use App\Models\KnowledgeBase;
use App\Services\AiChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatbotApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_chat_endpoint_returns_reply_and_writes_log(): void
    {
        $this->app->instance(AiChatService::class, new class extends AiChatService {
            public function getAnswer(string $userMessage, array $knowledge = [], array $analysis = [], array $history = []): string
            {
                return 'Câu trả lời test';
            }
        });

        $response = $this
            ->withHeader('X-Session-ID', 'session-test-1')
            ->postJson('/api/chat', [
                'message' => 'Ngành công nghệ thông tin lấy bao nhiêu điểm?',
                'platform' => 'web',
                'history' => [
                    ['role' => 'user', 'text' => 'Xin chào'],
                    ['role' => 'ai', 'text' => 'Mình có thể giúp gì?'],
                ],
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.session_id', 'session-test-1')
            ->assertJsonPath('data.reply', 'Câu trả lời test')
            ->assertJsonPath('data.analysis.intent', 'diem_chuan')
            ->assertJsonPath('data.analysis.major', 'Công nghệ thông tin')
            ->assertJsonPath('data.agent.steps.0.step', 'intent_detection');

        $this->assertDatabaseHas('chat_logs', [
            'session_id' => 'session-test-1',
            'platform' => 'web',
            'user_query' => 'Ngành công nghệ thông tin lấy bao nhiêu điểm?',
            'bot_response' => 'Câu trả lời test',
            'intent' => 'diem_chuan',
            'major_name' => 'Công nghệ thông tin',
        ]);
    }

    public function test_chat_endpoint_validates_message(): void
    {
        $response = $this->postJson('/api/chat', [
            'platform' => 'web',
        ]);

        $response->assertUnprocessable();
    }

    public function test_chat_endpoint_returns_fallback_when_ai_service_fails(): void
    {
        $this->app->instance(AiChatService::class, new class extends AiChatService {
            public function getAnswer(string $userMessage, array $knowledge = [], array $analysis = [], array $history = []): string
            {
                throw new \Exception('AI unavailable');
            }
        });

        $response = $this->postJson('/api/chat', [
            'message' => 'Điểm chuẩn ngành Công nghệ thông tin?',
            'platform' => 'web',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.agent.steps.2.status', 'degraded');
    }

    public function test_faq_questions_are_ranked_and_limited(): void
    {
        $knowledge = KnowledgeBase::create([
            'category' => 'nganh_dao_tao',
            'source_type' => 'nganh_dao_tao',
            'title' => 'Ngành Công nghệ thông tin',
            'content' => 'Thông tin xét tuyển ngành Công nghệ thông tin.',
            'url' => 'https://example.test/cntt',
        ]);

        FaqQuestion::create([
            'question' => 'Học phí ngành Công nghệ thông tin là bao nhiêu?',
            'category' => 'hoc_phi',
            'knowledge_base_id' => $knowledge->id,
        ]);

        FaqQuestion::create([
            'question' => 'Trường có những ngành nào?',
            'category' => 'nganh_dao_tao',
            'knowledge_base_id' => $knowledge->id,
        ]);

        $response = $this->getJson('/api/faq-questions?q=hoc%20phi%20cong%20nghe%20thong%20tin&limit=1');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.question', 'Học phí ngành Công nghệ thông tin là bao nhiêu?');
    }
}
