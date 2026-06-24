<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\AiChatLog;
use App\Models\ChatbotFeedback;
use Illuminate\View\View;

class ChatbotAnalyticsController extends Controller
{
    public function index(): View
    {
        $totalChats = AiChatLog::count();
        $total24h = AiChatLog::where('created_at', '>=', now()->subDay())->count();

        $totalFeedback = ChatbotFeedback::count();
        $helpfulCount = ChatbotFeedback::where('rating', 'helpful')->count();
        $helpfulRate = $totalFeedback > 0 ? round(($helpfulCount / $totalFeedback) * 100) : 0;

        $cacheHits = AiChatLog::where('source_type', 'cache')->count();
        $cacheHitRate = $totalChats > 0 ? round(($cacheHits / $totalChats) * 100) : 0;

        $errors = AiChatLog::where('source_type', 'error')->count();
        $errorRate = $totalChats > 0 ? round(($errors / $totalChats) * 100) : 0;

        $recentLogs = AiChatLog::latest()
            ->limit(20)
            ->get(['session_id', 'question', 'source_type', 'sentiment', 'created_at']);

        $feedbackTrend = ChatbotFeedback::where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, rating, COUNT(*) as count')
            ->groupBy('date', 'rating')
            ->orderBy('date', 'desc')
            ->get();

        $sourceBreakdown = AiChatLog::selectRaw('source_type, COUNT(*) as count')
            ->groupBy('source_type')
            ->get();

        $topIntents = AiChatLog::where('intent', '!=', null)
            ->selectRaw('intent, COUNT(*) as count')
            ->groupBy('intent')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        return view('internal.ai_audit.index', [
            'stats' => [
                'totalChats' => $totalChats,
                'total24h' => $total24h,
                'helpfulRate' => $helpfulRate,
                'cacheHitRate' => $cacheHitRate,
                'errorRate' => $errorRate,
            ],
            'recentLogs' => $recentLogs,
            'feedbackTrend' => $feedbackTrend,
            'sourceBreakdown' => $sourceBreakdown,
            'topIntents' => $topIntents,
        ]);
    }
}
