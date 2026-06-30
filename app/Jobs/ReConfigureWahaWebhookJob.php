<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class ReConfigureWahaWebhookJob implements ShouldQueue
{
    use Queueable;

    public int $tries  = 3;
    public int $backoff = 5;

    public function handle(): void
    {
        Artisan::call('waha:ensure-webhook');
        Log::info('ReConfigureWahaWebhookJob: webhook re-configured after WAHA WORKING event');
    }
}
