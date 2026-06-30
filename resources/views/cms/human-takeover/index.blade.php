@extends('cms.layouts.app')
@section('title', 'Jeda Percakapan')
@section('breadcrumb')
    <li class="breadcrumb-item active">Jeda Percakapan</li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1 fw-bold">Human Takeover — Jeda Percakapan</h4>
        <p class="text-muted small mb-0">Saat owner membalas manual dari WA, bot otomatis diam. Halaman ini untuk memantau dan mengontrol jeda secara manual.</p>
    </div>
    @if($active->count() > 0)
        <span class="badge bg-warning text-dark fs-6">{{ $active->count() }} aktif</span>
    @else
        <span class="badge bg-success fs-6">Tidak ada jeda aktif</span>
    @endif
</div>

{{-- Tabel Jeda Aktif --}}
<div class="card mb-4">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-pause-circle text-warning"></i>
        <span>Sedang Dijeda</span>
    </div>
    <div class="card-body p-0">
        @if($active->isEmpty())
        <div class="text-center py-5 text-muted">
            <i class="bi bi-check-circle fs-1 d-block mb-2 opacity-25"></i>
            Bot aktif untuk semua kontak — tidak ada jeda berlangsung.
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Nomor</th>
                        <th>Nama</th>
                        <th>Jeda Hingga</th>
                        <th class="text-center">Sisa Waktu</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($active as $pause)
                    <tr>
                        <td class="font-monospace">{{ $pause->from_number }}</td>
                        <td>{{ $pause->contact_name ?? '—' }}</td>
                        <td class="text-muted small">{{ $pause->paused_until->format('d M Y H:i') }}</td>
                        <td class="text-center">
                            <span class="badge bg-warning text-dark countdown-badge"
                                  data-until="{{ $pause->paused_until->timestamp }}">
                                menghitung...
                            </span>
                        </td>
                        <td>
                            <form method="POST"
                                  action="{{ route('cms.human-takeover.resume', $pause->from_number) }}"
                                  onsubmit="return confirm('Akhiri jeda untuk {{ $pause->from_number }}?')">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-outline-success">
                                    <i class="bi bi-play-circle me-1"></i>Resume
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

{{-- Form Jeda Manual --}}
<div class="card mb-4">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-hand-raised text-primary"></i>
        <span>Jeda Manual</span>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">Gunakan ini jika Anda akan membalas customer secara manual tapi belum sempat membalas dari WA (sehingga bot belum otomatis diam).</p>
        <form method="POST" action="{{ route('cms.human-takeover.pause') }}" class="row g-3 align-items-end">
            @csrf
            <div class="col-md-5">
                <label class="form-label small fw-semibold">Nomor WA Customer</label>
                <input type="text" name="number" class="form-control @error('number') is-invalid @enderror"
                       placeholder="628xxx... (tanpa + atau spasi)"
                       value="{{ old('number') }}" required>
                @error('number')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Durasi Jeda</label>
                <select name="minutes" class="form-select">
                    <option value="10" {{ $pauseMinutes == 10 ? 'selected' : '' }}>10 menit</option>
                    <option value="30" {{ $pauseMinutes == 30 ? 'selected' : '' }}>30 menit</option>
                    <option value="60">60 menit</option>
                    <option value="120">2 jam</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-pause-fill me-1"></i>Jeda Sekarang
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Log Takeover Terbaru --}}
<div class="card">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-clock-history text-muted"></i>
        <span>Log Human Takeover Terbaru</span>
    </div>
    <div class="card-body p-0">
        @if($recentLogs->isEmpty())
        <div class="text-center py-4 text-muted small">Belum ada log human takeover.</div>
        @else
        <div class="table-responsive">
            <table class="table table-hover mb-0 small">
                <thead class="table-light">
                    <tr>
                        <th>Waktu</th>
                        <th>Nomor</th>
                        <th>Nama</th>
                        <th>Pesan Owner</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentLogs as $log)
                    <tr>
                        <td class="text-muted text-nowrap">{{ $log->responded_at?->format('d M H:i') }}</td>
                        <td class="font-monospace">{{ $log->from_number }}</td>
                        <td>{{ $log->contact_name ?? '—' }}</td>
                        <td class="text-muted" style="max-width:300px">
                            <span class="text-truncate d-inline-block" style="max-width:280px">
                                {{ $log->message_in ?? '—' }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
function updateCountdowns() {
    document.querySelectorAll('.countdown-badge').forEach(el => {
        const until = parseInt(el.dataset.until, 10);
        const diff  = until - Math.floor(Date.now() / 1000);
        if (diff <= 0) {
            el.textContent = 'Berakhir';
            el.classList.replace('bg-warning', 'bg-secondary');
            return;
        }
        const m = Math.floor(diff / 60);
        const s = diff % 60;
        el.textContent = m + ':' + String(s).padStart(2, '0');
    });
}
updateCountdowns();
setInterval(updateCountdowns, 1000);
</script>
@endpush
