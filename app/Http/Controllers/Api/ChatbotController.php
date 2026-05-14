<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Chatbot\ChatbotServiceInterface;

class ChatbotController extends Controller
{
    protected $chatbotService;

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
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal memproses pesan: ' . $e->getMessage()
            ], 500);
        }
    }
}
