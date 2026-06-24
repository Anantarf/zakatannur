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
            'message' => 'required|string|min:1|max:500',
            'context' => 'sometimes|array',
            'context.last_intent' => 'sometimes|string|max:80',
            'context.last_source' => 'sometimes|string|max:40',
            'context.topic' => 'sometimes|string|max:40',
            'session_id' => 'sometimes|string|max:100',
        ]);

        try {
            // Trim dan validate message
            $message = trim($request->input('message'));
            if (strlen($message) < 1 || strlen($message) > 500) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pesan tidak valid. Gunakan 1-500 karakter.',
                    'retryable' => false,
                ], 400);
            }

            $sessionId = $request->input('session_id') ?: $request->session()->getId();
            $context = $request->input('context', []);

            // Handle chatbot
            $response = $this->chatbot->handle($message, $context, $sessionId);

            return response()->json($response->toArray(), $response->statusCode);
        } catch (\Throwable $e) {
            Log::error('Chatbot error', [
                'exception' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Layanan sementara tidak tersedia. Coba lagi nanti.',
                'retryable' => true,
            ], 503);
        }
    }
}
