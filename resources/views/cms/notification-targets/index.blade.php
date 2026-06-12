@extends('cms.layouts.app')
@section('title', 'Target Notifikasi WA')
@section('breadcrumb')
    <li class="breadcrumb-item active">Target Notifikasi WA</li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold">Target Notifikasi WA</h4>
    <a href="{{ route('cms.notification-targets.create') }}" class="btn btn-sm btn-primary">
        <i class="bi bi-plus-lg me-1"></i>Tambah Target
    </a>
</div>

<!-- Filter -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
            <select name="filter" class="form-select form-select-sm" style="width:auto">
                <option value="">Semua Status</option>
                <option value="active"   {{ request('filter')==='active'?'selected':'' }}>Aktif</option>
                <option value="inactive" {{ request('filter')==='inactive'?'selected':'' }}>Nonaktif</option>
            </select>
            <input type="text" name="search" class="form-control form-control-sm" style="max-width:220px"
                placeholder="Nama / Nomor WA..." value="{{ request('search') }}">
            <button class="btn btn-sm btn-primary"><i class="bi bi-search"></i> Filter</button>
            @if(request()->hasAny(['filter','search']))
                <a href="{{ route('cms.notification-targets.index') }}" class="btn btn-sm btn-light">Reset</a>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Nama</th>
                        <th>Nomor WA</th>
                        <th>Status</th>
                        <th>Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($targets as $t)
                    <tr>
                        <td class="text-muted">{{ $t->id }}</td>
                        <td class="fw-semibold">{{ $t->name }}</td>
                        <td class="font-monospace">{{ $t->masked_phone }}</td>
                        <td>
                            <button class="btn btn-sm {{ $t->is_active ? 'btn-success' : 'btn-outline-secondary' }} btn-toggle-status py-0"
                                data-id="{{ $t->id }}" style="font-size:.75rem">
                                {{ $t->is_active ? 'Aktif' : 'Nonaktif' }}
                            </button>
                        </td>
                        <td class="text-muted">{{ $t->created_at->format('d/m/Y') }}</td>
                        <td class="d-flex gap-1">
                            <a href="{{ route('cms.notification-targets.edit', $t) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button class="btn btn-sm btn-outline-danger btn-delete" data-id="{{ $t->id }}" data-name="{{ $t->name }}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">Belum ada target notifikasi</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('.btn-toggle-status').forEach(btn => {
    btn.addEventListener('click', async function() {
        const id  = this.dataset.id;
        try {
            const res  = await fetch(`{{ url('cms-minfara/notification-targets') }}/${id}/toggle`, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (data.success) {
                showToast(data.message);
                setTimeout(() => location.reload(), 600);
            } else {
                showToast(data.message || 'Gagal mengubah status.', 'danger');
            }
        } catch (e) {
            showToast('Terjadi kesalahan jaringan.', 'danger');
        }
    });
});

document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', async function() {
        const name = this.dataset.name;
        if (!confirm(`Hapus target "${name}"?`)) return;

        const id  = this.dataset.id;
        try {
            const res  = await fetch(`{{ url('cms-minfara/notification-targets') }}/${id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            });
            const data = await res.json();
            if (data.success) {
                showToast(data.message);
                setTimeout(() => location.reload(), 800);
            } else {
                showToast(data.message || 'Gagal menghapus.', 'danger');
            }
        } catch (e) {
            showToast('Terjadi kesalahan jaringan.', 'danger');
        }
    });
});
</script>
@endpush
