<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Chatbot\ChatbotServiceInterface;
use App\Services\Chatbot\Providers\MockChatbotProvider;
use App\Services\Chatbot\Providers\OpenAiChatbotProvider;

class ChatbotServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(ChatbotServiceInterface::class, function () {
            $openaiKey = config('services.openai.api_key');
            if (!empty($openaiKey)) {
                return new OpenAiChatbotProvider(
                    $openaiKey,
                    config('services.openai.model'),
                    config('services.openai.base_url'),
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
