<?php

namespace App\Console\Commands;

use App\Models\AiRequestTrace;
use Illuminate\Console\Command;

class PruneAiTracesCommand extends Command
{
    protected $signature   = 'ai:prune-traces {--days=14}';
    protected $description = 'Hapus baris ai_request_traces yang lebih tua dari N hari';

    public function handle(): int
    {
        $days = (int) $this->option('days');

        $deleted = AiRequestTrace::where('created_at', '<', now()->subDays($days))->delete();

        $this->info("Deleted {$deleted} trace rows older than {$days} days.");

        return self::SUCCESS;
    }
}
