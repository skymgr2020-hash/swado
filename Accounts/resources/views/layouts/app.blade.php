<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Accounts') · {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #8B5CF6; --primary-dark: #7C3AED; --primary-light: #A78BFA;
            --success: #10B981; --warning: #F59E0B; --danger: #EF4444; --info: #3B82F6;
            --ink: #1E293B; --muted: #64748B; --line: #E2E8F0; --bg: #F1F5F9;
            --sidebar-w: 258px; --topbar-h: 70px;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; font-family: "Plus Jakarta Sans", system-ui, sans-serif; color: #334155;
            background:
                radial-gradient(circle at 12% 0%, rgba(249,115,22,.08), transparent 26%),
                radial-gradient(circle at 88% 8%, rgba(59,130,246,.07), transparent 24%),
                var(--bg);
            min-height: 100vh;
        }
        a { text-decoration: none; }

        /* ---------- shell ---------- */
        .app-shell { display: flex; min-height: 100vh; }
        .sidebar {
            width: var(--sidebar-w); flex-shrink: 0; background: #fff; border-right: 1px solid var(--line);
            position: sticky; top: 0; height: 100vh; display: flex; flex-direction: column;
        }
        .sidebar-logo-section {
            display: flex; align-items: center; gap: 12px; padding: 20px; min-height: var(--topbar-h);
            border-bottom: 1px solid var(--line);
        }
        .sidebar-logo-icon {
            width: 42px; height: 42px; border-radius: 12px; flex-shrink: 0; color: #fff; font-size: 19px;
            display: grid; place-items: center;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            box-shadow: 0 8px 20px rgba(139,92,246,.28);
        }
        .sidebar-logo-text h2 { font-size: 18px; font-weight: 800; color: var(--ink); margin: 0; letter-spacing: -.4px; }
        .sidebar-logo-text small { font-size: 10px; color: var(--muted); font-weight: 600; letter-spacing: .04em; text-transform: uppercase; }
        .sidebar-nav-wrapper { flex: 1; overflow-y: auto; padding: 16px 12px 28px; scrollbar-width: thin; }
        .sidebar-section-title {
            font-size: 10px; text-transform: uppercase; letter-spacing: 1.4px; color: #94A3B8;
            font-weight: 700; padding: 14px 12px 6px;
        }
        .sidebar-nav { list-style: none; padding: 0; margin: 0; }
        .sidebar-nav li { margin-bottom: 2px; }
        .sidebar-nav-link {
            display: flex; align-items: center; gap: 12px; padding: 10px 14px; border-radius: 10px;
            color: var(--muted); font-size: 13.5px; font-weight: 500; transition: background .15s, color .15s;
            position: relative; border: 0; background: transparent; width: 100%; text-align: left; cursor: pointer;
        }
        .sidebar-nav-link:hover { background: #F8FAFC; color: #334155; }
        .sidebar-nav-link.active {
            background: linear-gradient(135deg, rgba(139,92,246,.12), rgba(139,92,246,.05));
            color: var(--primary); font-weight: 700;
        }
        .sidebar-nav-link.active::before {
            content: ""; position: absolute; left: 0; top: 50%; transform: translateY(-50%);
            width: 3px; height: 20px; border-radius: 0 3px 3px 0; background: var(--primary);
        }
        .sidebar-nav-link i { width: 18px; text-align: center; font-size: 15px; flex-shrink: 0; }

        .main-content { flex: 1; min-width: 0; }
        .page-content { padding: 26px 30px 70px; max-width: 1240px; }

        /* ---------- hero ---------- */
        .settings-hero, .page-hero {
            position: relative; overflow: hidden; border-radius: 24px; padding: 26px 28px; margin-bottom: 22px;
            color: #fff;
            background:
                radial-gradient(circle at 12% 18%, rgba(255,255,255,.22), transparent 24%),
                radial-gradient(circle at 88% 12%, rgba(251,191,36,.26), transparent 24%),
                linear-gradient(135deg, #111827 0%, #7c2d12 48%, #f97316 100%);
            box-shadow: 0 22px 55px rgba(249,115,22,.20);
            display: flex; justify-content: space-between; align-items: flex-start; gap: 18px; flex-wrap: wrap;
        }
        .settings-hero::after, .page-hero::after {
            content: ""; position: absolute; right: -80px; bottom: -120px; width: 260px; height: 260px;
            border-radius: 50%; background: rgba(255,255,255,.13);
        }
        .settings-hero > *, .page-hero > * { position: relative; z-index: 1; }
        .settings-hero h1, .page-hero h1 {
            font-size: clamp(22px, 3vw, 30px); font-weight: 800; letter-spacing: -.03em; margin: 0 0 6px; color: #fff;
        }
        .settings-hero p, .page-hero p { color: rgba(255,255,255,.8); margin: 0; font-size: 13.5px; max-width: 720px; line-height: 1.55; }
        .settings-eyebrow, .page-eyebrow {
            display: inline-flex; align-items: center; gap: 8px; padding: 7px 13px; margin-bottom: 12px;
            border-radius: 999px; background: rgba(255,255,255,.16); border: 1px solid rgba(255,255,255,.22);
            font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; color: #fff;
        }
        .settings-hero .btn, .page-hero .btn {
            border-color: rgba(255,255,255,.35); background: rgba(255,255,255,.14); color: #fff; backdrop-filter: blur(8px);
        }
        .settings-hero .btn:hover, .page-hero .btn:hover { background: rgba(255,255,255,.24); color: #fff; }
        .settings-hero .btn-light { background: #fff; color: #111827; border: 0; font-weight: 700; }

        /* ---------- cards ---------- */
        .settings-shell { display: flex; flex-direction: column; gap: 18px; }
        .table-card, .stat-card, .settings-card, .settings-link-card {
            background: #fff; border: 1px solid rgba(15,23,42,.08); border-radius: 20px;
            box-shadow: 0 16px 42px rgba(15,23,42,.06); transition: transform .18s, box-shadow .18s;
        }
        .table-card:hover, .stat-card:hover { transform: translateY(-2px); box-shadow: 0 22px 55px rgba(15,23,42,.10); }
        .stat-card { padding: 18px 20px; position: relative; overflow: hidden; }
        .stat-card::after {
            content: ""; position: absolute; right: -34px; top: -34px; width: 104px; height: 104px;
            border-radius: 50%; background: rgba(139,92,246,.10); pointer-events: none;
        }
        .stat-card .k, .stat-card small { font-size: 11px; color: #94A3B8; text-transform: uppercase; letter-spacing: .05em; font-weight: 700; }
        .stat-card .v { font-size: 22px; font-weight: 800; margin-top: 4px; color: var(--ink); }
        .stat-card .icon {
            width: 44px; height: 44px; border-radius: 12px; display: grid; place-items: center; font-size: 18px;
            background: rgba(139,92,246,.10); color: var(--primary);
        }
        .table-card .card-header, .settings-card-header {
            padding: 16px 20px; border-bottom: 1px solid rgba(15,23,42,.07); font-weight: 700; color: var(--ink);
            background: rgba(248,250,252,.6);
        }
        .table-card .card-header h5, .settings-card-title { font-size: 15px; font-weight: 800; margin: 0; letter-spacing: -.01em; color: var(--ink); }
        .settings-card-subtitle { color: var(--muted); font-size: 12.5px; margin: 2px 0 0; }
        .settings-card-body { padding: 18px 20px; }
        .settings-card { margin-bottom: 0; }
        .settings-section-title { font-weight: 700; font-size: 13px; margin: 6px 0 10px; color: var(--ink); }

        /* ---------- grid helpers ---------- */
        .settings-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 14px; }
        .settings-field { grid-column: span 12; }
        @media (min-width: 768px) {
            .settings-span-3 { grid-column: span 3; } .settings-span-4 { grid-column: span 4; }
            .settings-span-5 { grid-column: span 5; } .settings-span-6 { grid-column: span 6; }
            .settings-span-7 { grid-column: span 7; } .settings-span-12 { grid-column: span 12; }
        }

        /* ---------- tabs ---------- */
        .settings-tabs { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 20px; overflow-x: auto; padding-bottom: 4px; }
        .settings-tab {
            display: inline-flex; align-items: center; gap: 8px; padding: 9px 15px; border-radius: 12px;
            border: 1px solid rgba(148,163,184,.3); background: #fff; color: #556; font-size: 12.5px; font-weight: 600;
        }
        .settings-tab:hover { border-color: var(--primary-light); color: var(--primary); }
        .settings-tab.active { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); color: #fff; border-color: transparent; box-shadow: 0 10px 22px rgba(139,92,246,.28); }

        /* ---------- tables ---------- */
        .table { margin-bottom: 0; }
        .table thead th {
            border: 0; background: #F8FAFC; color: #64748B; font-size: 11px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .05em; padding: 12px 16px;
        }
        .table tbody td { padding: 12px 16px; border-color: #F1F5F9; font-size: 13.5px; vertical-align: middle; }
        .table tbody tr:hover td { background: #FCFCFD; }
        table.grid { width: 100%; border-collapse: collapse; }
        table.grid th {
            text-align: left; font-size: 10.5px; text-transform: uppercase; letter-spacing: .05em; color: #94A3B8;
            padding: 10px 14px; border-bottom: 1px solid var(--line); font-weight: 700;
        }
        table.grid td { padding: 11px 14px; border-bottom: 1px solid #F1F5F9; font-size: 13.5px; }
        table.grid tbody tr:last-child td { border-bottom: 0; }
        .num { text-align: right; font-variant-numeric: tabular-nums; }

        /* ---------- controls ---------- */
        .form-control, .form-select {
            border-radius: 10px; border: 1px solid #E2E8F0; padding: 9px 12px; font-size: 13.5px;
        }
        .form-control:focus, .form-select:focus { border-color: var(--primary-light); box-shadow: 0 0 0 4px rgba(139,92,246,.12); }
        .form-label { font-size: 12px; font-weight: 600; color: var(--muted); margin-bottom: 5px; }
        .btn { border-radius: 10px; font-weight: 600; font-size: 13.5px; padding: 8px 16px; }
        .btn-primary, .btn-success { background: linear-gradient(135deg, var(--primary), var(--primary-dark)); border: 0; box-shadow: 0 10px 22px rgba(139,92,246,.26); }
        .btn-primary:hover, .btn-success:hover { filter: brightness(1.05); }
        .btn-sm { padding: 6px 12px; font-size: 12.5px; border-radius: 9px; }
        .badge { font-weight: 700; border-radius: 8px; padding: 5px 9px; font-size: 11px; }
        .alert { border-radius: 14px; border: 0; font-size: 13.5px; }
        .alert-success { background: #ECFDF5; color: #065F46; }
        .alert-danger { background: #FEF2F2; color: #991B1B; }
        .alert-warning { background: #FFFBEB; color: #92400E; }

        @media (max-width: 860px) {
            .sidebar { position: fixed; z-index: 1050; transform: translateX(-100%); transition: transform .25s; }
            .sidebar.open { transform: none; }
            .page-content { padding: 18px 16px 60px; }
            .mobile-topbar { display: flex !important; }
        }
        .mobile-topbar {
            display: none; align-items: center; gap: 12px; padding: 12px 16px; background: #fff;
            border-bottom: 1px solid var(--line); position: sticky; top: 0; z-index: 1040;
        }
    </style>
    @yield('styles')
</head>
<body>
<div class="app-shell">
    @auth @include('accounting._sidebar') @endauth
    <div class="main-content">
        <div class="mobile-topbar">
            <button class="btn btn-sm btn-outline-secondary" onclick="document.querySelector('.sidebar').classList.toggle('open')"><i class="fas fa-bars"></i></button>
            <strong>{{ config('app.name') }}</strong>
        </div>
        <div class="page-content">
            @if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i>{{ session('success') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
            @if(session('error'))<div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button class="btn-close" data-bs-dismiss="alert"></button></div>@endif
            @if($errors->any())<div class="alert alert-danger"><ul class="mb-0 ps-3">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
            @yield('content')
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
@yield('scripts')
</body>
</html>
