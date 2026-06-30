@extends('cms.layouts.app')
@section('title', 'Saran FAQ')
@section('breadcrumb')
    <li class="breadcrumb-item active">Saran FAQ</li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Saran FAQ Otomatis</h4>
        <p class="text-muted small mb-0">Pertanyaan yang belum terjawab bot, dideteksi dari analisis percakapan harian.</p>
    </div>
    <div class="d-flex gap-2">
        <span class="badge bg-warning text-dark fs-6">{{ $pendingCount }} pending</span>
        @if($highPriorityCount > 0)
            <span class="badge bg-danger fs-6">{{ $highPriorityCount }} prioritas tinggi</span>
        @endif
    </div>
</div>

<!-- Filter -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
            <select name="status" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                <option value="pending" {{ request('status','pending')==='pending'?'selected':'' }}>Pending</option>
                <option value="approved" {{ request('status')==='approved'?'selected':'' }}>Disetujui</option>
                <option value="rejected" {{ request('status')==='rejected'?'selected':'' }}>Ditolak</option>
                <option value="all" {{ request('status')==='all'?'selected':'' }}>Semua</option>
            </select>
        </form>
    </div>
</div>

@if($suggestions->isEmpty())
<div class="card">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-lightbulb fs-1 mb-3 d-block opacity-25"></i>
        <div>Belum ada saran FAQ untuk status ini.</div>
        <small>Saran akan muncul setelah analisis percakapan harian berjalan (setiap hari pukul 02:00).</small>
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
                        <th>Pertanyaan yang Belum Terjawab</th>
                        <th class="text-center">Frekuensi</th>
                        <th>Prioritas</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($suggestions as $item)
                    <tr>
                        <td class="text-muted">{{ $item->id }}</td>
                        <td style="max-width:400px">
                            <div class="fw-semibold">{{ $item->question }}</div>
                            @if($item->example_phones)
                                <div class="text-muted small mt-1">
                                    Dari: {{ implode(', ', array_slice($item->example_phones, 0, 3)) }}
                                    @if(count($item->example_phones) > 3)
                                        +{{ count($item->example_phones) - 3 }} lainnya
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge bg-secondary rounded-pill">{{ $item->frequency }}×</span>
                        </td>
                        <td>
                            @if($item->high_priority)
                                <span class="badge bg-danger">Tinggi</span>
                            @else
                                <span class="text-muted small">Normal</span>
                            @endif
                        </td>
                        <td>
                            @if($item->status === 'pending')
                                <span class="badge bg-warning text-dark">Pending</span>
                            @elseif($item->status === 'approved')
                                <span class="badge bg-success">Disetujui</span>
                            @else
                                <span class="badge bg-secondary">Ditolak</span>
                            @endif
                        </td>
                        <td class="text-nowrap">
                            @if($item->status === 'pending')
                                <a href="{{ route('cms.faq-suggestions.approve', $item->id) }}"
                                   class="btn btn-sm btn-primary"
                                   title="Buat jadi FAQ">
                                    <i class="bi bi-plus-lg me-1"></i>Buat FAQ
                                </a>
                                <button class="btn btn-sm btn-outline-secondary btn-reject"
                                        data-id="{{ $item->id }}"
                                        title="Tolak saran ini">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @if($suggestions->hasPages())
    <div class="card-footer">
        {{ $suggestions->links() }}
    </div>
    @endif
</div>
@endif
@endsection

@push('scripts')
<script>
document.querySelectorAll('.btn-reject').forEach(btn => {
    btn.addEventListener('click', async function () {
        if (!confirm('Tolak saran FAQ ini?')) return;
        const id  = this.dataset.id;
        const res = await fetch(`/cms-minfara/faq-suggestions/${id}/reject`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        });
        const data = await res.json();
        if (data.success) {
            this.closest('tr').remove();
            showToast(data.message);
        }
    });
});
</script>
@endpush
