<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Chatbot\ChatbotServiceInterface;
use App\Services\Chatbot\Providers\GeminiChatbotProvider;
use App\Services\Chatbot\Providers\MockChatbotProvider;

class ChatbotServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(ChatbotServiceInterface::class, function () {
            $geminiKey = config('services.gemini.api_key');
            if (!empty($geminiKey)) {
                return new GeminiChatbotProvider(
                    $geminiKey,
                    config('services.gemini.model'),
                    config('services.gemini.base_url'),
                );
            }

            return new MockChatbotProvider();
        });
    }

    public function boot()
    {
        //
    }
}
