<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\InternalNotifyRequest;
use App\Jobs\SendWhatsAppNotificationJob;
use App\Models\NotificationLog;
use App\Models\NotificationTarget;
use App\Models\NotificationTemplate;
use App\Services\NotificationTemplateService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class InternalNotificationController extends Controller
{
    public function __construct(private readonly NotificationTemplateService $templateService) {}

    #[OA\Post(
        path: '/api/notification/notify',
        operationId: 'notificationNotify',
        summary: 'Kirim notifikasi WhatsApp ke semua target aktif',
        description: 'Notification API untuk mengirim notifikasi WhatsApp berdasarkan template trigger key. Pesan di-render dengan data dinamis lalu didispatch ke queue. Setiap target aktif mendapat satu NotificationLog dan satu job antrian. Membutuhkan header X-Internal-Key yang valid.',
        security: [['InternalApiKey' => []]],
        tags: ['Notification'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['trigger_key', 'data'],
                properties: [
                    new OA\Property(
                        property: 'trigger_key',
                        type: 'string',
                        example: 'transaction.created',
                        enum: [
                            'transaction.created',
                            'transaction.paid',
                            'transaction.cancelled',
                            'payment.reminder',
                            'enrollment.confirmed',
                            'enrollment.expired',
                        ],
                        description: <<<'DESC'
Kunci template notifikasi yang aktif. Sistem akan mencari template dengan trigger_key ini dan mengirim pesan ke semua target aktif.

**Daftar trigger key yang tersedia:**

| trigger_key | Keterangan | Kapan digunakan |
|---|---|---|
| `transaction.created` | Transaksi Baru | Saat pembeli menyelesaikan checkout / transaksi baru masuk |
| `transaction.paid` | Pembayaran Dikonfirmasi | Saat pembayaran diverifikasi dan dikonfirmasi oleh admin |
| `transaction.cancelled` | Transaksi Dibatalkan | Saat transaksi dibatalkan oleh admin atau otomatis expired |
| `payment.reminder` | Pengingat Pembayaran | Pengingat otomatis untuk transaksi yang belum dibayar |
| `enrollment.confirmed` | Pendaftaran Dikonfirmasi | Saat akses LMS siswa diaktifkan setelah pembayaran lunas |
| `enrollment.expired` | Masa Belajar Habis | Saat masa akses LMS siswa telah berakhir |
DESC
                    ),
                    new OA\Property(
                        property: 'data',
                        type: 'object',
                        description: <<<'DESC'
Data dinamis untuk mengisi variabel dalam template pesan. Variabel yang tidak dikirim akan dibiarkan kosong (tidak diganti).

**Variabel yang tersedia dalam template:**

| Variabel | Keterangan | Contoh |
|---|---|---|
| `{{customer_name}}` | Nama lengkap pelanggan | Budi Santoso |
| `{{program_name}}` | Nama program / kelas | Kelas B1 Online |
| `{{amount}}` | Nominal transaksi | Rp 1.499.000 |
| `{{transaction_id}}` | ID transaksi unik | TRX-2026-00123 |
| `{{status}}` | Status transaksi / pembayaran | Lunas |
| `{{date}}` | Tanggal & waktu kejadian | 12 Jun 2026, 22:00 |
| `{{cms_url}}` | URL halaman CMS terkait | http://187.77.116.47:8080/cms-minfara/... |
DESC
                        ,
                        properties: [
                            new OA\Property(property: 'customer_name', type: 'string', example: 'Budi Santoso',
                                description: 'Nama lengkap pelanggan'),
                            new OA\Property(property: 'program_name', type: 'string', example: 'Kelas B1 Online',
                                description: 'Nama program atau kelas yang dibeli'),
                            new OA\Property(property: 'amount', type: 'string', example: 'Rp 1.499.000',
                                description: 'Nominal transaksi (string terformat, bukan angka)'),
                            new OA\Property(property: 'transaction_id', type: 'string', example: 'TRX-2026-00123',
                                description: 'ID unik transaksi dari sistem LMS'),
                            new OA\Property(property: 'status', type: 'string', example: 'Lunas',
                                description: 'Status transaksi atau pembayaran'),
                            new OA\Property(property: 'date', type: 'string', example: '12 Jun 2026, 22:00',
                                description: 'Tanggal dan waktu kejadian (format bebas)'),
                            new OA\Property(property: 'cms_url', type: 'string',
                                example: 'http://187.77.116.47:8080/cms-minfara/admin/transactions/123',
                                description: 'URL halaman CMS yang relevan (opsional)'),
                        ]
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Notifikasi berhasil diantrekan',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'status', type: 'string', example: 'queued',
                        enum: ['queued', 'no_targets']),
                    new OA\Property(property: 'recipients', type: 'integer', example: 3,
                        description: 'Jumlah target yang menerima notifikasi'),
                    new OA\Property(property: 'template', type: 'string', example: 'transaction.created'),
                ])
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized — X-Internal-Key tidak valid atau kosong',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'error', type: 'string', example: 'Unauthorized'),
                ])
            ),
            new OA\Response(response: 404, description: 'Template tidak ditemukan atau nonaktif',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'error', type: 'string'),
                ])
            ),
            new OA\Response(response: 422, description: 'Validasi request gagal'),
        ]
    )]
    public function send(InternalNotifyRequest $request): JsonResponse
    {
        $template = NotificationTemplate::active()
            ->where('trigger_key', $request->trigger_key)
            ->first();

        if (!$template) {
            return response()->json([
                'error'   => 'Template not found or inactive for trigger_key: ' . $request->trigger_key,
            ], 404);
        }

        $targets = NotificationTarget::active()->get();

        if ($targets->isEmpty()) {
            return response()->json([
                'status'     => 'no_targets',
                'recipients' => 0,
                'template'   => $request->trigger_key,
            ]);
        }

        $data           = $request->input('data', []);
        $renderedMessage = $this->templateService->render($template->message_body, $data);

        foreach ($targets as $target) {
            $log = NotificationLog::create([
                'template_id'      => $template->id,
                'target_id'        => $target->id,
                'phone_number'     => $target->phone_number,
                'rendered_message' => $renderedMessage,
                'status'           => 'queued',
            ]);

            SendWhatsAppNotificationJob::dispatch(
                $target->phone_number,
                $renderedMessage,
                $log->id,
            );
        }

        return response()->json([
            'status'     => 'queued',
            'recipients' => $targets->count(),
            'template'   => $request->trigger_key,
        ]);
    }
}
