<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InternalNotifyRequest;
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
        path: '/api/internal/notify',
        operationId: 'internalNotify',
        summary: 'Kirim notifikasi WhatsApp ke semua target aktif',
        description: 'Endpoint internal untuk mengirim notifikasi WhatsApp berdasarkan template trigger key. Pesan di-render dengan data dinamis lalu didispatch ke queue. Setiap target aktif mendapat satu NotificationLog dan satu job antrian. Membutuhkan header X-Internal-Key yang valid.',
        security: [['InternalApiKey' => []]],
        tags: ['Internal'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['trigger_key', 'data'],
                properties: [
                    new OA\Property(property: 'trigger_key', type: 'string', example: 'transaction.created',
                        description: 'Kunci template notifikasi yang aktif'),
                    new OA\Property(
                        property: 'data',
                        type: 'object',
                        description: 'Data dinamis untuk mengisi variabel template',
                        properties: [
                            new OA\Property(property: 'customer_name', type: 'string', example: 'Budi Santoso'),
                            new OA\Property(property: 'program_name', type: 'string', example: 'Program Reguler'),
                            new OA\Property(property: 'amount', type: 'number', example: 1499000),
                            new OA\Property(property: 'transaction_id', type: 'string', example: 'TRX-2026-00123'),
                            new OA\Property(property: 'status', type: 'string', example: 'Lunas'),
                            new OA\Property(property: 'cms_url', type: 'string',
                                example: 'http://187.77.116.47:8080/cms-minfara'),
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
    public function handle(InternalNotifyRequest $request): JsonResponse
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
