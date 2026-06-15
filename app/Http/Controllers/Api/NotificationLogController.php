<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class NotificationLogController extends Controller
{
    // ── List ──────────────────────────────────────────────────────────────────

    #[OA\Get(
        path: '/api/notification/logs',
        operationId: 'notificationLogIndex',
        summary: 'Daftar log notifikasi WhatsApp (paginated)',
        description: 'Daftar semua notifikasi yang pernah dikirim/diantrekan, lengkap dengan status pengiriman dan relasi template. Bisa difilter berdasarkan status, nomor telepon, trigger key, dan rentang tanggal.',
        tags: ['Notification Log'],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', required: false,
                description: 'Filter berdasarkan status pengiriman',
                schema: new OA\Schema(type: 'string', enum: ['queued', 'sent', 'failed'])),
            new OA\Parameter(name: 'phone', in: 'query', required: false,
                description: 'Filter berdasarkan nomor telepon penerima',
                schema: new OA\Schema(type: 'string', example: '628123456789@c.us')),
            new OA\Parameter(name: 'trigger_key', in: 'query', required: false,
                description: 'Filter berdasarkan trigger_key template',
                schema: new OA\Schema(type: 'string', example: 'transaction.paid')),
            new OA\Parameter(name: 'date_from', in: 'query', required: false,
                schema: new OA\Schema(type: 'string', format: 'date', example: '2026-06-01')),
            new OA\Parameter(name: 'date_to', in: 'query', required: false,
                schema: new OA\Schema(type: 'string', format: 'date', example: '2026-06-15')),
            new OA\Parameter(name: 'per_page', in: 'query', required: false,
                schema: new OA\Schema(type: 'integer', default: 20, maximum: 100)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated list log notifikasi',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'data', type: 'array',
                        items: new OA\Items(properties: [
                            new OA\Property(property: 'id', type: 'integer'),
                            new OA\Property(property: 'phone_number', type: 'string'),
                            new OA\Property(property: 'rendered_message', type: 'string'),
                            new OA\Property(property: 'status', type: 'string',
                                enum: ['queued', 'sent', 'failed']),
                            new OA\Property(property: 'sent_at', type: 'string', format: 'date-time',
                                nullable: true),
                            new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                            new OA\Property(property: 'template', nullable: true, properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'trigger_key', type: 'string'),
                                new OA\Property(property: 'name', type: 'string'),
                            ]),
                            new OA\Property(property: 'target', nullable: true, properties: [
                                new OA\Property(property: 'id', type: 'integer'),
                                new OA\Property(property: 'name', type: 'string'),
                                new OA\Property(property: 'phone_number', type: 'string'),
                            ]),
                        ])
                    ),
                    new OA\Property(property: 'current_page', type: 'integer'),
                    new OA\Property(property: 'last_page', type: 'integer'),
                    new OA\Property(property: 'total', type: 'integer'),
                ])
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = NotificationLog::with(['template:id,trigger_key,name', 'target:id,name,phone_number'])
            ->latest();

        if ($request->status)    $query->where('status', $request->status);
        if ($request->phone)     $query->where('phone_number', 'like', '%' . $request->phone . '%');
        if ($request->date_from) $query->where('created_at', '>=', $request->date_from . ' 00:00:00');
        if ($request->date_to)   $query->where('created_at', '<=', $request->date_to . ' 23:59:59');

        if ($request->trigger_key) {
            $query->whereHas('template', fn($q) => $q->where('trigger_key', $request->trigger_key));
        }

        $perPage = min((int) ($request->per_page ?? 20), 100);

        return response()->json($query->paginate($perPage));
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    #[OA\Get(
        path: '/api/notification/logs/{id}',
        operationId: 'notificationLogShow',
        summary: 'Detail satu log notifikasi',
        description: 'Mengembalikan detail lengkap satu log notifikasi, termasuk pesan yang sudah di-render, respons dari WAHA, dan relasi template + target.',
        tags: ['Notification Log'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true,
                schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Detail log notifikasi berhasil',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'id', type: 'integer'),
                    new OA\Property(property: 'phone_number', type: 'string'),
                    new OA\Property(property: 'rendered_message', type: 'string'),
                    new OA\Property(property: 'status', type: 'string',
                        enum: ['queued', 'sent', 'failed']),
                    new OA\Property(property: 'waha_response', type: 'string', nullable: true,
                        description: 'Raw JSON response dari WAHA API'),
                    new OA\Property(property: 'sent_at', type: 'string', format: 'date-time', nullable: true),
                    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
                    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
                    new OA\Property(property: 'template', nullable: true, properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'trigger_key', type: 'string'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'message_body', type: 'string'),
                    ]),
                    new OA\Property(property: 'target', nullable: true, properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'phone_number', type: 'string'),
                    ]),
                ])
            ),
            new OA\Response(response: 404, description: 'Log tidak ditemukan'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $log = NotificationLog::with(['template', 'target'])->findOrFail($id);

        return response()->json($log);
    }
}
