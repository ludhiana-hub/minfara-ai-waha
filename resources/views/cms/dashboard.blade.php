@extends('cms.layouts.app')
@section('title', 'Dashboard')
@section('breadcrumb')
    <li class="breadcrumb-item active">Dashboard</li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold">Dashboard</h4>
    <div class="d-flex gap-2">
        <a href="{{ route('cms.faq.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Tambah Menu FAQ
        </a>
        <a href="{{ route('cms.test.index') }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-send me-1"></i>Test Kirim Pesan
        </a>
    </div>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="text-muted small mb-1">Total Menu FAQ</div>
                <div class="fs-2 fw-bold text-dark">{{ $totalFaq }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100" style="border-left: 4px solid #198754;">
            <div class="card-body">
                <div class="text-muted small mb-1">Menu Aktif / Nonaktif</div>
                <div class="fs-2 fw-bold">
                    <span class="text-success">{{ $activeFaq }}</span>
                    <span class="text-muted fs-5"> / </span>
                    <span class="text-danger">{{ $inactiveFaq }}</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100" style="border-left: 4px solid #0d6efd;">
            <div class="card-body">
                <div class="text-muted small mb-1">Log Hari Ini</div>
                <div class="fs-2 fw-bold text-primary">{{ $todayLogs }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100" style="border-left: 4px solid #fd7e14;">
            <div class="card-body">
                <div class="text-muted small mb-1">Mode FAQ / AI</div>
                <div class="fs-2 fw-bold">
                    <span class="text-success">{{ $modeCounts['faq'] ?? 0 }}</span>
                    <span class="text-muted fs-5"> / </span>
                    <span class="text-primary">{{ $modeCounts['ai'] ?? 0 }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- WAHA Status -->
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-whatsapp me-2 text-success"></i>Status WAHA</span>
                <button class="btn btn-sm btn-light" onclick="checkWahaStatus()">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-3">
                    @if($wahaStatus['connected'])
                        <span class="badge" style="background:var(--brand-wa); font-size:.85rem">🟢 WORKING</span>
                    @else
                        <span class="badge bg-danger" style="font-size:.85rem">🔴 {{ $wahaStatus['status'] }}</span>
                    @endif
                </div>
                @if($wahaStatus['phone'])
                    <div class="text-muted small">
                        <i class="bi bi-phone me-1"></i>
                        {{ str_replace('@c.us', '', $wahaStatus['phone']) }}
                    </div>
                @endif
                <div class="mt-3">
                    <div class="small text-muted mb-1">AI Fallback</div>
                    @if($aiEnabled)
                        <span class="badge bg-success">Aktif</span>
                    @else
                        <span class="badge bg-secondary">Nonaktif</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Log Terbaru -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-clock-history me-2"></i>Log Terbaru</span>
                <a href="{{ route('cms.log.index') }}" class="btn btn-sm btn-outline-secondary">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 small">
                        <thead class="table-light">
                            <tr>
                                <th>Nomor WA</th>
                                <th>Pesan</th>
                                <th>Mode</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentLogs as $log)
                            <tr>
                                <td class="text-nowrap">+{{ substr($log->from_number, 0, -4) }}****</td>
                                <td>{{ Str::limit($log->message_in, 40) }}</td>
                                <td>
                                    <span class="badge badge-{{ $log->mode }}">{{ $log->mode }}</span>
                                </td>
                                <td class="text-nowrap text-muted">
                                    {{ $log->responded_at?->diffForHumans() ?? '-' }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">Belum ada log percakapan</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
