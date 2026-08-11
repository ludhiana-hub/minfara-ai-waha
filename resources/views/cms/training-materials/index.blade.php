@extends('cms.layouts.app')
@section('title', 'Materi Latihan')
@section('breadcrumb')
    <li class="breadcrumb-item active">Materi Latihan</li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Materi Latihan AI Sales Agent</h4>
        <p class="text-muted small mb-0">Tempel teks atau upload dokumen (export chat admin manual, riset kompetitor, referensi teknik sales) — dibaca otomatis oleh job sintesis harian sebagai konteks tambahan, di luar log percakapan WA.</p>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addMaterialModal">
        <i class="bi bi-plus-lg me-1"></i>Tambah Materi
    </button>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
            <select name="category" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                <option value="all" {{ request('category','all')==='all'?'selected':'' }}>Semua Kategori</option>
                <option value="chat_export" {{ request('category')==='chat_export'?'selected':'' }}>Export Chat Admin</option>
                <option value="competitor_research" {{ request('category')==='competitor_research'?'selected':'' }}>Riset Kompetitor</option>
                <option value="sales_technique" {{ request('category')==='sales_technique'?'selected':'' }}>Teknik Sales</option>
                <option value="other" {{ request('category')==='other'?'selected':'' }}>Lainnya</option>
            </select>
        </form>
    </div>
</div>

@if($materials->isEmpty())
<div class="card">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-journal-plus fs-1 mb-3 d-block opacity-25"></i>
        <div>Belum ada materi latihan.</div>
        <small>Klik "Tambah Materi" untuk mulai menambahkan.</small>
    </div>
</div>
@else
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Judul</th>
                        <th>Kategori</th>
                        <th>Cuplikan</th>
                        <th>Sumber</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($materials as $item)
                    <tr>
                        <td class="text-muted">{{ $item->id }}</td>
                        <td style="max-width:200px">
                            {{ $item->title }}
                            @if($item->original_filename)
                                <div class="text-muted small"><i class="bi bi-paperclip"></i> {{ $item->original_filename }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-secondary">
                                @switch($item->category)
                                    @case('chat_export') Export Chat Admin @break
                                    @case('competitor_research') Riset Kompetitor @break
                                    @case('sales_technique') Teknik Sales @break
                                    @default Lainnya
                                @endswitch
                            </span>
                        </td>
                        <td class="text-muted" style="max-width:320px">{{ \Illuminate\Support\Str::limit($item->content, 150) }}</td>
                        <td class="text-muted small">{{ $item->source_note }}</td>
                        <td>
                            <span class="badge {{ $item->is_active ? 'bg-success' : 'bg-secondary' }} btn-toggle" style="cursor:pointer" data-id="{{ $item->id }}">
                                {{ $item->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="text-nowrap">
                            <form method="POST" action="{{ route('cms.training-materials.destroy', $item) }}" onsubmit="return confirm('Hapus materi ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @if($materials->hasPages())
    <div class="card-footer">
        {{ $materials->links() }}
    </div>
    @endif
</div>
@endif

<!-- Modal Tambah Materi -->
<div class="modal fade" id="addMaterialModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('cms.training-materials.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Materi Latihan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Judul <span class="text-muted small">(opsional kalau upload file — default pakai nama file)</span></label>
                        <input type="text" name="title" class="form-control" maxlength="255">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select name="category" class="form-select" required>
                            <option value="chat_export">Export Chat Admin (manual, bukan AI)</option>
                            <option value="competitor_research">Riset Kompetitor</option>
                            <option value="sales_technique">Teknik Sales</option>
                            <option value="other">Lainnya</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Upload File <span class="text-muted small">(boleh pilih beberapa sekaligus — PDF, DOCX, TXT, CSV, atau gambar/screenshot)</span></label>
                        <input type="file" name="files[]" class="form-control" multiple accept=".pdf,.docx,.txt,.csv,.jpg,.jpeg,.png,.webp">
                        <small class="text-muted">Maks 10MB per file. Isi teks otomatis diekstrak (gambar lewat OCR) — tiap file jadi 1 materi terpisah.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Atau Tempel Teks Langsung</label>
                        <textarea name="content" class="form-control" rows="8" placeholder="Kalau tidak upload file, tempel isi export chat, catatan riset kompetitor, atau referensi teknik sales di sini..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan Sumber (opsional)</label>
                        <input type="text" name="source_note" class="form-control" maxlength="255" placeholder="Mis: Export WA admin Juli 2026">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.btn-toggle').forEach(badge => {
    badge.addEventListener('click', async function () {
        const id  = this.dataset.id;
        const res = await fetch(`/cms-minfara/training-materials/${id}/toggle`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        });
        const data = await res.json();
        if (data.success) {
            this.textContent = data.is_active ? 'Aktif' : 'Nonaktif';
            this.classList.toggle('bg-success', data.is_active);
            this.classList.toggle('bg-secondary', !data.is_active);
            showToast('Status materi diperbarui.');
        }
    });
});
</script>
@endpush
