<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;

class RunAnalyticsJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;
    public int $tries   = 1;

    public function __construct(
        private readonly string $date,
        private readonly bool   $force = false,
    ) {}

    public function handle(): void
    {
        $args = ['--date' => $this->date];

        if ($this->force) {
            $args['--force'] = true;
        }

        Artisan::call('analytics:analyse', $args);
    }
}
