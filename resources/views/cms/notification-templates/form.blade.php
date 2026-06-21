@extends('cms.layouts.app')
@section('title', isset($template) ? 'Edit Template Notifikasi' : 'Tambah Template Notifikasi')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('cms.notification-templates.index') }}" class="text-decoration-none" style="color:var(--brand-primary)">Template Notifikasi WA</a></li>
    <li class="breadcrumb-item active">{{ isset($template) ? 'Edit' : 'Tambah' }}</li>
@endsection

@push('styles')
<style>
.wa-preview {
    background: #e5ddd5;
    border-radius: 8px;
    padding: 16px;
    min-height: 200px;
    font-family: 'Segoe UI', sans-serif;
    font-size: .9rem;
}
.wa-bubble {
    background: #dcf8c6;
    border-radius: 8px 8px 0 8px;
    padding: 8px 12px;
    max-width: 90%;
    margin-left: auto;
    white-space: pre-wrap;
    word-break: break-word;
    box-shadow: 0 1px 2px rgba(0,0,0,.15);
    line-height: 1.5;
}
.var-badge { cursor: pointer; font-family: monospace; font-size: .8rem; }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold">{{ isset($template) ? 'Edit Template Notifikasi' : 'Tambah Template Notifikasi' }}</h4>
    <a href="{{ route('cms.notification-templates.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
</div>

