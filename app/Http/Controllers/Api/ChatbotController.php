<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Chatbot\ChatbotOrchestrator;
use Illuminate\Support\Facades\Log;

class ChatbotController extends Controller
{
    protected ChatbotOrchestrator $chatbot;

    public function __construct(ChatbotOrchestrator $chatbot)
    {
        $this->chatbot = $chatbot;
    }

    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:500',
        ]);

        try {
            $response = $this->chatbot->handle($request->message);

            return response()->json($response->toArray(), $response->statusCode);
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
