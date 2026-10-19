<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; font-family: "Plus Jakarta Sans", system-ui, sans-serif; }
        body { margin: 0; min-height: 100vh; display: flex; background: #0b1220; color: #fff; }
        .brand-pane {
            flex: 1; position: relative; overflow: hidden; padding: 56px; display: flex; flex-direction: column; justify-content: space-between;
            background:
                radial-gradient(circle at 16% 20%, rgba(255,255,255,.16), transparent 26%),
                radial-gradient(circle at 84% 12%, rgba(251,191,36,.24), transparent 24%),
                linear-gradient(135deg, #111827 0%, #7c2d12 48%, #f97316 100%);
        }
        .brand-pane::after { content: ""; position: absolute; right: -120px; bottom: -160px; width: 380px; height: 380px; border-radius: 50%; background: rgba(255,255,255,.1); }
        .brand-pane > * { position: relative; z-index: 1; }
        .brand-mark { display: flex; align-items: center; gap: 14px; }
        .brand-mark .ico { width: 48px; height: 48px; border-radius: 14px; display: grid; place-items: center; background: rgba(255,255,255,.16); border: 1px solid rgba(255,255,255,.25); font-size: 22px; }
        .brand-mark h1 { font-size: 22px; font-weight: 800; margin: 0; }
        .brand-copy h2 { font-size: clamp(28px, 3.4vw, 42px); font-weight: 800; letter-spacing: -.03em; line-height: 1.1; margin: 0 0 14px; }
        .brand-copy p { color: rgba(255,255,255,.78); font-size: 15px; max-width: 440px; line-height: 1.6; }
        .brand-foot { font-size: 12px; color: rgba(255,255,255,.6); }
        .form-pane { width: 460px; max-width: 100%; background: #fff; color: #334155; padding: 56px 48px; display: flex; flex-direction: column; justify-content: center; }
        .form-pane h3 { font-size: 22px; font-weight: 800; color: #1E293B; margin: 0 0 4px; }
        .form-pane .sub { color: #64748B; font-size: 13.5px; margin-bottom: 28px; }
        .form-label { font-size: 12px; font-weight: 600; color: #64748B; margin-bottom: 6px; }
        .form-control { border-radius: 10px; border: 1px solid #E2E8F0; padding: 11px 13px; font-size: 14px; }
        .form-control:focus { border-color: #A78BFA; box-shadow: 0 0 0 4px rgba(139,92,246,.12); }
        .btn-primary { background: linear-gradient(135deg, #8B5CF6, #7C3AED); border: 0; border-radius: 10px; font-weight: 700; padding: 12px; box-shadow: 0 12px 26px rgba(139,92,246,.3); }
        .alert-danger { background: #FEF2F2; color: #991B1B; border: 0; border-radius: 12px; font-size: 13px; }
        @media (max-width: 900px) { .brand-pane { display: none; } .form-pane { width: 100%; } body { background: #fff; } }
    </style>
</head>
<body>
    <div class="brand-pane">
        <div class="brand-mark">
            <div class="ico"><i class="fas fa-calculator"></i></div>
            <h1>Swado Accounts</h1>
        </div>
        <div class="brand-copy">
            <h2>The books,<br>balanced.</h2>
            <p>General ledger, GST &amp; TDS/TCS returns, financial statements and the compliance calendar — one workspace for the finance team.</p>
        </div>
        <div class="brand-foot">Access restricted to accountant &amp; admin roles.</div>
    </div>
    <div class="form-pane">
        <h3>Sign in</h3>
        <p class="sub">Use your Swado account credentials.</p>
        @if($errors->any())<div class="alert alert-danger py-2 px-3 mb-3">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label">Email address</label>
                <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="form-check mb-4">
                <input type="checkbox" name="remember" class="form-check-input" id="r">
                <label for="r" class="form-check-label small text-muted">Keep me signed in</label>
            </div>
            <button class="btn btn-primary w-100">Sign in</button>
        </form>
    </div>
</body>
</html>
