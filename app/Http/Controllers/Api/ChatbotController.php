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
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'reply' => $reply
                ]
            ]);
        } catch (\Throwable $e) {
            Log::error('Chatbot request failed.', [
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memproses pesan. Silakan coba beberapa saat lagi.'
            ], 500);
        }
    }
}
