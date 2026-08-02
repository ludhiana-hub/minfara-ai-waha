<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login — MinFara AI CMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --brand-primary: #1D9E75;
            --brand-dark: #1a1a2e;
        }
        body {
            background: var(--brand-dark);
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            width: 100%;
            max-width: 380px;
            background: #fff;
            border-radius: 12px;
            padding: 36px 32px;
            box-shadow: 0 12px 40px rgba(0,0,0,.35);
        }
        .login-brand { text-align: center; margin-bottom: 28px; }
        .login-brand h5 { color: var(--brand-dark); font-weight: 700; margin: 8px 0 2px; }
        .login-brand small { color: #6c757d; }
        .btn-primary { background: var(--brand-primary); border-color: var(--brand-primary); }
        .btn-primary:hover { background: #178a63; border-color: #178a63; }
        .form-control:focus { border-color: var(--brand-primary); box-shadow: 0 0 0 .2rem rgba(29,158,117,.15); }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-brand">
        <div style="font-size: 2rem;">🤖</div>
        <h5>MinFara AI</h5>
        <small>CMS Dashboard</small>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger py-2 small" role="alert">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('cms.login.submit') }}">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label small fw-semibold">Email</label>
            <input type="email" class="form-control" id="email" name="email"
                   value="{{ old('email') }}" required autofocus autocomplete="username">
        </div>

        <div class="mb-3">
            <label for="password" class="form-label small fw-semibold">Password</label>
            <input type="password" class="form-control" id="password" name="password"
                   required autocomplete="current-password">
        </div>

        <div class="form-check mb-3">
            <input type="checkbox" class="form-check-input" id="remember" name="remember">
            <label class="form-check-label small" for="remember">Ingat saya</label>
        </div>

        <button type="submit" class="btn btn-primary w-100">Masuk</button>
    </form>
</div>

</body>
</html>
