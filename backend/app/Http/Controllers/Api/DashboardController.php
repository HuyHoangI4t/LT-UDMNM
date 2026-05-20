<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class DashboardController extends Controller
{
    #[OA\Get(
        path: '/api/dashboard/overview',
        summary: 'Dashboard overview',
        tags: ['Dashboard']
    )]
    public function overview(Request $request)
    {
        $baseQuery = $this->applyDateRange(ChatLog::query(), $request);

        return response()->json([
            'total_questions' => (clone $baseQuery)->count(),
            'today_questions' => (clone $baseQuery)->whereDate('created_at', today())->count(),
            'active_sessions' => (clone $baseQuery)->distinct('session_id')->count('session_id'),
            'average_response_time' => round((float) (clone $baseQuery)->avg('response_time'), 3),
            'top_intent' => (clone $baseQuery)->select('intent', DB::raw('COUNT(*) as total'))
                ->whereNotNull('intent')
                ->groupBy('intent')
                ->orderByDesc('total')
                ->first(),
            'updated_at' => now()->toISOString(),
        ]);
    }

    #[OA\Get(
        path: '/api/dashboard/top-majors',
        summary: 'Top majors',
        tags: ['Dashboard']
    )]
    public function topMajors(Request $request)
    {
        return $this->applyDateRange(ChatLog::query(), $request)
            ->select('major_name', DB::raw('COUNT(*) as total'))
            ->whereNotNull('major_name')
            ->groupBy('major_name')
            ->orderByDesc('total')
            ->limit((int) $request->query('limit', 10))
            ->get();
    }

    public function hotMajors(Request $request)
    {
        return $this->applyDateRange(ChatLog::query(), $request)
            ->select(
                'major_name',
                DB::raw('COUNT(*) as total'),
                DB::raw('COUNT(DISTINCT session_id) as sessions'),
                DB::raw("SUM(CASE WHEN intent = 'diem_chuan' THEN 1 ELSE 0 END) as score_interest"),
                DB::raw("SUM(CASE WHEN intent = 'tu_van_nganh' THEN 1 ELSE 0 END) as consulting_interest")
            )
            ->whereNotNull('major_name')
            ->groupBy('major_name')
            ->orderByDesc('total')
            ->limit((int) $request->query('limit', 10))
            ->get();
    }

    #[OA\Get(
        path: '/api/dashboard/questions-by-intent',
        summary: 'Questions by intent',
        tags: ['Dashboard']
    )]
    public function questionsByIntent(Request $request)
    {
        return $this->applyDateRange(ChatLog::query(), $request)
            ->select('intent', DB::raw('COUNT(*) as total'))
            ->whereNotNull('intent')
            ->groupBy('intent')
            ->orderByDesc('total')
            ->get();
    }

    #[OA\Get(
        path: '/api/dashboard/questions-by-day',
        summary: 'Questions by day',
        tags: ['Dashboard']
    )]
    public function questionsByDay(Request $request)
    {
        return $this->questionsByPeriod($request->merge(['period' => 'day']));
    }

    public function questionsByPeriod(Request $request)
    {
        $period = $request->query('period', 'day');
        $driver = DB::connection()->getDriverName();
        $dateExpression = $this->dateBucketExpression($period, $driver);

        return $this->applyDateRange(ChatLog::query(), $request)
            ->select(DB::raw("{$dateExpression} as period"), DB::raw('COUNT(*) as total'))
            ->groupBy('period')
            ->orderBy('period')
            ->get();
    }

    public function provinceHeatmap(Request $request)
    {
        return $this->applyDateRange(ChatLog::query(), $request)
            ->select('province', DB::raw('COUNT(*) as total'), DB::raw('COUNT(DISTINCT session_id) as sessions'))
            ->whereNotNull('province')
            ->groupBy('province')
            ->orderByDesc('total')
            ->get();
    }

    public function admissionMethods(Request $request)
    {
        return $this->applyDateRange(ChatLog::query(), $request)
            ->select('admission_method', DB::raw('COUNT(*) as total'))
            ->whereNotNull('admission_method')
            ->groupBy('admission_method')
            ->orderByDesc('total')
            ->get();
    }

    public function platforms(Request $request)
    {
        return $this->applyDateRange(ChatLog::query(), $request)
            ->select('platform', DB::raw('COUNT(*) as total'), DB::raw('COUNT(DISTINCT session_id) as sessions'))
            ->groupBy('platform')
            ->orderByDesc('total')
            ->get();
    }

    public function trends(Request $request)
    {
        $from = $request->query('from', now()->subDays(30)->toDateString());
        $to = $request->query('to', now()->toDateString());
        $scopedRequest = $request->merge(['from' => $from, 'to' => $to]);

        return response()->json([
            'period' => ['from' => $from, 'to' => $to],
            'questions_by_day' => $this->questionsByPeriod($scopedRequest)->toArray(),
            'hot_majors' => $this->hotMajors($scopedRequest)->toArray(),
            'intents' => $this->questionsByIntent($scopedRequest)->toArray(),
            'admission_methods' => $this->admissionMethods($scopedRequest)->toArray(),
        ]);
    }

    public function realtime(Request $request)
    {
        $minutes = min(max((int) $request->query('minutes', 30), 1), 240);
        $query = ChatLog::query()->where('created_at', '>=', now()->subMinutes($minutes));

        return response()->json([
            'window_minutes' => $minutes,
            'total_questions' => (clone $query)->count(),
            'active_sessions' => (clone $query)->distinct('session_id')->count('session_id'),
            'latest_logs' => (clone $query)
                ->latest()
                ->limit(10)
                ->get(['id', 'session_id', 'platform', 'intent', 'major_name', 'created_at']),
            'updated_at' => now()->toISOString(),
        ]);
    }

    public function export(Request $request)
    {
        $rows = $this->applyDateRange(ChatLog::query(), $request)
            ->latest()
            ->limit(min(max((int) $request->query('limit', 1000), 1), 5000))
            ->get();

        $filename = 'tuyen-sinh-report-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'id',
                'created_at',
                'session_id',
                'platform',
                'intent',
                'major_name',
                'admission_year',
                'admission_method',
                'score',
                'province',
                'user_query',
                'response_time',
            ]);

            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->id,
                    optional($row->created_at)->toDateTimeString(),
                    $row->session_id,
                    $row->platform,
                    $row->intent,
                    $row->major_name,
                    $row->admission_year,
                    $row->admission_method,
                    $row->score,
                    $row->province,
                    $row->user_query,
                    $row->response_time,
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function applyDateRange($query, Request $request)
    {
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->query('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->query('to'));
        }

        return $query;
    }

    private function dateBucketExpression(string $period, string $driver): string
    {
        if ($driver === 'sqlite') {
            return match ($period) {
                'month' => "strftime('%Y-%m', created_at)",
                'week' => "strftime('%Y-W%W', created_at)",
                default => "date(created_at)",
            };
        }

        return match ($period) {
            'month' => "DATE_FORMAT(created_at, '%Y-%m')",
            'week' => "YEARWEEK(created_at, 3)",
            default => 'DATE(created_at)',
        };
    }
}
