@extends('cms.layouts.app')
@section('title', 'Detail Log #' . $log->id)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('cms.log.index') }}" class="text-decoration-none" style="color:var(--brand-primary)">Log Percakapan</a></li>
    <li class="breadcrumb-item active">Detail #{{ $log->id }}</li>
@endsection

@push('styles')
<style>
.chat-area { background:#e5ddd5; border-radius:12px; padding:20px; min-height:250px; }
.bubble-in, .bubble-out { max-width:80%; border-radius:8px; padding:10px 14px; margin-bottom:12px; white-space:pre-wrap; word-break:break-word; font-size:.9rem; line-height:1.5; position:relative; }
.bubble-in  { background:#fff; border-radius:0 8px 8px 8px; margin-right:auto; box-shadow:0 1px 2px rgba(0,0,0,.12); }
.bubble-out { background:#dcf8c6; border-radius:8px 8px 0 8px; margin-left:auto; box-shadow:0 1px 2px rgba(0,0,0,.12); }
.bubble-label { font-size:.72rem; font-weight:600; margin-bottom:4px; }
.bubble-time  { font-size:.7rem; color:rgba(0,0,0,.45); text-align:right; margin-top:4px; }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold">Detail Log #{{ $log->id }}</h4>
    <a href="{{ route('cms.log.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
</div>

<div class="row g-3">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><i class="bi bi-chat-square-dots me-2"></i>Percakapan</div>
            <div class="card-body">
                <div class="chat-area">
                    <!-- Pesan user -->
                    <div>
                        <div class="bubble-label text-muted">
                            {{ $log->display_contact }}
                        </div>
                        <div class="bubble-in">{{ $log->message_in }}</div>
                    </div>
                    <!-- Balasan bot -->
                    @if($log->message_out)
                    <div class="text-end">
                        <div class="bubble-label text-muted">MinFara AI 🤖</div>
                        <div class="bubble-out">{{ $log->message_out }}</div>
                        <div class="bubble-time">{{ $log->responded_at?->format('H:i') }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-person-lines-fill me-2"></i>Info Pengirim</div>
            <div class="card-body">
                <table class="table table-borderless table-sm mb-0">
                    @if($log->contact_name)
                    <tr>
                        <td class="text-muted small">Nama</td>
                        <td class="fw-semibold small">{{ $log->contact_name }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="text-muted small">Nomor WA</td>
                        <td class="fw-semibold small">
                            <a href="https://wa.me/{{ $log->from_number }}" target="_blank" class="text-decoration-none">
                                {{ $log->display_number }} <i class="bi bi-box-arrow-up-right small"></i>
                            </a>
                        </td>
                    </tr>
                    @if($log->ip_address)
                    <tr>
                        <td class="text-muted small">IP Address</td>
                        <td class="small font-monospace">{{ $log->ip_address }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="text-muted small">Mode</td>
                        <td><span class="badge badge-{{ $log->mode }}">{{ str_replace('_',' ',$log->mode) }}</span></td>
                    </tr>
                    @if($log->ai_tokens_used)
                    <tr>
                        <td class="text-muted small">Token AI</td>
                        <td class="small">{{ number_format($log->ai_tokens_used) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="text-muted small">Waktu</td>
                        <td class="small">{{ $log->responded_at?->format('d M Y, H:i:s') ?? '—' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted small">ID Log</td>
                        <td class="small text-muted">#{{ $log->id }}</td>
                    </tr>
                </table>
            </div>
        </div>

        @if($log->contact_name)
        <div class="card mt-3">
            <div class="card-body py-2">
                <a href="{{ route('cms.log.index', ['search' => $log->from_number]) }}"
                   class="btn btn-sm btn-outline-primary w-100">
                    <i class="bi bi-search me-1"></i>Lihat semua percakapan nomor ini
                </a>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
