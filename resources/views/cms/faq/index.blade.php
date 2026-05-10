@extends('cms.layouts.app')
@section('title', 'Menu FAQ')
@section('breadcrumb')
    <li class="breadcrumb-item active">Menu FAQ</li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold">Manajemen Menu FAQ</h4>
    <a href="{{ route('cms.faq.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Tambah Menu
    </a>
</div>

<!-- Filter & Search -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
            <div class="btn-group btn-group-sm">
                @foreach([''=>'Semua','active'=>'Aktif','inactive'=>'Nonaktif','parent'=>'Menu Utama','sub'=>'Sub-menu'] as $val=>$label)
                    @php
                        $currentFilter = request('filter', '');
                        $isActive = $currentFilter === $val;
                        $url = $val === ''
                            ? route('cms.faq.index', array_filter(['search' => request('search')]))
                            : route('cms.faq.index', array_filter(['filter' => $val, 'search' => request('search')]));
                    @endphp
                    <a href="{{ $url }}" class="btn {{ $isActive ? 'btn-primary' : 'btn-outline-secondary' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>
            <input type="text" name="search" class="form-control form-control-sm" style="max-width:200px"
                   placeholder="Cari command / judul..." value="{{ request('search') }}">
            <button class="btn btn-sm btn-secondary"><i class="bi bi-search"></i></button>
            @if(request('search') || request('filter'))
                <a href="{{ route('cms.faq.index') }}" class="btn btn-sm btn-light">Reset</a>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="faqTable">
                <thead class="table-light">
                    <tr>
                        <th style="width:32px"></th>
                        <th>Command</th>
                        <th>Judul</th>
                        <th>Parent</th>
                        <th>Status</th>
                        <th>Urutan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="sortableBody">
                    @forelse($faqs as $faq)
                    <tr data-id="{{ $faq->id }}">
                        <td class="text-center" style="cursor:grab"><i class="bi bi-grip-vertical text-muted"></i></td>
                        <td>
                            @if($faq->parent_command)
                                <span class="ms-3"></span>
                            @endif
                            <code class="bg-success bg-opacity-10 text-success px-2 py-1 rounded">{{ $faq->command }}</code>
                        </td>
                        <td>{{ $faq->title }}</td>
                        <td>
                            @if($faq->parent_command)
                                <span class="badge bg-secondary">{{ $faq->parent_command }}</span>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td>
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input toggle-status" type="checkbox"
                                    data-id="{{ $faq->id }}"
                                    data-url="{{ route('cms.faq.toggle', $faq) }}"
                                    {{ $faq->is_active ? 'checked' : '' }}>
                            </div>
                        </td>
                        <td>{{ $faq->sort_order }}</td>
                        <td>
                            <a href="{{ route('cms.faq.edit', $faq) }}" class="btn btn-sm btn-outline-primary me-1">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <button class="btn btn-sm btn-outline-danger btn-delete"
                                data-id="{{ $faq->id }}"
                                data-title="{{ $faq->title }}"
                                data-url="{{ route('cms.faq.destroy', $faq) }}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            Belum ada menu FAQ. <a href="{{ route('cms.faq.create') }}">Tambah sekarang</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Hapus -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="bi bi-trash me-2"></i>Hapus Menu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Yakin ingin menghapus menu <strong id="deleteTitle"></strong>?
                Tindakan ini tidak dapat dibatalkan.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Hapus</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Toggle status
document.querySelectorAll('.toggle-status').forEach(el => {
    el.addEventListener('change', async function() {
        const res = await fetch(this.dataset.url, {
            method: 'PATCH',
            headers: {'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json'}
        });
        const data = await res.json();
        if (data.success) {
            showToast(data.message);
        } else {
            this.checked = !this.checked;
            showToast('Gagal mengubah status', 'error');
        }
    });
});

// Delete
let deleteUrl = null;
document.querySelectorAll('.btn-delete').forEach(el => {
    el.addEventListener('click', function() {
        deleteUrl = this.dataset.url;
        document.getElementById('deleteTitle').textContent = this.dataset.title;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    });
});
document.getElementById('confirmDelete').addEventListener('click', async function() {
    if (!deleteUrl) return;
    const res = await fetch(deleteUrl, {
        method: 'DELETE',
        headers: {'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json'}
    });
    const data = await res.json();
    if (data.success) {
        showToast(data.message);
        bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
        setTimeout(() => location.reload(), 800);
    }
});

// Sortable drag & drop
const sortable = Sortable.create(document.getElementById('sortableBody'), {
    animation: 150,
    handle: 'td:first-child',
    onEnd: async function() {
        const order = [...document.querySelectorAll('#sortableBody tr')].map(tr => tr.dataset.id);
        await fetch('{{ route("cms.faq.reorder") }}', {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json'},
            body: JSON.stringify({ order })
        });
        showToast('Urutan berhasil disimpan');
    }
});
</script>
@endpush
