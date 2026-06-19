<?php

namespace App\Providers;

use App\Models\BotConfig;
use App\Services\Contracts\AiServiceInterface;
use App\Services\GeminiService;
use App\Services\GroqService;
use App\Services\OpenRouterService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Default AI provider binding — resolved from BotConfig at runtime
        // Allows swapping provider via DI without changing job/controller code
        $this->app->bind(AiServiceInterface::class, function () {
            $provider = BotConfig::get('ai_default_provider', 'groq');
            return match ($provider) {
                'gemini'     => new GeminiService(),
                'openrouter' => new OpenRouterService(),
                default      => new GroqService(),
            };
        });
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();
    }
}
