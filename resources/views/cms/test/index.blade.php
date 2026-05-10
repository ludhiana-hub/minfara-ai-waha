@extends('cms.layouts.app')
@section('title', 'Test Kirim Pesan')
@section('breadcrumb')
    <li class="breadcrumb-item active">Test Kirim Pesan</li>
@endsection

@section('content')
<div class="mb-4">
    <h4 class="mb-0 fw-bold">Test Kirim Pesan WhatsApp</h4>
</div>

<div class="row g-3">
    <!-- Form Kirim -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-send me-2"></i>Kirim Pesan Manual</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nomor WA Tujuan</label>
                    <div class="input-group">
                        <span class="input-group-text">+</span>
                        <input type="text" id="phoneInput" class="form-control"
                            placeholder="628123456789" value="">
                    </div>
                    <div class="form-text">Format: 628xxx tanpa + atau spasi</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Pesan</label>
                    <textarea id="messageInput" rows="6" class="form-control"
                        placeholder="Tulis pesan di sini..."></textarea>
                </div>
                <button class="btn btn-primary w-100" id="sendBtn" onclick="sendMessage()">
                    <i class="bi bi-send me-1"></i>Kirim via WAHA
                </button>
            </div>
        </div>

        <!-- Simulasi FAQ -->
        <div class="card mt-3">
            <div class="card-header"><i class="bi bi-journal-text me-2"></i>Kirim dari Menu FAQ</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Pilih Menu FAQ</label>
                    <select class="form-select" id="faqSelect" onchange="loadFaqContent()">
                        <option value="">— Pilih menu —</option>
                        @foreach($faqs as $faq)
                        <option value="{{ $faq->id }}" data-content="{{ htmlspecialchars($faq->content) }}">
                            [{{ $faq->command }}] {{ $faq->title }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div id="faqPreview" class="d-none">
                    <div class="bg-light rounded p-3 mb-3 small font-monospace" id="faqContent" style="white-space:pre-wrap;max-height:150px;overflow-y:auto"></div>
                    <button class="btn btn-success btn-sm w-100" onclick="sendFaqPreview()">
                        <i class="bi bi-send me-1"></i>Kirim Preview Ini
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Response Panel -->
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-terminal me-2"></i>Response Panel</div>
            <div class="card-body">
                <div id="responsePanel" class="text-muted text-center py-5">
                    <i class="bi bi-send fs-2 d-block mb-2"></i>
                    Kirim pesan untuk melihat response
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function loadFaqContent() {
    const sel = document.getElementById('faqSelect');
    const opt = sel.options[sel.selectedIndex];
    const preview = document.getElementById('faqPreview');
    const content = document.getElementById('faqContent');
    if (opt.value) {
        content.textContent = opt.dataset.content;
        preview.classList.remove('d-none');
    } else {
        preview.classList.add('d-none');
    }
}

function sendFaqPreview() {
    const content = document.getElementById('faqContent').textContent;
    document.getElementById('messageInput').value = content;
    sendMessage();
}

async function sendMessage() {
    const phone   = document.getElementById('phoneInput').value.trim();
    const message = document.getElementById('messageInput').value.trim();

    if (!phone || !message) {
        showToast('Nomor WA dan pesan wajib diisi', 'error');
        return;
    }

    const btn = document.getElementById('sendBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Mengirim...';

    const panel = document.getElementById('responsePanel');
    panel.innerHTML = '<div class="text-center text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Mengirim pesan...</div>';

    const start = Date.now();
    try {
        const res = await fetch('{{ route("cms.test.send") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ phone, message }),
        });
        const data = await res.json();
        const elapsed = Date.now() - start;

        panel.innerHTML = `
            <div class="alert ${data.success ? 'alert-success' : 'alert-danger'} mb-3">
                <strong>${data.success ? '✅ Berhasil!' : '❌ Gagal!'}</strong> ${data.message}
            </div>
            <div class="mb-2 small text-muted">
                <i class="bi bi-clock me-1"></i>Response time: <strong>${data.elapsed ?? elapsed}ms</strong>
                &nbsp;|&nbsp; HTTP Status: <strong>${data.status}</strong>
            </div>
            <div class="bg-dark text-light rounded p-3 small font-monospace" style="overflow:auto;max-height:300px">
                <pre class="mb-0">${JSON.stringify(data.body, null, 2)}</pre>
            </div>`;

        showToast(data.message, data.success ? 'success' : 'error');
    } catch(e) {
        panel.innerHTML = `<div class="alert alert-danger">Error: ${e.message}</div>`;
        showToast('Terjadi kesalahan: ' + e.message, 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-send me-1"></i>Kirim via WAHA';
    }
}
</script>
@endpush
