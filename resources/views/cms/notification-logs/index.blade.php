@extends('cms.layouts.app')
@section('title', 'Log Notifikasi WA')
@section('breadcrumb')
    <li class="breadcrumb-item active">Log Notifikasi WA</li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold">Log Notifikasi WA</h4>
</div>

<!-- Stats -->
<div class="row g-2 mb-3">
    @foreach(['queued'=>'warning','sent'=>'success','failed'=>'danger'] as $s=>$color)
    <div class="col-auto">
        <div class="card px-3 py-2">
            <div class="small text-muted">{{ ucfirst($s) }}</div>
            <div class="fw-bold text-{{ $color }}">{{ $stats[$s] ?? 0 }}</div>
        </div>
    </div>
    @endforeach
</div>

<!-- Filter -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
            <select name="status" class="form-select form-select-sm" style="width:auto">
                <option value="all">Semua Status</option>
                <option value="queued"  {{ request('status')==='queued'?'selected':'' }}>Queued</option>
                <option value="sent"    {{ request('status')==='sent'?'selected':'' }}>Sent</option>
                <option value="failed"  {{ request('status')==='failed'?'selected':'' }}>Failed</option>
            </select>
            <input type="text" name="date_from" class="form-control form-control-sm flatpickr" style="max-width:130px"
                placeholder="Dari tanggal" value="{{ request('date_from') }}">
            <input type="text" name="date_to" class="form-control form-control-sm flatpickr" style="max-width:130px"
                placeholder="Sampai tanggal" value="{{ request('date_to') }}">
            <button class="btn btn-sm btn-primary"><i class="bi bi-search"></i> Filter</button>
            @if(request()->hasAny(['status','date_from','date_to']))
                <a href="{{ route('cms.notification-logs.index') }}" class="btn btn-sm btn-light">Reset</a>
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
                        <th>Template</th>
                        <th>Penerima</th>
                        <th>Nomor WA</th>
                        <th>Status</th>
                        <th>Terkirim</th>
                        <th>Dibuat</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td class="text-muted">{{ $log->id }}</td>
                        <td>
                            @if($log->template)
                                <div class="fw-semibold">{{ $log->template->name }}</div>
                                <div class="text-muted" style="font-size:.75rem"><code>{{ $log->template->trigger_key }}</code></div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>{{ $log->target?->name ?? '—' }}</td>
                        <td class="font-monospace">
                            {{ substr($log->phone_number, 0, 5) }}****{{ substr($log->phone_number, -4) }}
                        </td>
                        <td>
                            @php
                                $badge = match($log->status) {
                                    'sent'   => 'bg-success',
                                    'failed' => 'bg-danger',
                                    default  => 'bg-warning text-dark',
                                };
                            @endphp
                            <span class="badge {{ $badge }}">{{ ucfirst($log->status) }}</span>
                        </td>
                        <td class="text-muted text-nowrap">{{ $log->sent_at?->format('d/m H:i') ?? '—' }}</td>
                        <td class="text-muted text-nowrap">{{ $log->created_at->format('d/m H:i') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">Belum ada log notifikasi</td>
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
@endsection

@push('scripts')
<script>
flatpickr('.flatpickr', { dateFormat: 'Y-m-d', locale: { firstDayOfWeek: 1 } });
</script>
@endpush
