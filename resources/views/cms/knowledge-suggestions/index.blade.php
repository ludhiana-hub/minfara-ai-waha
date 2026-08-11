@extends('cms.layouts.app')
@section('title', 'Saran Pengetahuan')
@section('breadcrumb')
    <li class="breadcrumb-item active">Saran Pengetahuan</li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Saran Pengetahuan Otomatis</h4>
        <p class="text-muted small mb-0">Q&A pengetahuan & rekomendasi coaching sales hasil analisis AI dari percakapan kemarin. Review dulu sebelum masuk ke bot.</p>
    </div>
    <div class="d-flex gap-2">
        <span class="badge bg-warning text-dark fs-6">{{ $pendingCount }} pending</span>
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
            <select name="type" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                <option value="all" {{ request('type','all')==='all'?'selected':'' }}>Semua Tipe</option>
                <option value="knowledge" {{ request('type')==='knowledge'?'selected':'' }}>Pengetahuan</option>
                <option value="coaching" {{ request('type')==='coaching'?'selected':'' }}>Coaching Sales</option>
            </select>
        </form>
    </div>
</div>

@if($suggestions->isEmpty())
<div class="card">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-book-half fs-1 mb-3 d-block opacity-25"></i>
        <div>Belum ada saran untuk status/tipe ini.</div>
        <small>Saran akan muncul setelah job sintesis harian berjalan (tiap hari pukul 03:00).</small>
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
                        <th>Tipe</th>
                        <th>Pertanyaan / Temuan</th>
                        <th>Jawaban / Rekomendasi</th>
                        <th>Sumber</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($suggestions as $item)
                    <tr>
                        <td class="text-muted">{{ $item->id }}</td>
                        <td>
                            @if($item->type === 'coaching')
                                <span class="badge bg-info text-dark">Coaching Sales</span>
                            @else
                                <span class="badge bg-primary">Pengetahuan</span>
                            @endif
                        </td>
                        <td style="max-width:280px">
                            @if($item->status === 'pending')
                                <textarea class="form-control form-control-sm field-question" rows="2">{{ $item->question }}</textarea>
                            @else
                                {{ $item->question }}
                            @endif
                        </td>
                        <td style="max-width:320px">
                            @if($item->status === 'pending')
                                <textarea class="form-control form-control-sm field-answer" rows="2">{{ $item->answer }}</textarea>
                            @else
                                {{ $item->answer }}
                            @endif
                        </td>
                        <td class="text-muted small" style="max-width:220px">
                            @if($item->period_start || $item->period_end)
                                <div>{{ $item->period_start?->format('d/m') }}&ndash;{{ $item->period_end?->format('d/m') }}</div>
                            @endif
                            @if($item->example_phones)
                                <div>
                                    {{ implode(', ', array_slice($item->example_phones, 0, 3)) }}
                                    @if(count($item->example_phones) > 3)
                                        +{{ count($item->example_phones) - 3 }} lainnya
                                    @endif
                                </div>
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
                                <button class="btn btn-sm btn-primary btn-approve"
                                        data-id="{{ $item->id }}"
                                        title="Setujui (simpan editan bila ada) dan tambahkan ke bot">
                                    <i class="bi bi-check-lg me-1"></i>Setujui
                                </button>
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
document.querySelectorAll('.btn-approve').forEach(btn => {
    btn.addEventListener('click', async function () {
        if (!confirm('Setujui saran ini? Akan langsung ditambahkan ke bot.')) return;
        const id  = this.dataset.id;
        const row = this.closest('tr');
        const question = row.querySelector('.field-question')?.value ?? '';
        const answer   = row.querySelector('.field-answer')?.value ?? '';
        const res = await fetch(`/cms-minfara/knowledge-suggestions/${id}/approve`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({ question, answer })
        });
        const data = await res.json();
        if (data.success) {
            row.remove();
            showToast(data.message);
        }
    });
});

document.querySelectorAll('.btn-reject').forEach(btn => {
    btn.addEventListener('click', async function () {
        if (!confirm('Tolak saran ini?')) return;
        const id  = this.dataset.id;
        const res = await fetch(`/cms-minfara/knowledge-suggestions/${id}/reject`, {
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
