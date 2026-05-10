@extends('cms.layouts.app')
@section('title', 'Konfigurasi')
@section('breadcrumb')
    <li class="breadcrumb-item active">Konfigurasi</li>
@endsection

@push('styles')
<style>
.wa-preview-sm { background:#e5ddd5; border-radius:8px; padding:12px; font-size:.85rem; }
.wa-bubble-sm  { background:#dcf8c6; border-radius:8px 8px 0 8px; padding:8px 10px; max-width:90%; margin-left:auto; white-space:pre-wrap; word-break:break-word; box-shadow:0 1px 2px rgba(0,0,0,.15); }
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold">Konfigurasi Chatbot</h4>
</div>

<form method="POST" action="{{ route('cms.konfigurasi.update') }}">
    @csrf

    <ul class="nav nav-tabs mb-4" id="configTabs">
        <li class="nav-item"><button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-umum">🏠 Umum</button></li>
        <li class="nav-item"><button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-ai">🤖 AI</button></li>
        <li class="nav-item"><button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-pesan">💬 Pesan</button></li>
        <li class="nav-item"><button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-kontak">📞 Kontak</button></li>
        <li class="nav-item"><button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-api">🔑 API & Kredensial</button></li>
    </ul>

    <div class="tab-content">
        <!-- Tab Umum -->
        <div class="tab-pane fade show active" id="tab-umum">
            <div class="card">
                <div class="card-header">Pengaturan Umum</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ $configs['bot_name']->label ?? 'Nama Bot' }}</label>
                        <input type="text" name="bot_name" class="form-control" value="{{ $configs['bot_name']->value ?? 'MinFara AI' }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ $configs['bot_greeting']->label ?? 'Kata Sapaan' }}</label>
                        <input type="text" name="bot_greeting" class="form-control"
                            value="{{ $configs['bot_greeting']->value ?? '' }}">
                        <div class="form-text">Pisahkan dengan koma. Contoh: halo,hai,hi,hello,menu,start</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab AI -->
        <div class="tab-pane fade" id="tab-ai">
            <div class="card">
                <div class="card-header">Pengaturan AI</div>
                <div class="card-body">
                    <div class="mb-3 d-flex align-items-center gap-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="ai_enabled" id="aiEnabled" value="1"
                                {{ ($configs['ai_enabled']->value ?? 'true') === 'true' ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="aiEnabled">Aktifkan AI Fallback</label>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">{{ $configs['ai_max_tokens']->label ?? 'Maksimal Token' }}</label>
                            <input type="number" name="ai_max_tokens" class="form-control"
                                value="{{ $configs['ai_max_tokens']->value ?? 500 }}" min="100" max="2000">
                            <div class="form-text">Batas panjang jawaban AI (100-2000 token)</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">
                                {{ $configs['ai_temperature']->label ?? 'Temperatur AI' }}:
                                <span id="tempValue">{{ $configs['ai_temperature']->value ?? 0.7 }}</span>
                            </label>
                            <input type="range" name="ai_temperature" class="form-range"
                                value="{{ $configs['ai_temperature']->value ?? 0.7 }}" min="0" max="1" step="0.1"
                                oninput="document.getElementById('tempValue').textContent = this.value">
                            <div class="d-flex justify-content-between small text-muted"><span>Konsisten</span><span>Kreatif</span></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-semibold mb-0">System Prompt AI</label>
                            <span class="badge bg-secondary" id="promptCount">0 karakter</span>
                        </div>
                        <textarea name="ai_system_prompt" id="systemPrompt" rows="15" class="form-control font-monospace"
                            oninput="document.getElementById('promptCount').textContent = this.value.length + ' karakter'">{{ $configs['ai_system_prompt']->value ?? '' }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Pesan -->
        <div class="tab-pane fade" id="tab-pesan">
            <div class="row g-3">
                @foreach([
                    ['footer_faq', 'Footer Pesan FAQ', 'faqFooterPreview'],
                    ['footer_ai',  'Footer Pesan AI',  'aiFooterPreview'],
                    ['fallback_message', 'Pesan Error/Fallback', 'fallbackPreview'],
                ] as [$key, $label, $previewId])
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">{{ $label }}</div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-7">
                                    <textarea name="{{ $key }}" rows="5" class="form-control font-monospace preview-textarea"
                                        data-preview="{{ $previewId }}">{{ $configs[$key]->value ?? '' }}</textarea>
                                </div>
                                <div class="col-md-5">
                                    <div class="wa-preview-sm">
                                        <div class="wa-bubble-sm" id="{{ $previewId }}">Preview...</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Tab Kontak -->
        <div class="tab-pane fade" id="tab-kontak">
            <div class="card">
                <div class="card-header">Informasi Kontak</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ $configs['admin_wa']->label ?? 'Nomor WA Admin' }}</label>
                        <div class="input-group">
                            <span class="input-group-text">+</span>
                            <input type="text" name="admin_wa" class="form-control"
                                value="{{ $configs['admin_wa']->value ?? '' }}"
                                placeholder="628xxxxxxxxxx">
                        </div>
                        <div class="form-text">Format: 628xxx tanpa + dan spasi</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ $configs['admin_wa_label']->label ?? 'Label Admin WA' }}</label>
                        <input type="text" name="admin_wa_label" class="form-control"
                            value="{{ $configs['admin_wa_label']->value ?? '' }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">{{ $configs['office_hours']->label ?? 'Jam Operasional' }}</label>
                        <input type="text" name="office_hours" class="form-control"
                            value="{{ $configs['office_hours']->value ?? '' }}"
                            placeholder="Senin–Sabtu, 08.00–20.00 WIB">
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab API & Kredensial -->
        <div class="tab-pane fade" id="tab-api">
            <div class="alert alert-info d-flex gap-2 mb-4">
                <i class="bi bi-info-circle-fill fs-5"></i>
                <div>
                    Nilai ditampilkan dari <strong>database</strong> (prioritas) atau <strong>.env</strong> (fallback).
                    Kosongkan field API Key jika tidak ingin mengubah nilai yang sudah tersimpan.
                </div>
            </div>

            <!-- WAHA -->
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="bi bi-whatsapp text-success"></i>
                    <strong>WAHA (WhatsApp HTTP API)</strong>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">Server URL</label>
                            @php $wahaUrl = $configs['waha_url']->value ?? $envFallbacks['waha_url']; @endphp
                            <input type="text" name="waha_url" class="form-control font-monospace"
                                value="{{ $wahaUrl }}"
                                placeholder="http://localhost:3000">
                            <div class="form-text">
                                URL server WAHA. Contoh: <code>http://localhost:3000</code>
                                @if(isset($configs['waha_url']) && $configs['waha_url']->value)
                                    <span class="badge bg-success ms-1">DB</span>
                                @elseif($envFallbacks['waha_url'])
                                    <span class="badge bg-secondary ms-1">.env</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold">Session Name</label>
                            @php $wahaSession = $configs['waha_session']->value ?? $envFallbacks['waha_session']; @endphp
                            <input type="text" name="waha_session" class="form-control font-monospace"
                                value="{{ $wahaSession }}"
                                placeholder="default">
                            <div class="form-text">
                                Nama sesi WhatsApp aktif di WAHA
                                @if(isset($configs['waha_session']) && $configs['waha_session']->value)
                                    <span class="badge bg-success ms-1">DB</span>
                                @elseif($envFallbacks['waha_session'])
                                    <span class="badge bg-secondary ms-1">.env</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">API Key</label>
                            @php $wahaKey = $configs['waha_api_key']->value ?? $envFallbacks['waha_api_key']; @endphp
                            <div class="input-group">
                                <input type="password" name="waha_api_key" id="wahaApiKey" class="form-control font-monospace"
                                    autocomplete="new-password"
                                    value="{{ $wahaKey }}">
                                <button type="button" class="btn btn-outline-secondary btn-toggle-pwd" data-target="wahaApiKey">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            @if(isset($configs['waha_api_key']) && $configs['waha_api_key']->value)
                                <div class="form-text text-success"><i class="bi bi-check-circle me-1"></i>Tersimpan di database. Kosongkan jika tidak ingin mengubah.</div>
                            @elseif($envFallbacks['waha_api_key'])
                                <div class="form-text text-info"><i class="bi bi-info-circle me-1"></i>Dibaca dari <code>.env</code>. Simpan di sini untuk override.</div>
                            @else
                                <div class="form-text text-warning"><i class="bi bi-exclamation-circle me-1"></i>Belum diset di database maupun <code>.env</code>.</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gemini -->
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="bi bi-stars text-warning"></i>
                    <strong>Google Gemini AI</strong>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Model</label>
                            @php $geminiModel = $configs['gemini_model']->value ?? $envFallbacks['gemini_model']; @endphp
                            <input type="text" name="gemini_model" class="form-control font-monospace"
                                value="{{ $geminiModel }}"
                                placeholder="gemini-2.0-flash">
                            <div class="form-text">
                                Contoh: <code>gemini-2.0-flash</code>, <code>gemini-1.5-pro</code>
                                @if(isset($configs['gemini_model']) && $configs['gemini_model']->value)
                                    <span class="badge bg-success ms-1">DB</span>
                                @elseif($envFallbacks['gemini_model'])
                                    <span class="badge bg-secondary ms-1">.env</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">API Key</label>
                            @php $geminiKey = $configs['gemini_api_key']->value ?? $envFallbacks['gemini_api_key']; @endphp
                            <div class="input-group">
                                <input type="password" name="gemini_api_key" id="geminiApiKey" class="form-control font-monospace"
                                    autocomplete="new-password"
                                    value="{{ $geminiKey }}">
                                <button type="button" class="btn btn-outline-secondary btn-toggle-pwd" data-target="geminiApiKey">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            @if(isset($configs['gemini_api_key']) && $configs['gemini_api_key']->value)
                                <div class="form-text text-success"><i class="bi bi-check-circle me-1"></i>Tersimpan di database. Kosongkan jika tidak ingin mengubah.</div>
                            @elseif($envFallbacks['gemini_api_key'])
                                <div class="form-text text-info"><i class="bi bi-info-circle me-1"></i>Dibaca dari <code>.env</code>. Simpan di sini untuk override.</div>
                            @else
                                <div class="form-text text-warning"><i class="bi bi-exclamation-circle me-1"></i>Belum diset di database maupun <code>.env</code>.</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save me-1"></i>Simpan Semua Konfigurasi
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const prompt = document.getElementById('systemPrompt');
    if (prompt) document.getElementById('promptCount').textContent = prompt.value.length + ' karakter';

    // Toggle show/hide password fields
    document.querySelectorAll('.btn-toggle-pwd').forEach(btn => {
        btn.addEventListener('click', function() {
            const input = document.getElementById(this.dataset.target);
            const icon  = this.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        });
    });

    function renderWa(text) {
        return text
            .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
            .replace(/\*(.*?)\*/g,'<strong>$1</strong>')
            .replace(/_(.*?)_/g,'<em>$1</em>')
            .replace(/\n/g,'<br>') || '<span class="text-muted">Kosong...</span>';
    }

    document.querySelectorAll('.preview-textarea').forEach(ta => {
        const previewEl = document.getElementById(ta.dataset.preview);
        if (previewEl) {
            previewEl.innerHTML = renderWa(ta.value);
            ta.addEventListener('input', () => { previewEl.innerHTML = renderWa(ta.value); });
        }
    });
});
</script>
@endpush
