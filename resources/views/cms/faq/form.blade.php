@extends('cms.layouts.app')
@section('title', isset($faq) ? 'Edit Menu FAQ' : 'Tambah Menu FAQ')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('cms.faq.index') }}" class="text-decoration-none" style="color:var(--brand-primary)">Menu FAQ</a></li>
    <li class="breadcrumb-item active">{{ isset($faq) ? 'Edit' : 'Tambah' }}</li>
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
    max-width: 85%;
    margin-left: auto;
    white-space: pre-wrap;
    word-break: break-word;
    box-shadow: 0 1px 2px rgba(0,0,0,.15);
    line-height: 1.5;
}
</style>
@endpush

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold">{{ isset($faq) ? 'Edit Menu FAQ' : 'Tambah Menu FAQ' }}</h4>
    <a href="{{ route('cms.faq.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
</div>

<form method="POST" action="{{ isset($faq) ? route('cms.faq.update', $faq) : route('cms.faq.store') }}">
    @csrf
    @if(isset($faq)) @method('PUT') @endif

    <div class="row g-3">
        <!-- Form Fields -->
        <div class="col-md-7">
            <div class="card">
                <div class="card-header">Informasi Menu</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Command <span class="text-danger">*</span></label>
                            <input type="text" name="command" class="form-control @error('command') is-invalid @enderror"
                                value="{{ old('command', $faq->command ?? '') }}"
                                placeholder="Contoh: 1, 2, 1.1, 1.2" required>
                            <div class="form-text">Unik, diketik user untuk mengakses menu ini.</div>
                            @error('command')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Judul <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                                value="{{ old('title', $faq->title ?? '') }}"
                                placeholder="Label internal untuk admin" required>
                            @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Parent Command</label>
                            <select name="parent_command" class="form-select @error('parent_command') is-invalid @enderror">
                                <option value="">— Menu Utama (tanpa parent) —</option>
                                @foreach($parents as $parent)
                                    <option value="{{ $parent->command }}"
                                        {{ old('parent_command', $faq->parent_command ?? '') === $parent->command ? 'selected' : '' }}>
                                        {{ $parent->command }} — {{ $parent->title }}
                                    </option>
                                @endforeach
                            </select>
                            @error('parent_command')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Urutan</label>
                            <input type="number" name="sort_order" class="form-control"
                                value="{{ old('sort_order', $faq->sort_order ?? 0) }}" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold">Status</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive"
                                    {{ old('is_active', $faq->is_active ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="isActive">Aktif</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>Konten Pesan <span class="text-danger">*</span></span>
                    <span class="badge bg-secondary" id="charCount">0 / 4096</span>
                </div>
                <div class="card-body">
                    <!-- Shortcut buttons -->
                    <div class="d-flex flex-wrap gap-1 mb-2">
                        <button type="button" class="btn btn-xs btn-outline-secondary btn-sm py-0" onclick="insertText('*', '*')"><b>Bold</b></button>
                        <button type="button" class="btn btn-xs btn-outline-secondary btn-sm py-0" onclick="insertText('_', '_')"><i>Italic</i></button>
                        <button type="button" class="btn btn-xs btn-outline-secondary btn-sm py-0" onclick="insertText('\n', '')">↵ Baris</button>
                        @foreach(['✅','❌','📚','💰','📍','👋','🇩🇪','🎓','📝','⚙️'] as $emoji)
                            <button type="button" class="btn btn-sm btn-light py-0 px-2" onclick="insertRaw('{{ $emoji }}')">{{ $emoji }}</button>
                        @endforeach
                    </div>
                    <textarea name="content" id="contentArea" rows="12"
                        class="form-control font-monospace @error('content') is-invalid @enderror"
                        placeholder="Tulis konten pesan WhatsApp di sini..." required>{{ old('content', $faq->content ?? '') }}</textarea>
                    @error('content')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="d-flex gap-2 mt-3">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>{{ isset($faq) ? 'Perbarui Menu' : 'Simpan Menu' }}
                </button>
                <a href="{{ route('cms.faq.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </div>

        <!-- Preview Panel -->
        <div class="col-md-5">
            <div class="card sticky-top" style="top: 80px;">
                <div class="card-header"><i class="bi bi-whatsapp me-2 text-success"></i>Preview Tampilan WA</div>
                <div class="card-body">
                    <div class="wa-preview">
                        <div class="wa-bubble" id="waPreview">Ketik konten di sebelah kiri untuk preview...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
const textarea = document.getElementById('contentArea');
const preview  = document.getElementById('waPreview');
const counter  = document.getElementById('charCount');

function updatePreview() {
    const text = textarea.value;
    counter.textContent = text.length + ' / 4096';
    if (text.length > 4000) counter.className = 'badge bg-danger';
    else if (text.length > 3500) counter.className = 'badge bg-warning';
    else counter.className = 'badge bg-secondary';

    let html = text
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/\*(.*?)\*/g, '<strong>$1</strong>')
        .replace(/_(.*?)_/g, '<em>$1</em>')
        .replace(/\n/g, '<br>');
    preview.innerHTML = html || '<span class="text-muted">Preview kosong...</span>';
}

function insertText(before, after) {
    const start = textarea.selectionStart, end = textarea.selectionEnd;
    const sel = textarea.value.substring(start, end);
    const rep = before + (sel || 'teks') + after;
    textarea.setRangeText(rep, start, end, 'select');
    textarea.focus(); updatePreview();
}

function insertRaw(text) {
    const start = textarea.selectionStart;
    textarea.setRangeText(text, start, start, 'end');
    textarea.focus(); updatePreview();
}

textarea.addEventListener('input', updatePreview);
updatePreview();
</script>
@endpush
