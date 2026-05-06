<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatLog;
use Illuminate\Http\Request;
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
    public function index()
    {
        // Lấy danh sách logs, sắp xếp mới nhất lên đầu, phân trang 10 dòng/trang
        $logs = ChatLog::orderBy('created_at', 'desc')->paginate(10);
        
        return response()->json([
            'status' => 'success',
            'data' => $logs
        ]);
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
    public function show($id)
    {
        $log = ChatLog::find($id);
        
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
    public function destroy($id)
    {
        $log = ChatLog::find($id);
        
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
}