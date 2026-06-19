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

/* ── Analisis Sekarang button ─────────────────────────────────── */
.btn-analyze {
    background: linear-gradient(135deg, #1D9E75, #0d6efd);
    color: #fff !important;
    border: none;
    font-weight: 600;
    font-size: .85rem;
    padding: 8px 18px;
    border-radius: 8px;
    transition: all .25s;
    white-space: nowrap;
}
.btn-analyze:hover {
    background: linear-gradient(135deg, #178a65, #0b5ed7);
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(13,110,253,.3);
}
.btn-analyze:active { transform: translateY(0); box-shadow: none; }
.btn-analyze:disabled { opacity: .65; transform: none; cursor: not-allowed; }

/* ── AI Loader Overlay ────────────────────────────────────────── */
.ai-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.78);
    backdrop-filter: blur(6px);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}
.ai-overlay.show { display: flex; }

.ai-loader-card {
    background: #fff;
    border-radius: 20px;
    padding: 44px 48px 40px;
    text-align: center;
    max-width: 440px;
    width: 92%;
    box-shadow: 0 24px 64px rgba(0,0,0,.45);
    animation: card-pop .3s cubic-bezier(.34,1.56,.64,1);
}
@keyframes card-pop {
    from { opacity:0; transform: scale(.88); }
    to   { opacity:1; transform: scale(1); }
}

