<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ChatbotController;
use App\Http\Controllers\Api\ChatLogController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Các route ở đây sẽ tự động được Laravel thêm tiền tố "/api" ở đằng trước.
*/

// 1. API Kiểm tra trạng thái hệ thống (Health Check)
Route::get('/health', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Hệ thống Backend đang hoạt động bình thường!'
    ], 200);
});

// 2. API Dành cho Thí sinh (Chatbot)
Route::post('/chat', [ChatbotController::class, 'chat']);

// 3. API Dành cho Admin / Nhà trường (Quản lý Dashboard)
Route::get('/chat-logs', [ChatLogController::class, 'index']);
Route::get('/chat-logs/{id}', [ChatLogController::class, 'show']);
Route::delete('/chat-logs/{id}', [ChatLogController::class, 'destroy']);