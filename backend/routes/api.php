<?php

use App\Http\Controllers\Api\ChatbotController;
use App\Http\Controllers\Api\ChatLogController;
use App\Http\Controllers\Api\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'Hệ thống backend đang hoạt động bình thường.',
    ], 200);
});

Route::post('/chat', [ChatbotController::class, 'chat'])->middleware('throttle:30,1');
Route::get('/faq-questions', [ChatbotController::class, 'faqQuestions'])->middleware('throttle:60,1');

$adminMiddleware = filter_var(env('ADMIN_API_AUTH', false), FILTER_VALIDATE_BOOLEAN)
    ? ['auth:sanctum']
    : [];

Route::middleware($adminMiddleware)->group(function () {
    Route::get('/chat-logs', [ChatLogController::class, 'index']);
    Route::get('/chat-logs/{id}', [ChatLogController::class, 'show']);
    Route::delete('/chat-logs/{id}', [ChatLogController::class, 'destroy']);
    Route::post('/chat-logs/import', [ChatLogController::class, 'import']);

    Route::get('/dashboard/overview', [DashboardController::class, 'overview']);
    Route::get('/dashboard/top-majors', [DashboardController::class, 'topMajors']);
    Route::get('/dashboard/hot-majors', [DashboardController::class, 'hotMajors']);
    Route::get('/dashboard/questions-by-intent', [DashboardController::class, 'questionsByIntent']);
    Route::get('/dashboard/questions-by-day', [DashboardController::class, 'questionsByDay']);
    Route::get('/dashboard/questions-by-period', [DashboardController::class, 'questionsByPeriod']);
    Route::get('/dashboard/province-heatmap', [DashboardController::class, 'provinceHeatmap']);
    Route::get('/dashboard/admission-methods', [DashboardController::class, 'admissionMethods']);
    Route::get('/dashboard/platforms', [DashboardController::class, 'platforms']);
    Route::get('/dashboard/trends', [DashboardController::class, 'trends']);
    Route::get('/dashboard/realtime', [DashboardController::class, 'realtime']);
    Route::get('/dashboard/export', [DashboardController::class, 'export']);
});
