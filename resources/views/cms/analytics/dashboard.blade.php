@extends('cms.layouts.app')
@section('title', 'Customer Analytics')
@section('breadcrumb')
    <li class="breadcrumb-item active">Customer Analytics</li>
@endsection

@push('styles')
<style>
.metric-card { border-left: 4px solid var(--brand-primary); }
.metric-card .metric-value { font-size: 2rem; font-weight: 700; line-height: 1; }
.metric-card .metric-label { font-size: .78rem; color: #6c757d; text-transform: uppercase; letter-spacing: .04em; }
.metric-card .metric-sub { font-size: .82rem; margin-top: 4px; }
.topic-bar { height: 8px; border-radius: 4px; background: var(--brand-primary); transition: width .4s; }
.faq-gap-item { border-left: 3px solid #dc3545; padding-left: 10px; margin-bottom: 8px; }
.faq-gap-count { font-size: .75rem; font-weight: 700; color: #dc3545; }
.segment-pill { font-size: .75rem; padding: 2px 8px; border-radius: 10px; }
.period-btn { font-size: .8rem; }
.period-btn.active { background: var(--brand-primary); color: #fff; border-color: var(--brand-primary); }
.unanalysed-banner { background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 10px 16px; font-size: .85rem; }
</style>
@endpush

@section('content')

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-0 fw-bold">Customer Analytics</h4>
        <small class="text-muted">Analisis perilaku customer dari percakapan WhatsApp · {{ $from }} s/d {{ $to }}</small>
    </div>
    <div class="d-flex gap-2 align-items-center">
        {{-- Period selector --}}
        <div class="btn-group btn-group-sm">
            @foreach([['7','7 Hari'],['14','14 Hari'],['30','30 Hari']] as [$val,$label])
                <a href="{{ route('cms.analytics.index', ['period' => $val]) }}"
                   class="btn btn-outline-secondary period-btn {{ $period == $val ? 'active' : '' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>
</div>

{{-- Unanalysed banner --}}
@if($unanalysed > 0)
<div class="unanalysed-banner mb-3">
    <i class="bi bi-info-circle me-2"></i>
    <strong>{{ $unanalysed }} sesi hari ini belum dianalisis.</strong>
    Analisis berjalan otomatis tiap malam pukul 02.00, atau jalankan manual:
    <code class="ms-1">php artisan analytics:analyse --date={{ now()->toDateString() }}</code>
</div>
@endif

{{-- Summary cards --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card metric-card h-100">
            <div class="card-body">
                <div class="metric-label">Total Sesi</div>
                <div class="metric-value text-dark">{{ number_format($totalSessions) }}</div>
                <div class="metric-sub text-muted">{{ $uniqueCustomers }} customer unik</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card metric-card h-100" style="border-color:#198754">
            <div class="card-body">
                <div class="metric-label">Bot Effectiveness</div>
                <div class="metric-value text-success">{{ $effectiveness }}%</div>
                <div class="metric-sub text-muted">Sesi yang terjawab sempurna</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card metric-card h-100" style="border-color:#0d6efd">
            <div class="card-body">
                <div class="metric-label">Avg Purchase Intent</div>
                <div class="metric-value text-primary">{{ $avgIntent }}<span style="font-size:1rem">/10</span></div>
                <div class="metric-sub text-muted">Rata-rata niat beli customer</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card metric-card h-100" style="border-color:{{ $sentiment['negative'] > $sentiment['positive'] ? '#dc3545' : '#198754' }}">
            <div class="card-body">
                <div class="metric-label">Sentiment Positif</div>
                <div class="metric-value" style="color:{{ $sentiment['negative'] > $sentiment['positive'] ? '#dc3545' : '#198754' }}">
                    {{ $totalSessions > 0 ? round(($sentiment['positive'] / $totalSessions) * 100) : 0 }}%
                </div>
                <div class="metric-sub text-muted">
                    <span class="text-danger">{{ $sentiment['negative'] }} negatif</span> ·
                    <span class="text-secondary">{{ $sentiment['neutral'] }} netral</span>
                </div>
            </div>
        </div>
    </div>
</div>

@if($totalSessions === 0)
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-bar-chart-line" style="font-size:3rem;color:#dee2e6"></i>
        <p class="mt-3 text-muted">Belum ada data analitik untuk periode ini.<br>
        Pastikan command <code>analytics:analyse</code> sudah dijalankan.</p>
    </div>
</div>
@else

<div class="row g-3 mb-4">
    {{-- Trend chart --}}
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-graph-up me-2 text-primary"></i>Tren Harian</span>
            </div>
            <div class="card-body">
                <canvas id="trendChart" height="110"></canvas>
            </div>
        </div>
    </div>

    {{-- Sentiment donut --}}
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-emoji-smile me-2 text-success"></i>Distribusi Sentiment</div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center">
                <canvas id="sentimentChart" height="180" style="max-width:180px"></canvas>
                <div class="mt-3 d-flex gap-3 justify-content-center flex-wrap">
                    <span><span class="badge bg-success">Positif</span> {{ $sentiment['positive'] }}</span>
                    <span><span class="badge bg-secondary">Netral</span> {{ $sentiment['neutral'] }}</span>
                    <span><span class="badge bg-danger">Negatif</span> {{ $sentiment['negative'] }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    {{-- Top topics --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-fire me-2 text-danger"></i>Top Topik Percakapan</div>
            <div class="card-body">
                @php $maxCount = $topTopics->max('count') ?: 1; @endphp
                @forelse($topTopics as $t)
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span style="font-size:.85rem">{{ $t['topic'] }}</span>
                        <span class="badge bg-primary rounded-pill">{{ $t['count'] }}</span>
                    </div>
                    <div class="topic-bar" style="width:{{ round(($t['count']/$maxCount)*100) }}%"></div>
                </div>
                @empty
                <p class="text-muted text-center py-3">Belum ada data topik</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- FAQ Gaps --}}
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-exclamation-triangle me-2 text-danger"></i>FAQ Gap — Perlu Ditambahkan</span>
                <span class="badge bg-danger">{{ $faqGaps->count() }} isu</span>
            </div>
            <div class="card-body">
                @forelse($faqGaps as $gap)
                <div class="faq-gap-item">
                    <div class="d-flex justify-content-between align-items-start">
                        <span style="font-size:.85rem">{{ $gap['question'] }}</span>
                        <span class="faq-gap-count ms-2">{{ $gap['count'] }}x</span>
                    </div>
                </div>
                @empty
                <p class="text-muted text-center py-3"><i class="bi bi-check-circle text-success"></i> Tidak ada FAQ gap!</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    {{-- Customer segments --}}
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-people me-2 text-info"></i>Customer Segments</div>
            <div class="card-body">
                <canvas id="segmentChart" height="200"></canvas>
            </div>
        </div>
    </div>

    {{-- Channel sources --}}
    <div class="col-lg-3">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-megaphone me-2 text-warning"></i>Sumber Channel</div>
            <div class="card-body">
                @forelse($channels as $channel => $count)
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span style="font-size:.85rem">{{ $channel }}</span>
                    <span class="badge bg-warning text-dark">{{ $count }}</span>
                </div>
                @empty
                <p class="text-muted text-center py-3 small">Belum ada data channel</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Intent score trend --}}
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-currency-dollar me-2 text-success"></i>Purchase Intent Trend</div>
            <div class="card-body">
                <canvas id="intentChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

{{-- Recent analyses table --}}
<div class="card">
    <div class="card-header"><i class="bi bi-table me-2"></i>Analisis Terbaru</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>Customer</th>
                        <th>Topik</th>
                        <th>Sentiment</th>
                        <th>Segment</th>
                        <th>Intent</th>
                        <th>FAQ Gap</th>
                        <th>Resolved</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recent as $r)
                    <tr>
                        <td class="text-nowrap">{{ $r->session_date->format('d M') }}</td>
                        <td>
                            <div style="font-size:.82rem">{{ $r->contact_name ?: 'Unknown' }}</div>
                            <div class="text-muted" style="font-size:.72rem">{{ substr($r->phone_number, 0, 6) }}***</div>
                        </td>
                        <td style="max-width:180px;font-size:.82rem">{{ $r->topic }}</td>
                        <td>
                            @if($r->sentiment === 'positive')
                                <span class="badge bg-success">Positif</span>
                            @elseif($r->sentiment === 'negative')
                                <span class="badge bg-danger">Negatif</span>
                            @else
                                <span class="badge bg-secondary">Netral</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $r->segment_color }} segment-pill">
                                {{ $r->segment_label }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-{{ $r->intent_badge }}">{{ $r->purchase_intent_score ?? 0 }}/10</span>
                        </td>
                        <td>
                            @if($r->is_faq_gap)
                                <i class="bi bi-exclamation-circle text-danger" title="{{ $r->faq_gap_question }}"></i>
                            @else
                                <i class="bi bi-check text-success"></i>
                            @endif
                        </td>
                        <td>
                            @if($r->resolved)
                                <i class="bi bi-check-circle text-success"></i>
                            @else
                                <i class="bi bi-x-circle text-danger"></i>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
const brandGreen  = '#1D9E75';
const brandBlue   = '#0d6efd';
const brandRed    = '#dc3545';
const brandGray   = '#6c757d';
const brandYellow = '#ffc107';

@if($totalSessions > 0)

// ── Trend chart ──────────────────────────────────────────────────────────────
new Chart(document.getElementById('trendChart'), {
    type: 'line',
    data: {
        labels: @json($trendDays->toArray()),
        datasets: [
            {
                label: 'Total Sesi',
                data: @json($trendSessions->toArray()),
                borderColor: brandGreen, backgroundColor: brandGreen + '22',
                fill: true, tension: 0.4, pointRadius: 3,
            },
            {
                label: 'Sentimen Positif',
                data: @json($trendPositive->toArray()),
                borderColor: brandBlue, backgroundColor: 'transparent',
                tension: 0.4, pointRadius: 3, borderDash: [4,4],
            },
            {
                label: 'Sentimen Negatif',
                data: @json($trendNegative->toArray()),
                borderColor: brandRed, backgroundColor: 'transparent',
                tension: 0.4, pointRadius: 3, borderDash: [2,2],
            },
        ],
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } },
});

// ── Sentiment donut ───────────────────────────────────────────────────────────
new Chart(document.getElementById('sentimentChart'), {
    type: 'doughnut',
    data: {
        labels: ['Positif', 'Netral', 'Negatif'],
        datasets: [{ data: [{{ $sentiment['positive'] }}, {{ $sentiment['neutral'] }}, {{ $sentiment['negative'] }}],
            backgroundColor: [brandGreen, brandGray, brandRed], borderWidth: 2 }],
    },
    options: { responsive: true, plugins: { legend: { display: false } }, cutout: '65%' },
});

// ── Segment chart ─────────────────────────────────────────────────────────────
@php
$segLabels = array_map(fn($k) => \App\Models\ConversationAnalysis::SEGMENTS[$k] ?? $k, array_keys($segments));
$segValues = array_values($segments);
$segColors = ['#198754','#0dcaf0','#ffc107','#0d6efd','#dc3545','#6c757d'];
@endphp
new Chart(document.getElementById('segmentChart'), {
    type: 'bar',
    data: {
        labels: @json($segLabels),
        datasets: [{ label: 'Customer', data: @json($segValues),
            backgroundColor: @json(array_slice($segColors, 0, count($segValues))), borderWidth: 0 }],
    },
    options: { responsive: true, indexAxis: 'y',
        plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true } } },
});

// ── Intent trend ──────────────────────────────────────────────────────────────
new Chart(document.getElementById('intentChart'), {
    type: 'line',
    data: {
        labels: @json($trendDays->toArray()),
        datasets: [{
            label: 'Avg Intent Score',
            data: @json($trendIntent->toArray()),
            borderColor: brandYellow, backgroundColor: brandYellow + '33',
            fill: true, tension: 0.4, pointRadius: 3,
        }],
    },
    options: { responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, max: 10 } } },
});

@endif
</script>
@endpush
