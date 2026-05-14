<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\Chatbot\ChatbotServiceInterface;
use App\Services\Chatbot\Providers\MockChatbotProvider;

class ChatbotServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Ganti MockChatbotProvider dengan provider asli (OpenAI/Gemini) nantinya di sini
        $this->app->bind(ChatbotServiceInterface::class, MockChatbotProvider::class);
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
