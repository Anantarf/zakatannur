<?php

namespace App\Http\Controllers\Api;

use App\Services\Chatbot\ChatbotOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatbotStreamController
{
    protected ChatbotOrchestrator $chatbot;

    public function __construct(ChatbotOrchestrator $chatbot)
    {
        $this->chatbot = $chatbot;
    }

    public function stream(Request $request): StreamedResponse|JsonResponse|Response
    {
        $request->validate([
            'message' => 'required|string|min:1|max:500',
            'context' => 'sometimes|array',
            'session_id' => 'sometimes|string|max:100',
        ]);

        $message = trim($request->input('message'));
        if (strlen($message) < 1 || strlen($message) > 500) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pesan tidak valid. Gunakan 1-500 karakter.',
                'retryable' => false,
            ], 400);
        }

        $sessionId = $request->input('session_id')
            ?: hash('sha256', $request->ip() . '|' . (string) $request->userAgent());
        $context = $request->input('context', []);

        return new StreamedResponse(function () use ($message, $context, $sessionId) {
            try {
                $response = $this->chatbot->handle($message, $context, $sessionId);

                if ($response->statusCode === 200) {
                    $reply = $response->reply;
                    // ponytail: batch chunks ~50 chars to reduce overhead, no artificial delay
                    $chunkSize = 50;
                    for ($i = 0; $i < strlen($reply); $i += $chunkSize) {
                        $chunk = substr($reply, $i, $chunkSize);
                        echo "data: " . json_encode(['chunk' => $chunk]) . "\n\n";
                        flush();
                    }

                    if (!empty($response->actions)) {
                        echo "data: " . json_encode(['actions' => $response->actions]) . "\n\n";
                        flush();
                    }

                    echo "data: [DONE]\n\n";
                } else {
                    echo "data: " . json_encode([
                        'error' => $response->reply,
                        'retryable' => $response->retryable,
                    ]) . "\n\n";
                }

                flush();
            } catch (\Throwable $e) {
                Log::error('Chatbot stream error', [
                    'exception' => $e->getMessage(),
                ]);
                echo "data: " . json_encode([
                    'error' => 'Layanan streaming mengalami kendala',
                    'retryable' => true,
                ]) . "\n\n";
                flush();
            }
        }, 200, [
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'text/event-stream',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
