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
            'context' => 'sometimes|array',
            'context.last_intent' => 'sometimes|string|max:80',
            'context.last_source' => 'sometimes|string|max:40',
            'context.topic' => 'sometimes|string|max:40',
        ]);

        try {
            $response = $this->chatbot->handle($request->message, $request->input('context', []));

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