/* Pulsing orb */
.ai-orb {
    width: 76px; height: 76px;
    border-radius: 50%;
    background: linear-gradient(135deg, #1D9E75, #0d6efd);
    margin: 0 auto 28px;
    display: flex; align-items: center; justify-content: center;
    font-size: 2rem; color: #fff;
    position: relative;
    animation: orb-breathe 2.5s ease-in-out infinite;
}
.ai-orb::before, .ai-orb::after {
    content: '';
    position: absolute; border-radius: 50%;
    border: 2px solid rgba(13,110,253,.35);
    animation: ring-expand 2.5s ease-out infinite;
}
.ai-orb::before { width: 96px; height: 96px; animation-delay: 0s; }
.ai-orb::after  { width: 118px; height: 118px; animation-delay: .7s; }
@keyframes orb-breathe {
    0%,100% { box-shadow: 0 0 0 0 rgba(13,110,253,.3); }
    50%      { box-shadow: 0 0 22px 6px rgba(13,110,253,.18); }
}
@keyframes ring-expand {
    0%   { transform: scale(.85); opacity: .9; }
    100% { transform: scale(1.9); opacity: 0; }
}

/* Progress bar */
.ai-progress {
    height: 5px; border-radius: 3px;
    background: #e9ecef; overflow: hidden;
    margin: 18px 0 16px;
}
.ai-progress-fill {
    height: 100%; border-radius: 3px; width: 4%;
    background: linear-gradient(90deg, #1D9E75, #0d6efd, #6f42c1, #0d6efd, #1D9E75);
    background-size: 300% 100%;
    transition: width .9s ease;
    animation: shimmer 2.2s linear infinite;
}
@keyframes shimmer {
    0%   { background-position: 100% center; }
    100% { background-position: -100% center; }
}

/* Thinking dots */
.thinking-dots { margin-top: 12px; }
.thinking-dots span {
    display: inline-block;
    width: 9px; height: 9px; border-radius: 50%;
    background: linear-gradient(135deg, #1D9E75, #0d6efd);
    margin: 0 4px;
    animation: dot-bounce 1.5s ease-in-out infinite;
}
.thinking-dots span:nth-child(2) { animation-delay: .22s; }
.thinking-dots span:nth-child(3) { animation-delay: .44s; }
@keyframes dot-bounce {
    0%,60%,100% { transform: translateY(0); opacity: .5; }
    30%          { transform: translateY(-10px); opacity: 1; }
}
</style>
@endpush

@section('content')

{{-- Header --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold">Customer Analytics</h4>
        <small class="text-muted">Analisis perilaku customer dari percakapan WhatsApp · {{ $from }} s/d {{ $to }}</small>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap justify-content-end">
        {{-- Period selector --}}
        <div class="btn-group btn-group-sm">
            @foreach([['7','7 Hari'],['14','14 Hari'],['30','30 Hari']] as [$val,$label])
                <a href="{{ route('cms.analytics.index', ['period' => $val]) }}"
                   class="btn btn-outline-secondary period-btn {{ $period == $val ? 'active' : '' }}">{{ $label }}</a>
            @endforeach
        </div>
        {{-- Analisis Sekarang --}}
        <button class="btn btn-analyze" id="btnAnalyze" onclick="runAnalysis()">
            <i class="bi bi-stars me-1"></i>Analisis Sekarang
            @if($unanalysed > 0)
                <span class="badge bg-white text-dark ms-1" style="font-size:.72rem">{{ $unanalysed }} baru</span>
            @endif
        </button>
    </div>
</div>

{{-- Unanalysed notice (subtle) --}}
@if($unanalysed > 0)
<div class="d-flex align-items-center gap-2 mb-3 px-3 py-2 rounded-3"
     style="background:#fff8e1;border:1px solid #ffe082;font-size:.83rem">
    <i class="bi bi-clock-history text-warning"></i>
    <span><strong>{{ $unanalysed }} sesi hari ini</strong> belum dianalisis — klik <em>Analisis Sekarang</em> atau tunggu otomatis pukul 02.00.</span>
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

{{-- ── AI Loader Overlay ──────────────────────────────────────────────────── --}}
<div class="ai-overlay" id="aiOverlay">
    <div class="ai-loader-card">

        {{-- Orb icon --}}
        <div class="ai-orb"><i class="bi bi-stars"></i></div>

        {{-- Phase 1 — Processing --}}
        <div id="phaseProcessing">
            <h5 class="fw-bold mb-1">AI Sedang Menganalisis</h5>
            <p class="text-muted mb-0" style="font-size:.83rem">Sedang menganalisis percakapan — mohon tunggu</p>
            <div class="ai-progress"><div class="ai-progress-fill" id="loaderBar"></div></div>
            <p class="fw-semibold mb-2" style="color:#0d6efd;font-size:.9rem" id="loaderMsg">Memulai analisis...</p>
            <div class="thinking-dots"><span></span><span></span><span></span></div>
        </div>

        {{-- Phase 2 — Selesai (hasil nyata) --}}
        <div id="phaseQueued" style="display:none">
            <div class="mb-3" id="doneIcon"><i class="bi bi-check-circle-fill text-success" style="font-size:2.4rem"></i></div>
            <h5 class="fw-bold mb-1" id="doneTitle">Analisis Selesai!</h5>
            <p class="text-muted mb-4" style="font-size:.85rem" id="doneMsg">Berhasil menganalisis percakapan.</p>
            <div class="d-flex gap-2 justify-content-center">
                <button class="btn btn-primary btn-sm px-4" id="doneReloadBtn" onclick="window.location.reload()">
                    <i class="bi bi-arrow-clockwise me-1"></i>Muat Ulang
                </button>
                <button class="btn btn-outline-secondary btn-sm" onclick="closeLoader()">Tutup</button>
            </div>
        </div>

        {{-- Phase 3 — Error --}}
        <div id="phaseError" style="display:none">
            <div class="mb-3"><i class="bi bi-x-circle-fill text-danger" style="font-size:2.4rem"></i></div>
            <h5 class="fw-bold mb-1">Gagal Memulai Analisis</h5>
            <p class="text-muted mb-4" style="font-size:.85rem" id="loaderErrMsg">Terjadi kesalahan. Coba lagi.</p>
            <button class="btn btn-outline-secondary btn-sm px-4" onclick="closeLoader()">Tutup</button>
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
const brandGreen  = '#1D9E75';
const brandBlue   = '#0d6efd';
const brandRed    = '#dc3545';
const brandGray   = '#6c757d';
const brandYellow = '#ffc107';

// ── Analisis Sekarang ─────────────────────────────────────────────────────────
const AI_STEPS = [
    'Memuat percakapan hari ini...',
    'AI membaca setiap pesan customer...',
    'Mengklasifikasi segmen customer...',
    'Menghitung purchase intent score...',
    'Mendeteksi pertanyaan yang tidak terjawab...',
    'Menganalisis sentimen percakapan...',
    'Mendeteksi sumber channel marketing...',
    'Menyusun laporan analitik...',
    'Hampir selesai...',
];

function runAnalysis() {
    const overlay  = document.getElementById('aiOverlay');
    const bar      = document.getElementById('loaderBar');
    const msgEl    = document.getElementById('loaderMsg');
    const btnEl    = document.getElementById('btnAnalyze');

    btnEl.disabled = true;
    overlay.classList.add('show');
    document.getElementById('phaseProcessing').style.display = 'block';
    document.getElementById('phaseQueued').style.display     = 'none';
    document.getElementById('phaseError').style.display      = 'none';

    let step = 0, progress = 4;
    msgEl.textContent = AI_STEPS[0];
    bar.style.width   = progress + '%';

    const stepInterval = setInterval(() => {
        step = (step + 1) % AI_STEPS.length;
        msgEl.textContent = AI_STEPS[step];
        progress = Math.min(progress + Math.floor(Math.random() * 10 + 6), 88);
        bar.style.width = progress + '%';
    }, 2800);

    fetch('{{ route('cms.analytics.run') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
        },
        body: JSON.stringify({ date: '{{ now()->toDateString() }}' }),
    })
    .then(r => {
        if (!r.ok) return r.json().then(e => Promise.reject(e));
        return r.json();
    })
    .then(data => {
        clearInterval(stepInterval);
        bar.style.width = '100%';

        const icon  = document.getElementById('doneIcon');
        const title = document.getElementById('doneTitle');
        const msg   = document.getElementById('doneMsg');
        const reloadBtn = document.getElementById('doneReloadBtn');

        if (data.status === 'done') {
            icon.innerHTML  = '<i class="bi bi-check-circle-fill text-success" style="font-size:2.4rem"></i>';
            title.textContent = 'Analisis Selesai!';
            msg.textContent   = data.message || 'Berhasil menganalisis percakapan.';
            reloadBtn.style.display = '';
            // Auto-reload agar data baru langsung tampil
            setTimeout(() => window.location.reload(), 1500);
        } else {
            // empty / ai_failed — tidak ada data baru, jangan reload
            icon.innerHTML  = '<i class="bi bi-exclamation-circle-fill text-warning" style="font-size:2.4rem"></i>';
            title.textContent = data.status === 'empty' ? 'Tidak Ada Percakapan' : 'Analisis Gagal';
            msg.textContent   = data.message || 'Tidak ada yang dianalisis.';
            reloadBtn.style.display = 'none';
        }

        setTimeout(() => {
            document.getElementById('phaseProcessing').style.display = 'none';
            document.getElementById('phaseQueued').style.display     = 'block';
        }, 500);
    })
    .catch(err => {
        clearInterval(stepInterval);
        document.getElementById('phaseProcessing').style.display = 'none';
        document.getElementById('phaseError').style.display      = 'block';
        document.getElementById('loaderErrMsg').textContent =
            (err && err.message) ? err.message : 'Terjadi kesalahan server. Coba lagi.';
        btnEl.disabled = false;
    });
}

function closeLoader() {
    document.getElementById('aiOverlay').classList.remove('show');
    document.getElementById('btnAnalyze').disabled = false;
}

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
