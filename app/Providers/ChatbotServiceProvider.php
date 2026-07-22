<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Chatbot\ChatbotServiceInterface;
use App\Services\Chatbot\Providers\MockChatbotProvider;
use App\Services\Chatbot\Providers\OpenAiChatbotProvider;
use App\Services\Chatbot\Providers\OpenAiEmbeddingsProvider;

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
                    models: [
                        'fast' => config('services.openai.fast_model'),
                        'premium' => config('services.openai.premium_model'),
                    ],
                );
            }

            return new MockChatbotProvider();
        });

        $this->app->singleton(OpenAiEmbeddingsProvider::class, function () {
            $openaiKey = config('services.openai.api_key');
            return new OpenAiEmbeddingsProvider(
                $openaiKey ?? '',
                config('services.openai.base_url', 'https://api.openai.com/v1')
            );
        });
    }

    public function boot()
    {
        //
    }
}
