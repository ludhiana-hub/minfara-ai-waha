<?php

namespace App\Jobs;

use App\Models\NotificationLog;
use App\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendWhatsAppNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries   = 3;
    public int $backoff = 60;
    public int $timeout = 60;

    public function __construct(
        private readonly string $phone,
        private readonly string $renderedMessage,
        private readonly ?int   $logId = null,
    ) {}

    public function handle(WhatsAppService $whatsapp): void
    {
        if (!$whatsapp->isSessionWorking()) {
            Log::warning('SendWhatsAppNotificationJob: WAHA session not WORKING, releasing for retry', [
                'phone' => $this->phone,
                'logId' => $this->logId,
            ]);
            $this->release(300);
            return;
        }

        $sent = $whatsapp->sendMessage($this->phone, $this->renderedMessage);

        if ($this->logId) {
            NotificationLog::where('id', $this->logId)->update(
                $sent
                    ? ['status' => 'sent', 'sent_at' => now(), 'waha_response' => 'OK']
                    : ['status' => 'failed', 'waha_response' => 'sendMessage returned false']
            );
        }

        if (!$sent) {
            throw new \RuntimeException("WAHA sendMessage failed for {$this->phone}");
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendWhatsAppNotificationJob failed permanently', [
            'phone'   => $this->phone,
            'logId'   => $this->logId,
            'message' => $exception->getMessage(),
        ]);

        if ($this->logId) {
            NotificationLog::where('id', $this->logId)->update([
                'status'        => 'failed',
                'waha_response' => $exception->getMessage(),
            ]);
        }
    }
}
