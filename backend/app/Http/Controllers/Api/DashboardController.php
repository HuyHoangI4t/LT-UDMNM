<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatLog;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function overview()
    {
        return response()->json([
            'total_questions' => ChatLog::count(),
            'today_questions' => ChatLog::whereDate('created_at', today())->count(),
            'top_intent' => ChatLog::select('intent', DB::raw('COUNT(*) as total'))
                ->groupBy('intent')
                ->orderByDesc('total')
                ->first(),
        ]);
    }

    public function topMajors()
    {
        return ChatLog::select('major_name', DB::raw('COUNT(*) as total'))
            ->whereNotNull('major_name')
            ->groupBy('major_name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();
    }

    public function questionsByIntent()
    {
        return ChatLog::select('intent', DB::raw('COUNT(*) as total'))
            ->whereNotNull('intent')
            ->groupBy('intent')
            ->orderByDesc('total')
            ->get();
    }

    public function questionsByDay()
    {
        return ChatLog::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();
    }
}