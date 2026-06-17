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
                    <div class="mb-3 d-flex align-items-center gap-3 flex-wrap">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="ai_enabled" id="aiEnabled" value="1"
                                {{ ($configs['ai_enabled']->value ?? 'true') === 'true' ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="aiEnabled">Aktifkan AI Fallback</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Urutan Provider AI</label>
                        <input type="text" name="ai_provider_order" class="form-control font-monospace"
                            value="{{ $configs['ai_provider_order']->value ?? 'groq,gemini,openrouter' }}"
                            placeholder="groq,gemini,openrouter">
                        <div class="form-text">Urutan prioritas fallback, pisah koma. Bot mencoba provider pertama, lalu beralih ke berikutnya jika kuota habis. Pastikan API Key setiap provider sudah diisi di tab <strong>API &amp; Kredensial</strong>.</div>
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
                    <span class="badge bg-warning text-dark ms-auto">Free Tier</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">Model (Primary)</label>
                            @php $geminiModel = $configs['gemini_model']->value ?? $envFallbacks['gemini_model']; @endphp
                            <select name="gemini_model" class="form-select font-monospace">
                                @foreach($aiModels['gemini'] as $m)
                                    <option value="{{ $m['id'] }}" {{ $geminiModel === $m['id'] ? 'selected' : '' }}>
                                        {{ $m['label'] }} — {{ $m['desc'] }}
                                    </option>
                                @endforeach
                                @if(!collect($aiModels['gemini'])->pluck('id')->contains($geminiModel))
                                    <option value="{{ $geminiModel }}" selected>{{ $geminiModel }} (custom)</option>
                                @endif
                            </select>
                            <div class="form-text">
                                Semua model di atas gratis (free tier Google AI Studio).
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

            <!-- Groq -->
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="bi bi-lightning-charge-fill text-primary"></i>
                    <strong>Groq AI</strong>
                    <span class="badge bg-primary ms-auto">Free Tier</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label fw-semibold">Model (Primary)</label>
                            @php $groqModel = $configs['groq_model']->value ?? $envFallbacks['groq_model']; @endphp
                            <select name="groq_model" class="form-select font-monospace">
                                @foreach($aiModels['groq'] as $m)
                                    <option value="{{ $m['id'] }}" {{ $groqModel === $m['id'] ? 'selected' : '' }}>
                                        {{ $m['label'] }} — {{ $m['desc'] }}
                                    </option>
                                @endforeach
                                @if(!collect($aiModels['groq'])->pluck('id')->contains($groqModel))
                                    <option value="{{ $groqModel }}" selected>{{ $groqModel }} (custom)</option>
                                @endif
                            </select>
                            <div class="form-text">
                                Semua model di atas gratis (free tier Groq, ada batas request/menit).
                                @if(isset($configs['groq_model']) && $configs['groq_model']->value)
                                    <span class="badge bg-success ms-1">DB</span>
                                @elseif($envFallbacks['groq_model'])
                                    <span class="badge bg-secondary ms-1">.env</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">API Key</label>
                            @php $groqKey = $configs['groq_api_key']->value ?? $envFallbacks['groq_api_key']; @endphp
                            <div class="input-group">
                                <input type="password" name="groq_api_key" id="groqApiKey" class="form-control font-monospace"
                                    autocomplete="new-password"
                                    value="{{ $groqKey }}">
                                <button type="button" class="btn btn-outline-secondary btn-toggle-pwd" data-target="groqApiKey">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            @if(isset($configs['groq_api_key']) && $configs['groq_api_key']->value)
                                <div class="form-text text-success"><i class="bi bi-check-circle me-1"></i>Tersimpan di database. Kosongkan jika tidak ingin mengubah.</div>
                            @elseif($envFallbacks['groq_api_key'])
                                <div class="form-text text-info"><i class="bi bi-info-circle me-1"></i>Dibaca dari <code>.env</code>. Simpan di sini untuk override.</div>
                            @else
                                <div class="form-text text-warning"><i class="bi bi-exclamation-circle me-1"></i>Belum diset di database maupun <code>.env</code>.</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <!-- OpenRouter -->
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center gap-2">
                    <i class="bi bi-diagram-3-fill text-success"></i>
                    <strong>OpenRouter AI</strong>
                    <span class="badge bg-success ms-auto">Free Models</span>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning py-2 mb-3" style="font-size:.82rem">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <strong>WAJIB ada <code>:free</code></strong> di akhir nama model — tanpa itu OpenRouter mengenakan biaya/kredit!
                        Semua model di bawah sudah menyertakan <code>:free</code>.
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Model (Primary)</label>
                            @php $orModel = $configs['openrouter_model']->value ?? $envFallbacks['openrouter_model']; @endphp
                            <select name="openrouter_model" class="form-select font-monospace">
                                @foreach($aiModels['openrouter'] as $m)
                                    <option value="{{ $m['id'] }}" {{ $orModel === $m['id'] ? 'selected' : '' }}>
                                        {{ $m['label'] }} — {{ $m['desc'] }}
                                    </option>
                                @endforeach
                                @if(!collect($aiModels['openrouter'])->pluck('id')->contains($orModel))
                                    <option value="{{ $orModel }}" selected>{{ $orModel }} (custom)</option>
                                @endif
                            </select>
                            <div class="form-text">
                                Semua model di atas gratis (hanya ada batas request per hari).
                                @if(isset($configs['openrouter_model']) && $configs['openrouter_model']->value)
                                    <span class="badge bg-success ms-1">DB</span>
                                @elseif($envFallbacks['openrouter_model'])
                                    <span class="badge bg-secondary ms-1">.env</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">API Key</label>
                            @php $orKey = $configs['openrouter_api_key']->value ?? $envFallbacks['openrouter_api_key']; @endphp
                            <div class="input-group">
                                <input type="password" name="openrouter_api_key" id="openrouterApiKey" class="form-control font-monospace"
                                    autocomplete="new-password"
                                    value="{{ $orKey }}">
                                <button type="button" class="btn btn-outline-secondary btn-toggle-pwd" data-target="openrouterApiKey">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            @if(isset($configs['openrouter_api_key']) && $configs['openrouter_api_key']->value)
                                <div class="form-text text-success"><i class="bi bi-check-circle me-1"></i>Tersimpan di database. Kosongkan jika tidak ingin mengubah.</div>
                            @elseif($envFallbacks['openrouter_api_key'])
                                <div class="form-text text-info"><i class="bi bi-info-circle me-1"></i>Dibaca dari <code>.env</code>. Simpan di sini untuk override.</div>
                            @else
                                <div class="form-text text-warning"><i class="bi bi-exclamation-circle me-1"></i>Belum diset di database maupun <code>.env</code>.</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- NVIDIA NIM — Analytics AI -->
            <div class="card mb-4" style="border-left: 4px solid #76b900">
                <div class="card-header d-flex align-items-center gap-2">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="#76b900"><path d="M8.974 0v1.66c3.107.2 5.56 1.34 6.693 3.12L8.974 9.107V5.573l-1.387.84V18.52c.453.08.92.12 1.387.12 5.307 0 9.613-4.307 9.613-9.613C18.587 3.72 14.28 0 8.974 0zm-3.2 6.8L.8 9.613v4.774l1.387-.84V10.12l3.587-2.147V6.8zm0 4.747v1.173l1.387-.84v-1.173L5.774 11.547z"/></svg>
                    <strong>NVIDIA NIM</strong>
                    <span class="badge ms-auto" style="background:#76b900">Analytics AI — Primary</span>
                </div>
                <div class="card-body">
                    <div class="alert alert-success py-2 mb-3" style="font-size:.82rem">
                        <i class="bi bi-bar-chart-line me-1"></i>
                        Provider <strong>khusus</strong> untuk fitur <strong>Customer Analytics</strong>.
                        Jika primary gagal, bot otomatis coba model fallback (lihat di bawah). Tidak fallback ke Groq/Gemini/OpenRouter.
                    </div>
                    <div class="row g-3">
                        <div class="col-md-9">
                            <label class="form-label fw-semibold">Model (Primary — dapat dipilih)</label>
                            @php $nvidiaModel = $configs['nvidia_model']->value ?? $envFallbacks['nvidia_model']; @endphp
                            <select name="nvidia_model" class="form-select font-monospace">
                                @foreach($aiModels['nvidia']['primary'] as $m)
                                    <option value="{{ $m['id'] }}" {{ $nvidiaModel === $m['id'] ? 'selected' : '' }}>
                                        {{ $m['label'] }} — {{ $m['desc'] }}
                                    </option>
                                @endforeach
                                @if(!collect($aiModels['nvidia']['primary'])->pluck('id')->contains($nvidiaModel))
                                    <option value="{{ $nvidiaModel }}" selected>{{ $nvidiaModel }} (custom)</option>
                                @endif
                            </select>
                            <div class="form-text">
                                Model primary dicoba pertama kali untuk setiap sesi analitik.
                                @if(isset($configs['nvidia_model']) && $configs['nvidia_model']->value)
                                    <span class="badge bg-success ms-1">DB</span>
                                @elseif($envFallbacks['nvidia_model'])
                                    <span class="badge bg-secondary ms-1">.env</span>
                                @endif
                            </div>
                        </div>

                        <!-- Fallback chain — FYI only, not selectable -->
                        <div class="col-12">
                            <label class="form-label fw-semibold text-muted" style="font-size:.85rem">
                                <i class="bi bi-info-circle me-1"></i>Fallback Otomatis (FYI — tidak dapat diubah)
                            </label>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($aiModels['nvidia']['fallback'] as $i => $m)
                                    <div class="d-flex align-items-center gap-1 px-3 py-1 rounded-pill border bg-light" style="font-size:.78rem">
                                        <span class="badge rounded-pill text-bg-secondary" style="font-size:.7rem">{{ $i + 1 }}</span>
                                        <span class="font-monospace text-secondary">{{ $m['id'] }}</span>
                                        <span class="text-muted">— {{ $m['desc'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                            <div class="form-text mt-1">
                                Urutan ini hardcoded di sistem. Hanya primary di atas yang dapat Anda pilih.
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">API Key</label>
                            <div class="form-text text-muted mb-1" style="font-size:.78rem">
                                Dapatkan API key gratis di <strong>build.nvidia.com</strong> → API Key
                            </div>
                            @php $nvidiaKey = $configs['nvidia_api_key']->value ?? $envFallbacks['nvidia_api_key']; @endphp
                            <div class="input-group">
                                <input type="password" name="nvidia_api_key" id="nvidiaApiKey" class="form-control font-monospace"
                                    autocomplete="new-password"
                                    value="{{ $nvidiaKey }}">
                                <button type="button" class="btn btn-outline-secondary btn-toggle-pwd" data-target="nvidiaApiKey">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            @if(isset($configs['nvidia_api_key']) && $configs['nvidia_api_key']->value)
                                <div class="form-text text-success"><i class="bi bi-check-circle me-1"></i>Tersimpan di database. Kosongkan jika tidak ingin mengubah.</div>
                            @elseif($envFallbacks['nvidia_api_key'])
                                <div class="form-text text-info"><i class="bi bi-info-circle me-1"></i>Dibaca dari <code>.env</code>. Simpan di sini untuk override.</div>
                            @else
                                <div class="form-text text-warning"><i class="bi bi-exclamation-circle me-1"></i>Belum diset — fitur Customer Analytics tidak akan berfungsi.</div>
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
