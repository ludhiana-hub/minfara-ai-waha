@extends('cms.layouts.app')
@section('title', 'Log Percakapan')
@section('breadcrumb')
    <li class="breadcrumb-item active">Log Percakapan</li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold">Log Percakapan</h4>
    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#clearModal">
        <i class="bi bi-trash me-1"></i>Hapus Log
    </button>
</div>

<!-- Stats -->
<div class="row g-2 mb-3">
    <div class="col-auto">
        <div class="card px-3 py-2">
            <div class="small text-muted">Total Hari Ini</div>
            <div class="fw-bold">{{ $todayTotal }}</div>
        </div>
    </div>
    @foreach(['faq'=>'success','ai'=>'primary','error'=>'danger','end_chat'=>'secondary'] as $mode=>$color)
    <div class="col-auto">
        <div class="card px-3 py-2">
            <div class="small text-muted">{{ ucfirst(str_replace('_',' ',$mode)) }}</div>
            <div class="fw-bold text-{{ $color }}">{{ $todayStats[$mode] ?? 0 }}</div>
        </div>
    </div>
    @endforeach
</div>

<!-- Filter -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
            <select name="mode" class="form-select form-select-sm" style="width:auto">
                <option value="">Semua Mode</option>
                <option value="faq" {{ request('mode')==='faq'?'selected':'' }}>FAQ</option>
                <option value="ai" {{ request('mode')==='ai'?'selected':'' }}>AI</option>
                <option value="error" {{ request('mode')==='error'?'selected':'' }}>Error</option>
                <option value="end_chat" {{ request('mode')==='end_chat'?'selected':'' }}>End Chat</option>
            </select>
            <input type="text" name="search" class="form-control form-control-sm" style="max-width:200px"
                placeholder="Nomor WA / Nama..." value="{{ request('search') }}">
            <input type="text" name="date_from" class="form-control form-control-sm flatpickr" style="max-width:130px"
                placeholder="Dari tanggal" value="{{ request('date_from') }}">
            <input type="text" name="date_to" class="form-control form-control-sm flatpickr" style="max-width:130px"
                placeholder="Sampai tanggal" value="{{ request('date_to') }}">
            <button class="btn btn-sm btn-primary"><i class="bi bi-search"></i> Filter</button>
            @if(request()->hasAny(['mode','search','date_from','date_to']))
                <a href="{{ route('cms.log.index') }}" class="btn btn-sm btn-light">Reset</a>
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
                        <th>Pengirim</th>
                        <th>Pesan Masuk</th>
                        <th>Mode</th>
                        <th>Token AI</th>
                        <th>IP</th>
                        <th>Waktu</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td class="text-muted">{{ $log->id }}</td>
                        <td class="text-nowrap">
                            @if($log->contact_name)
                                <div class="fw-semibold">{{ $log->contact_name }}</div>
                            @endif
                            <div class="text-muted {{ $log->contact_name ? 'small' : '' }}">
                                {{ $log->display_number }}
                            </div>
                        </td>
                        <td style="max-width:260px">{{ Str::limit($log->message_in, 60) }}</td>
                        <td><span class="badge badge-{{ $log->mode }}">{{ str_replace('_',' ',$log->mode) }}</span></td>
                        <td>{{ $log->ai_tokens_used ?? '—' }}</td>
                        <td class="text-muted font-monospace" style="font-size:.75rem">
                            {{ $log->ip_address ?? '—' }}
                        </td>
                        <td class="text-nowrap text-muted">{{ $log->responded_at?->format('d/m H:i') ?? '—' }}</td>
                        <td>
                            <a href="{{ route('cms.log.show', $log->id) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Belum ada log percakapan</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($logs->hasPages())
    <div class="card-footer">
        {{ $logs->links() }}
    </div>
    @endif
</div>

<!-- Modal Clear -->
<div class="modal fade" id="clearModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="bi bi-trash me-2"></i>Hapus Log</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">Pilih log yang ingin dihapus:</div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="clearType" id="clearOld" value="old" checked>
                    <label class="form-check-label" for="clearOld">Hapus log lebih dari 30 hari</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="clearType" id="clearAll" value="all">
                    <label class="form-check-label text-danger" for="clearAll">Hapus SEMUA log (tidak dapat dibatalkan)</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="confirmClear">Hapus Log</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
flatpickr('.flatpickr', { dateFormat: 'Y-m-d', locale: { firstDayOfWeek: 1 } });

document.getElementById('confirmClear').addEventListener('click', async function() {
    const type = document.querySelector('input[name="clearType"]:checked').value;
    const res = await fetch('{{ route("cms.log.clear") }}', {
        method: 'DELETE',
        headers: {'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: JSON.stringify({ type })
    });
    const data = await res.json();
    bootstrap.Modal.getInstance(document.getElementById('clearModal')).hide();
    showToast(data.message);
    setTimeout(() => location.reload(), 1000);
});
</script>
@endpush
