<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FaqMenu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use OpenApi\Attributes as OA;

class FaqController extends Controller
{
    #[OA\Get(
        path: '/api/faqs',
        operationId: 'faqIndex',
        summary: 'List semua FAQ',
        description: 'Mengembalikan daftar FAQ, diurutkan berdasarkan sort_order. Bisa difilter berdasarkan pencarian atau status aktif.',
        tags: ['FAQ'],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', required: false,
                description: 'Cari berdasarkan command, judul, atau isi',
                schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'mode', in: 'query', required: false,
                description: 'Filter status FAQ',
                schema: new OA\Schema(type: 'string', enum: ['active', 'inactive'])),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Daftar FAQ',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1),
                        new OA\Property(property: 'command', type: 'string', example: '1'),
                        new OA\Property(property: 'title', type: 'string', example: 'Info Pendaftaran'),
                        new OA\Property(property: 'content', type: 'string', example: 'Pendaftaran bisa dilakukan...'),
                        new OA\Property(property: 'parent_command', type: 'string', nullable: true),
                        new OA\Property(property: 'is_active', type: 'boolean', example: true),
                        new OA\Property(property: 'sort_order', type: 'integer', example: 1),
                    ])
                )
            ),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = FaqMenu::query();

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('command', 'like', "%{$s}%")
                  ->orWhere('title',   'like', "%{$s}%")
                  ->orWhere('content', 'like', "%{$s}%");
            });
        }

        if ($request->mode === 'active') {
            $query->where('is_active', true);
        } elseif ($request->mode === 'inactive') {
            $query->where('is_active', false);
        }

        return response()->json(
            $query->orderBy('sort_order')->orderBy('command')->get()
        );
    }

    #[OA\Post(
        path: '/api/faqs',
        operationId: 'faqStore',
        summary: 'Buat FAQ baru',
        description: 'Membuat entri FAQ baru. sort_order otomatis diisi sebagai max+1.',
        tags: ['FAQ'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['command', 'title', 'content'],
                properties: [
                    new OA\Property(property: 'command', type: 'string', example: '1',
                        description: 'Kode perintah (maks 100 karakter)'),
                    new OA\Property(property: 'title', type: 'string', example: 'Info Pendaftaran'),
                    new OA\Property(property: 'content', type: 'string', example: 'Untuk mendaftar, kunjungi...'),
                    new OA\Property(property: 'parent_command', type: 'string', nullable: true),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'FAQ berhasil dibuat',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'id', type: 'integer', example: 5),
                ])
            ),
            new OA\Response(response: 422, description: 'Validasi gagal'),
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'command'        => ['required', 'string', 'max:100'],
            'title'          => ['required', 'string', 'max:255'],
            'content'        => ['required', 'string', 'min:5', 'max:4096'],
            'parent_command' => ['nullable', 'string', 'max:100'],
            'is_active'      => ['nullable', 'boolean'],
        ]);

        $faq = FaqMenu::create([
            ...$data,
            'is_active'  => $data['is_active'] ?? true,
            'sort_order' => (FaqMenu::max('sort_order') ?? 0) + 1,
        ]);

        Cache::forget('faq_ai_context');

        return response()->json($faq, 201);
    }

    #[OA\Get(
        path: '/api/faqs/{faq}',
        operationId: 'faqShow',
        summary: 'Detail satu FAQ',
        tags: ['FAQ'],
        parameters: [
            new OA\Parameter(name: 'faq', in: 'path', required: true,
                description: 'ID FAQ', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Data FAQ'),
            new OA\Response(response: 404, description: 'FAQ tidak ditemukan'),
        ]
    )]
    public function show(FaqMenu $faq): JsonResponse
    {
        return response()->json($faq);
    }

    #[OA\Put(
        path: '/api/faqs/{faq}',
        operationId: 'faqUpdate',
        summary: 'Update FAQ',
        tags: ['FAQ'],
        parameters: [
            new OA\Parameter(name: 'faq', in: 'path', required: true,
                description: 'ID FAQ', schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(properties: [
                new OA\Property(property: 'command', type: 'string', example: '1'),
                new OA\Property(property: 'title', type: 'string', example: 'Info Pendaftaran'),
                new OA\Property(property: 'content', type: 'string', example: 'Deskripsi baru...'),
                new OA\Property(property: 'parent_command', type: 'string', nullable: true),
                new OA\Property(property: 'is_active', type: 'boolean'),
            ])
        ),
        responses: [
            new OA\Response(response: 200, description: 'FAQ berhasil diupdate'),
            new OA\Response(response: 404, description: 'FAQ tidak ditemukan'),
            new OA\Response(response: 422, description: 'Validasi gagal'),
        ]
    )]
    public function update(Request $request, FaqMenu $faq): JsonResponse
    {
        $data = $request->validate([
            'command'        => ['sometimes', 'string', 'max:100'],
            'title'          => ['sometimes', 'string', 'max:255'],
            'content'        => ['sometimes', 'string', 'min:5', 'max:4096'],
            'parent_command' => ['nullable', 'string', 'max:100'],
            'is_active'      => ['nullable', 'boolean'],
        ]);

        $faq->update($data);
        Cache::forget('faq_ai_context');

        return response()->json($faq->fresh());
    }

    #[OA\Delete(
        path: '/api/faqs/{faq}',
        operationId: 'faqDestroy',
        summary: 'Hapus FAQ',
        tags: ['FAQ'],
        parameters: [
            new OA\Parameter(name: 'faq', in: 'path', required: true,
                description: 'ID FAQ', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'FAQ berhasil dihapus',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'FAQ berhasil dihapus'),
                ])
            ),
            new OA\Response(response: 404, description: 'FAQ tidak ditemukan'),
        ]
    )]
    public function destroy(FaqMenu $faq): JsonResponse
    {
        $faq->delete();
        Cache::forget('faq_ai_context');

        return response()->json(['message' => 'FAQ berhasil dihapus']);
    }

    #[OA\Post(
        path: '/api/faqs/{faq}/toggle',
        operationId: 'faqToggle',
        summary: 'Toggle status aktif FAQ',
        description: 'Membalik nilai is_active dari FAQ. Aktif → Nonaktif atau sebaliknya.',
        tags: ['FAQ'],
        parameters: [
            new OA\Parameter(name: 'faq', in: 'path', required: true,
                description: 'ID FAQ', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Status berhasil diubah, mengembalikan data FAQ terbaru'),
            new OA\Response(response: 404, description: 'FAQ tidak ditemukan'),
        ]
    )]
    public function toggle(FaqMenu $faq): JsonResponse
    {
        $faq->update(['is_active' => !$faq->is_active]);
        Cache::forget('faq_ai_context');

        return response()->json($faq->fresh());
    }

    #[OA\Post(
        path: '/api/faqs/reorder',
        operationId: 'faqReorder',
        summary: 'Update urutan FAQ (drag-and-drop)',
        description: 'Memperbarui sort_order dari beberapa FAQ sekaligus. Dikirim sebagai array of {id, sort_order}.',
        tags: ['FAQ'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['items'],
                properties: [
                    new OA\Property(
                        property: 'items',
                        type: 'array',
                        items: new OA\Items(properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 3),
                            new OA\Property(property: 'sort_order', type: 'integer', example: 1),
                        ])
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Urutan berhasil diperbarui',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Urutan berhasil diperbarui'),
                ])
            ),
            new OA\Response(response: 422, description: 'Validasi gagal'),
        ]
    )]
    public function reorder(Request $request): JsonResponse
    {
        $items = $request->validate([
            'items'              => ['required', 'array'],
            'items.*.id'         => ['required', 'integer', 'exists:faq_menus,id'],
            'items.*.sort_order' => ['required', 'integer'],
        ])['items'];

        foreach ($items as $item) {
            FaqMenu::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        Cache::forget('faq_ai_context');

        return response()->json(['message' => 'Urutan berhasil diperbarui']);
    }

    #[OA\Post(
        path: '/api/faqs/bulk-toggle',
        operationId: 'faqBulkToggle',
        summary: 'Toggle status aktif beberapa FAQ sekaligus',
        tags: ['FAQ'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['ids', 'is_active'],
                properties: [
                    new OA\Property(property: 'ids', type: 'array',
                        items: new OA\Items(type: 'integer'), example: [1, 2, 3]),
                    new OA\Property(property: 'is_active', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Status FAQ diperbarui',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Status FAQ diperbarui'),
                ])
            ),
            new OA\Response(response: 422, description: 'Validasi gagal'),
        ]
    )]
    public function bulkToggle(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids'       => ['required', 'array'],
            'ids.*'     => ['integer', 'exists:faq_menus,id'],
            'is_active' => ['required', 'boolean'],
        ]);

        FaqMenu::whereIn('id', $data['ids'])->update(['is_active' => $data['is_active']]);
        Cache::forget('faq_ai_context');

        return response()->json(['message' => 'Status FAQ diperbarui']);
    }
}
