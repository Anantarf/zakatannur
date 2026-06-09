<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Chatbot\ChatbotServiceInterface;
use App\Services\Chatbot\Providers\MockChatbotProvider;

use App\Services\Chatbot\Providers\GeminiChatbotProvider;

class ChatbotServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(ChatbotServiceInterface::class, function () {
            $apiKey  = config('services.gemini.api_key');
            $model   = config('services.gemini.model');
            $baseUrl = config('services.gemini.base_url');

            if (!empty($apiKey)) {
                return new GeminiChatbotProvider($apiKey, $model, $baseUrl);
            }

            // Fallback ke mock jika API Key belum dikonfigurasi
            return new MockChatbotProvider();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
