@extends('cms.layouts.app')
@section('title', isset($target) ? 'Edit Target Notifikasi' : 'Tambah Target Notifikasi')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('cms.notification-targets.index') }}" class="text-decoration-none" style="color:var(--brand-primary)">Target Notifikasi WA</a></li>
    <li class="breadcrumb-item active">{{ isset($target) ? 'Edit' : 'Tambah' }}</li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0 fw-bold">{{ isset($target) ? 'Edit Target Notifikasi' : 'Tambah Target Notifikasi' }}</h4>
    <a href="{{ route('cms.notification-targets.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Kembali
    </a>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Informasi Target</div>
            <div class="card-body">
                <form method="POST" action="{{ isset($target) ? route('cms.notification-targets.update', $target) : route('cms.notification-targets.store') }}">
                    @csrf
                    @if(isset($target)) @method('PUT') @endif

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nama <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $target->name ?? '') }}"
                            placeholder="Contoh: Admin Utama, Tim Sales" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nomor WhatsApp <span class="text-danger">*</span></label>
                        <input type="text" name="phone_number" class="form-control font-monospace @error('phone_number') is-invalid @enderror"
                            value="{{ old('phone_number', $target->phone_number ?? '') }}"
                            placeholder="08xxx / 628xxx / +62xxx" required>
                        <div class="form-text">Akan dinormalisasi ke format 628xxx secara otomatis.</div>
                        @error('phone_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Status</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="isActive"
                                {{ old('is_active', $target->is_active ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="isActive">Aktif (terima notifikasi)</label>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>{{ isset($target) ? 'Perbarui Target' : 'Simpan Target' }}
                        </button>
                        <a href="{{ route('cms.notification-targets.index') }}" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-info-circle me-2"></i>Format Nomor yang Didukung</div>
            <div class="card-body">
                <ul class="list-group list-group-flush small">
                    <li class="list-group-item d-flex justify-content-between">
                        <code>08xxx</code> <span class="text-muted">→ 628xxx</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <code>628xxx</code> <span class="text-muted">→ 628xxx (tidak berubah)</span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <code>+62xxx</code> <span class="text-muted">→ 628xxx</span>
                    </li>
                </ul>
                <div class="mt-3 alert alert-info py-2 small mb-0">
                    <i class="bi bi-whatsapp me-1"></i>
                    Nomor harus sudah terdaftar di WhatsApp dan dapat menerima pesan.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
