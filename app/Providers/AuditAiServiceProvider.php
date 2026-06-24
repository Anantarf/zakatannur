<?php

namespace App\Providers;

use App\Services\Audit\GeminiAuditProvider;
use Illuminate\Support\ServiceProvider;

class AuditAiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GeminiAuditProvider::class, function () {
            return new GeminiAuditProvider(
                config('services.gemini.api_key', ''),
                config('services.gemini.model', 'gemini-2.5-flash'),
                config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta/models'),
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
