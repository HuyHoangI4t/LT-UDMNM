<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\AiChatService;
use App\Models\ChatLog;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA; // <--- SỬA THÀNH ATTRIBUTES
use App\Models\KnowledgeBase;
use Exception;

class ChatbotController extends Controller
{

    protected AiChatService $aiChatService;

    public function __construct(AiChatService $aiChatService)
    {
        $this->aiChatService = $aiChatService;
    }

    #[OA\Post(
        path: '/api/chat',
        summary: 'Gửi câu hỏi cho Chatbot AI',
        tags: ['Chatbot']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['message'],
            properties: [
                new OA\Property(property: 'message', description: 'Nội dung câu hỏi của thí sinh', type: 'string', example: 'Ngành CNTT lấy bao nhiêu điểm?'),
                new OA\Property(property: 'platform', description: 'Nền tảng gửi (web, zalo, facebook)', type: 'string', example: 'web')
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Thành công',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'session_id', type: 'string', example: '550e8400-e29b-41d4-a716-446655440000'),
                        new OA\Property(property: 'reply', type: 'string', example: 'Chào bạn, ngành CNTT năm nay dự kiến lấy 23.5 điểm...')
                    ]
                )
            ]
        )
    )]
    #[OA\Response(response: 422, description: 'Lỗi xác thực dữ liệu (Validation)')]
    #[OA\Response(response: 500, description: 'Lỗi hệ thống hoặc lỗi AI')]
    public function chat(Request $request)
    {
        // 1. Validate dữ liệu đầu vào
        $request->validate([
            'message' => 'required|string|max:1000',
            'platform' => 'nullable|string'
        ]);

        $userMessage = $request->input('message');
        $platform = $request->input('platform', 'web');
        $sessionId = $request->header('X-Session-ID', Str::uuid()->toString());

        try {
            // 2. Gọi Service xử lý AI (Ollama / Gemini)
            $botReply = $this->aiChatService->getAnswer($userMessage);

            // 3. Lưu log vào Database (phục vụ Dashboard)
            try {
                ChatLog::create([
                    'session_id' => $sessionId,
                    'platform' => $platform,
                    'user_query' => $userMessage,
                    'bot_response' => $botReply,
                ]);
            } catch (\Throwable $logException) {
                // Bỏ qua lỗi lưu log để chatbot vẫn trả lời được khi DB chưa sẵn sàng.
            }

            // 4. Trả về kết quả JSON cho Frontend
            return response()->json([
                'status' => 'success',
                'data' => [
                    'session_id' => $sessionId,
                    'reply' => $botReply
                ]
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'success',
                'data' => [],
            ], 200);
        }
    }


    /**
     * Trả về danh sách câu hỏi thường gặp (FAQ) từ bảng knowledge_bases.
     */
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