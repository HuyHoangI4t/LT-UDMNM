<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA; // THÊM KHAI BÁO ATTRIBUTES

class ChatLogController extends Controller
{
    #[OA\Get(
        path: '/api/chat-logs',
        summary: 'Lấy danh sách lịch sử hội thoại (dành cho Dashboard)',
        tags: ['Quản lý Dashboard']
    )]
    #[OA\Response(
        response: 200,
        description: 'Thành công',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(
                    property: 'data',
                    description: 'Đối tượng phân trang của Laravel',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'current_page', type: 'integer', example: 1),
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'first_page_url', type: 'string'),
                        new OA\Property(property: 'last_page', type: 'integer', example: 5),
                        new OA\Property(property: 'total', type: 'integer', example: 45)
                    ]
                )
            ]
        )
    )]
    public function index(Request $request)
    {
        $perPage = min(max((int) $request->query('per_page', 10), 1), 100);

        try {
            $logs = ChatLog::orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'data' => $logs
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'current_page' => 1,
                    'data' => [],
                    'first_page_url' => null,
                    'last_page' => 1,
                    'total' => 0,
                    'prev_page_url' => null,
                    'next_page_url' => null,
                ],
            ], 200);
        }
    }

    #[OA\Get(
        path: '/api/chat-logs/{id}',
        summary: 'Xem chi tiết một phiên chat',
        tags: ['Quản lý Dashboard']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        description: 'ID của dòng lịch sử chat',
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Thành công',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'data', type: 'object')
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Không tìm thấy',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'error'),
                new OA\Property(property: 'message', type: 'string', example: 'Không tìm thấy bản ghi này')
            ]
        )
    )]
    public function show(int|string $id)
    {
        try {
            $log = ChatLog::find($id);
        } catch (\Throwable $e) {
            $log = null;
        }
        
        if (!$log) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Không tìm thấy bản ghi này'
            ], 404);
        }
        
        return response()->json([
            'status' => 'success', 
            'data' => $log
        ]);
    }

    #[OA\Delete(
        path: '/api/chat-logs/{id}',
        summary: 'Xóa một dòng lịch sử chat',
        tags: ['Quản lý Dashboard']
    )]
    #[OA\Parameter(
        name: 'id',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Xóa thành công',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'message', type: 'string', example: 'Đã xóa thành công')
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Không tìm thấy',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'error'),
                new OA\Property(property: 'message', type: 'string', example: 'Không tìm thấy bản ghi này')
            ]
        )
    )]
    public function destroy(int|string $id)
    {
        try {
            $log = ChatLog::find($id);
        } catch (\Throwable $e) {
            $log = null;
        }
        
        if (!$log) {
            return response()->json([
                'status' => 'error', 
                'message' => 'Không tìm thấy bản ghi này'
            ], 404);
        }
        
        $log->delete();
        
        return response()->json([
            'status' => 'success', 
            'message' => 'Đã xóa thành công'
        ]);
    }

    // Bulk import chat logs for training/initial data
    public function import(Request $request)
    {
        $data = $request->input('data');

        if (!is_array($data)) {
            return response()->json(['status' => 'error', 'message' => 'Invalid payload, expect data array under "data" key'], 400);
        }

        $created = 0;
        try {
            DB::beginTransaction();
            foreach ($data as $item) {
                $session_id = $item['session_id'] ?? Str::uuid()->toString();
                $platform = $item['platform'] ?? 'web';
                $user_query = $item['user_query'] ?? ($item['question'] ?? null);
                $bot_response = $item['bot_response'] ?? ($item['answer'] ?? null);
                $created_at = $item['created_at'] ?? now();

                if (!$user_query || !$bot_response) {
                    continue; // skip incomplete rows
                }

                ChatLog::create([
                    'session_id' => $session_id,
                    'platform' => $platform,
                    'user_query' => $user_query,
                    'bot_response' => $bot_response,
                    'created_at' => $created_at,
                    'updated_at' => $created_at,
                ]);

                $created++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }

        return response()->json(['status' => 'success', 'created' => $created]);
    }
}
