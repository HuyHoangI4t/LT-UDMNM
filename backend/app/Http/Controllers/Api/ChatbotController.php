<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatLog;
use App\Models\KnowledgeBase;
use App\Services\AiChatService;
use App\Services\KnowledgeSearchService;
use App\Services\QuestionAnalyzerService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class ChatbotController extends Controller
{
    protected AiChatService $aiChatService;
    protected QuestionAnalyzerService $questionAnalyzerService;
    protected KnowledgeSearchService $knowledgeSearchService;

    public function __construct(
        AiChatService $aiChatService,
        QuestionAnalyzerService $questionAnalyzerService,
        KnowledgeSearchService $knowledgeSearchService
    ) {
        $this->aiChatService = $aiChatService;
        $this->questionAnalyzerService = $questionAnalyzerService;
        $this->knowledgeSearchService = $knowledgeSearchService;
    }

    #[OA\Post(
        path: '/api/chat',
        summary: 'Gửi câu hỏi cho Chatbot AI',
        tags: ['Chatbot']
    )]
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'platform' => 'nullable|string'
        ]);

        $startTime = microtime(true);

        $userMessage = trim($request->input('message'));
        $platform = $request->input('platform', 'web');
        $sessionId = $request->header('X-Session-ID', Str::uuid()->toString());

        try {
            $analysis = $this->questionAnalyzerService->analyze($userMessage);

            $knowledge = $this->knowledgeSearchService->searchSmart(
                $userMessage,
                $analysis,
                6
            );

            $botReply = $this->aiChatService->getAnswer(
                $userMessage,
                $knowledge,
                $analysis
            );

            $responseTime = round(microtime(true) - $startTime, 3);

            try {
                ChatLog::create([
                    'session_id' => $sessionId,
                    'platform' => $platform,
                    'user_query' => $userMessage,
                    'bot_response' => $botReply,
                    'intent' => $analysis['intent'] ?? null,
                    'major_name' => $analysis['major'] ?? null,
                    'admission_year' => $analysis['year'] ?? null,
                    'response_time' => $responseTime,
                ]);
            } catch (\Throwable $logException) {
                // Không để lỗi log làm chết chatbot.
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'session_id' => $sessionId,
                    'reply' => $botReply,
                    'analysis' => $analysis,
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Chatbot đang gặp lỗi: ' . $e->getMessage(),
                'data' => [
                    'session_id' => $sessionId,
                    'reply' => 'Xin lỗi, hiện tại hệ thống chưa xử lý được câu hỏi này. Bạn thử hỏi lại rõ hơn giúp mình nhé.'
                ]
            ], 200);
        }
    }

    public function faqQuestions(): \Illuminate\Http\JsonResponse
    {
        try {
            $items = KnowledgeBase::query()
                ->whereIn('category', ['faq', 'faqs', 'cau_hoi'])
                ->orderBy('id', 'desc')
                ->limit(50)
                ->get(['title', 'content']);

            $data = $items->map(function ($it) {
                return [
                    'title' => $it->title,
                    'content' => $it->content,
                ];
            })->values();

            return response()->json([
                'status' => 'success',
                'data' => $data,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Lỗi khi lấy FAQ: ' . $e->getMessage(),
            ], 500);
        }
    }
}