<form method="POST" action="{{ isset($template) ? route('cms.notification-templates.update', $template) : route('cms.notification-templates.store') }}">
    @csrf
    @if(isset($template)) @method('PUT') @endif

    <div class="row g-3">
        <div class="col-md-7">
            <div class="card">
                <div class="card-header">Informasi Template</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Nama Template <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ old('name', $template->name ?? '') }}"
                                placeholder="Contoh: Transaksi Baru" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Trigger Key <span class="text-danger">*</span></label>
                            @php
                                $currentKey = old('trigger_key', $template->trigger_key ?? '');
                                $isCustom   = $currentKey !== '' && !array_key_exists($currentKey, $triggerKeys);
                            @endphp
                            <select id="triggerKeySelect" class="form-select mb-2 @error('trigger_key') is-invalid @enderror"
                                onchange="onTriggerKeyChange(this.value)">
                                <option value="">-- Pilih Trigger Key --</option>
                                @foreach($triggerKeys as $key => $label)
                                    <option value="{{ $key }}" {{ (!$isCustom && $currentKey === $key) ? 'selected' : '' }}>
                                        {{ $label }} — {{ $key }}
                                    </option>
                                @endforeach
                                <option value="__custom__" {{ $isCustom ? 'selected' : '' }}>Custom (isi manual)...</option>
                            </select>
                            <input type="text" name="trigger_key" id="triggerKeyInput"
                                class="form-control font-monospace @error('trigger_key') is-invalid @enderror"
                                value="{{ $currentKey }}"
                                placeholder="Contoh: transaction.created"
                                style="{{ $isCustom ? '' : 'display:none' }}"
                                {{ $isCustom ? 'required' : '' }}>
                            <div class="form-text">Unik, digunakan saat memanggil Notification API.</div>
                            @error('trigger_key')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Status</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive"
                                    {{ old('is_active', $template->is_active ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="isActive">Aktif</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">Isi Pesan WA <span class="text-danger">*</span></div>
                <div class="card-body">
                    <!-- Variable badges -->
                    <div class="mb-2">
                        <small class="text-muted d-block mb-1">Klik variabel untuk menyisipkan ke pesan:</small>
                        <div class="d-flex flex-wrap gap-1">
                                <span class="badge bg-primary var-badge" onclick="insertVar('@{{customer_name}}')">@{{customer_name}}</span>
                            <span class="badge bg-primary var-badge" onclick="insertVar('@{{email}}')">@{{email}}</span>
                            <span class="badge bg-primary var-badge" onclick="insertVar('@{{program_name}}')">@{{program_name}}</span>
                            <span class="badge bg-primary var-badge" onclick="insertVar('@{{amount}}')">@{{amount}}</span>
                            <span class="badge bg-primary var-badge" onclick="insertVar('@{{transaction_id}}')">@{{transaction_id}}</span>
                            <span class="badge bg-primary var-badge" onclick="insertVar('@{{status}}')">@{{status}}</span>
                            <span class="badge bg-primary var-badge" onclick="insertVar('@{{date}}')">@{{date}}</span>
                            <span class="badge bg-primary var-badge" onclick="insertVar('@{{product_url}}')">@{{product_url}}</span>
                        </div>
                    </div>
                    <textarea name="message_body" id="messageBody" rows="12"
                        class="form-control font-monospace @error('message_body') is-invalid @enderror"
                        placeholder="Tulis pesan notifikasi WhatsApp di sini...">{{ old('message_body', $template->message_body ?? '') }}</textarea>
                    @error('message_body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>{{ isset($template) ? 'Perbarui Template' : 'Simpan Template' }}
                </button>
                <a href="{{ route('cms.notification-templates.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </div>

        <!-- Live Preview -->
        <div class="col-md-5">
            <div class="card sticky-top" style="top: 80px;">
                <div class="card-header"><i class="bi bi-whatsapp me-2 text-success"></i>Preview (Data Dummy)</div>
                <div class="card-body">
                    <div class="wa-preview">
                        <div class="wa-bubble" id="waPreview">Ketik isi pesan di sebelah kiri untuk preview...</div>
                    </div>
                </div>
                <div class="card-footer bg-light">
                    <small class="text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        Preview menggunakan data dummy. Hasil aktual sesuai data transaksi nyata.
                    </small>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
function onTriggerKeyChange(val) {
    const input  = document.getElementById('triggerKeyInput');
    const select = document.getElementById('triggerKeySelect');
    if (val === '__custom__') {
        input.style.display = '';
        input.required = true;
        input.value = '';
        input.focus();
    } else {
        input.style.display = 'none';
        input.required = false;
        input.value = val;
    }
}

// Init: sync hidden input on page load for non-custom selections
(function() {
    const select = document.getElementById('triggerKeySelect');
    if (select && select.value !== '__custom__' && select.value !== '') {
        const input = document.getElementById('triggerKeyInput');
        if (!input.value) input.value = select.value;
    }
})();

const textarea = document.getElementById('messageBody');
const preview  = document.getElementById('waPreview');

// Dummy data for live preview
const dummyData = {
    '@{{customer_name}}':  'Budi Santoso',
    '@{{email}}':          'budi.santoso@email.com',
    '@{{program_name}}':   'Kelas B1 Online',
    '@{{amount}}':         'Rp 1.499.000',
    '@{{transaction_id}}': 'TRX-20240601-001',
    '@{{status}}':         'paid',
    '@{{date}}':           '{{ now()->format("d M Y, H:i") }}',
    '@{{product_url}}':    '{{ config("whatsapp.cms_base_url") }}/admin/transactions/1',
};

function renderPreview(text) {
    let result = text;
    for (const [key, val] of Object.entries(dummyData)) {
        result = result.replaceAll(key, val);
    }
    return result
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/\*(.*?)\*/g, '<strong>$1</strong>')
        .replace(/_(.*?)_/g, '<em>$1</em>')
        .replace(/\n/g, '<br>');
}

function updatePreview() {
    const html = renderPreview(textarea.value);
    preview.innerHTML = html || '<span class="text-muted">Preview kosong...</span>';
}

function insertVar(variable) {
    const start = textarea.selectionStart;
    const end   = textarea.selectionEnd;
    textarea.setRangeText(variable, start, end, 'end');
    textarea.focus();
    updatePreview();
}

textarea.addEventListener('input', updatePreview);
updatePreview();
</script>
@endpush
