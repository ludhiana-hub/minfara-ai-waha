<?php

namespace App\Providers;

use App\Models\BotConfig;
use App\Services\Ai\Support\NullSleeper;
use App\Services\Ai\Support\Sleeper;
use App\Services\Ai\Support\SleeperContract;
use Illuminate\Pagination\Paginator;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // (Dead binding removed — nothing in the codebase ever resolved AiServiceInterface.
        // It also ran a DB query in register() during every console bootstrap, including
        // composer install's package:discover step where no database exists yet, and its
        // match() was missing NvidiaService with 'ai_default_provider' never seeded. Provider
        // selection lives in ProcessAiReply::$providerOrder, not a container binding.)

        // NullSleeper in tests so AiRouter's retry-delay (usleep) doesn't slow the suite down.
        $this->app->bind(SleeperContract::class, fn () => $this->app->environment('testing')
            ? new NullSleeper()
            : new Sleeper());
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // queue:work reuses the same PHP process across many jobs (see --max-jobs in the
        // compose files) — BotConfig's per-request memo must be cleared between jobs or a
        // worker would keep serving stale config for its entire lifetime instead of just
        // within one job.
        Event::listen(JobProcessing::class, fn () => BotConfig::clearRequestMemo());
        Event::listen(JobProcessed::class, fn () => BotConfig::clearRequestMemo());
    }
}
