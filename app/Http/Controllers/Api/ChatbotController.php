<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Chatbot\ChatbotServiceInterface;
use Illuminate\Support\Facades\Log;

class ChatbotController extends Controller
{
    protected ChatbotServiceInterface $chatbotService;

    public function __construct(ChatbotServiceInterface $chatbotService)
    {
        $this->chatbotService = $chatbotService;
    }

    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:500',
        ]);

        try {
            $reply = $this->chatbotService->sendMessage($request->message);

            $wasFallback = method_exists($this->chatbotService, 'wasLastReplyFallback')
                && $this->chatbotService->wasLastReplyFallback();

            $cleanReply = $wasFallback && str_starts_with($reply, ChatbotServiceInterface::FALLBACK_PREFIX)
                ? substr($reply, strlen(ChatbotServiceInterface::FALLBACK_PREFIX))
                : $reply;

            if ($wasFallback) {
                return response()->json([
                    'status' => 'error',
                    'message' => $cleanReply,
                    'retryable' => true,
                ], 503);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'reply' => $cleanReply,
                ]
            ]);
        } catch (\Throwable $e) {
            Log::error('Chatbot request failed.', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memproses pesan. Silakan coba beberapa saat lagi.',
                'retryable' => true,
            ], 500);
        }
    }
}
