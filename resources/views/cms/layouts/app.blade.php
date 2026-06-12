<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CMS') — MinFara AI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        :root {
            --brand-primary: #1D9E75;
            --brand-dark: #1a1a2e;
            --brand-wa: #25D366;
        }
        body { background: #f8f9fa; font-family: 'Segoe UI', sans-serif; }
        .sidebar {
            width: 260px; min-height: 100vh; background: var(--brand-dark);
            position: fixed; top: 0; left: 0; z-index: 1000;
            display: flex; flex-direction: column;
            transition: transform .3s ease;
        }
        .sidebar-brand {
            padding: 20px 20px 16px;
            border-bottom: 1px solid rgba(255,255,255,.08);
        }
        .sidebar-brand h5 { color: var(--brand-wa); font-weight: 700; margin: 0; font-size: 1.1rem; }
        .sidebar-brand small { color: rgba(255,255,255,.4); font-size: .72rem; }
        .sidebar-nav { flex: 1; padding: 12px 0; }
        .sidebar-nav .nav-link {
            color: rgba(255,255,255,.65); padding: 10px 20px;
            border-radius: 0; transition: all .2s; font-size: .9rem;
            display: flex; align-items: center; gap: 10px;
        }
        .sidebar-nav .nav-link:hover { color: #fff; background: rgba(255,255,255,.06); }
        .sidebar-nav .nav-link.active { color: #fff; background: var(--brand-primary); }
        .sidebar-nav .nav-link i { font-size: 1rem; width: 18px; }
        .sidebar-footer {
            padding: 14px 20px; border-top: 1px solid rgba(255,255,255,.08);
            font-size: .78rem;
        }
        .sidebar-footer .version { color: rgba(255,255,255,.3); }
        .waha-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #dc3545; }
        .waha-dot.connected { background: var(--brand-wa); }
        .waha-status-text { color: rgba(255,255,255,.5); font-size: .78rem; }
        .main-wrapper { margin-left: 260px; min-height: 100vh; }
        .topbar {
            background: #fff; padding: 14px 24px; border-bottom: 1px solid #e9ecef;
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 999; box-shadow: 0 1px 4px rgba(0,0,0,.06);
        }
        .topbar-title { font-weight: 600; font-size: 1rem; color: #343a40; }
        .main-content { padding: 24px; }
        .btn-primary { background: var(--brand-primary); border-color: var(--brand-primary); }
        .btn-primary:hover { background: #178a63; border-color: #178a63; }
        .btn-outline-primary { color: var(--brand-primary); border-color: var(--brand-primary); }
        .btn-outline-primary:hover { background: var(--brand-primary); border-color: var(--brand-primary); }
        .badge-faq { background: #198754; }
        .badge-ai { background: #0d6efd; }
        .badge-error { background: #dc3545; }
        .badge-end_chat { background: #6c757d; }
        .card { border: none; box-shadow: 0 1px 4px rgba(0,0,0,.07); }
        .card-header { background: #fff; border-bottom: 1px solid #f0f0f0; font-weight: 600; }
        .stat-card { border-left: 4px solid var(--brand-primary); }
        .toast-container { z-index: 9999; }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-260px); }
            .sidebar.show { transform: translateX(0); }
            .main-wrapper { margin-left: 0; }
        }
    </style>
    @stack('styles')
</head>
<body>

<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <h5>🤖 MinFara AI</h5>
        <small>CMS Dashboard</small>
    </div>
    <nav class="sidebar-nav">
        <div class="nav flex-column">
            <a href="{{ route('cms.dashboard') }}" class="nav-link {{ request()->routeIs('cms.dashboard') ? 'active' : '' }}">
                <i class="bi bi-house-door"></i> Dashboard
            </a>
            <a href="{{ route('cms.faq.index') }}" class="nav-link {{ request()->routeIs('cms.faq.*') ? 'active' : '' }}">
                <i class="bi bi-journal-text"></i> Menu FAQ
            </a>
            <a href="{{ route('cms.konfigurasi.index') }}" class="nav-link {{ request()->routeIs('cms.konfigurasi.*') ? 'active' : '' }}">
                <i class="bi bi-gear"></i> Konfigurasi
            </a>
            <a href="{{ route('cms.log.index') }}" class="nav-link {{ request()->routeIs('cms.log.*') ? 'active' : '' }}">
                <i class="bi bi-chat-square-text"></i> Log Percakapan
            </a>
            <a href="{{ route('cms.test.index') }}" class="nav-link {{ request()->routeIs('cms.test.*') ? 'active' : '' }}">
                <i class="bi bi-send"></i> Test Kirim Pesan
            </a>
            <div style="padding: 8px 20px 4px; font-size:.7rem; color:rgba(255,255,255,.3); text-transform:uppercase; letter-spacing:.05em">Notifikasi WA</div>
            <a href="{{ route('cms.notification-templates.index') }}" class="nav-link {{ request()->routeIs('cms.notification-templates.*') ? 'active' : '' }}">
                <i class="bi bi-file-text"></i> Template Notif
            </a>
            <a href="{{ route('cms.notification-targets.index') }}" class="nav-link {{ request()->routeIs('cms.notification-targets.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i> Target Notif
            </a>
            <a href="{{ route('cms.notification-logs.index') }}" class="nav-link {{ request()->routeIs('cms.notification-logs.*') ? 'active' : '' }}">
                <i class="bi bi-bell"></i> Log Notifikasi
            </a>
        </div>
    </nav>
    <div class="sidebar-footer">
        <div class="version mb-1">v1.0.0</div>
        <div class="d-flex align-items-center gap-2">
            <span class="waha-dot" id="wahaDot"></span>
            <span class="waha-status-text" id="wahaStatusText">Memeriksa...</span>
        </div>
    </div>
</div>

<div class="main-wrapper">
    <div class="topbar">
        <div class="d-flex align-items-center gap-3">
            <button class="btn btn-sm btn-light d-md-none" onclick="document.getElementById('sidebar').classList.toggle('show')">
                <i class="bi bi-list"></i>
            </button>
            <nav aria-label="breadcrumb" class="mb-0">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('cms.dashboard') }}" class="text-decoration-none" style="color:var(--brand-primary)">CMS</a></li>
                    @yield('breadcrumb')
                </ol>
            </nav>
        </div>
        <div id="botStatusBadge">
            <span class="badge bg-secondary">Memeriksa...</span>
        </div>
    </div>

    <div class="main-content">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="mainToast" class="toast align-items-center border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body" id="toastMessage"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

function showToast(message, type = 'success') {
    const toast = document.getElementById('mainToast');
    const msg   = document.getElementById('toastMessage');
    toast.className = 'toast align-items-center border-0 text-white bg-' + (type === 'success' ? 'success' : 'danger');
    msg.textContent = message;
    new bootstrap.Toast(toast, { delay: 3500 }).show();
}

async function checkWahaStatus() {
    try {
        const res  = await fetch('{{ route("cms.api.waha-status") }}');
        const data = await res.json();
        const dot  = document.getElementById('wahaDot');
        const txt  = document.getElementById('wahaStatusText');
        const badge = document.getElementById('botStatusBadge');
        if (data.connected) {
            dot.classList.add('connected');
            txt.textContent = 'WAHA Connected';
            badge.innerHTML = '<span class="badge" style="background:var(--brand-wa)">🟢 Bot Aktif</span>';
        } else {
            dot.classList.remove('connected');
            txt.textContent = 'WAHA Disconnected';
            badge.innerHTML = '<span class="badge bg-danger">🔴 Bot Nonaktif</span>';
        }
    } catch(e) {
        document.getElementById('wahaStatusText').textContent = 'Tidak dapat terhubung';
    }
}

checkWahaStatus();
setInterval(checkWahaStatus, 30000);
</script>
@stack('scripts')
</body>
</html>
