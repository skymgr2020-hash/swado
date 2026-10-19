@extends('layouts.admin')

@section('title', 'Dashboard')

@section('styles')
<style>
    .support-kpi-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 18px;
    }

    @media (max-width: 1100px) {
        .support-kpi-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 640px) {
        .support-kpi-grid {
            grid-template-columns: 1fr;
        }
    }

    .support-kpi-grid .kpi-card {
        border: 1px solid rgba(226, 232, 240, .88);
        background:
            linear-gradient(180deg, rgba(255,255,255,.96), rgba(255,255,255,.88)),
            radial-gradient(circle at top right, var(--card-glow, rgba(124,58,237,.12)), transparent 42%);
        box-shadow: 0 22px 55px rgba(15, 23, 42, .07);
        position: relative;
        min-height: 150px;
        padding: 22px;
        border-radius: 24px;
        overflow: hidden;
    }

    .support-kpi-grid .kpi-card::after {
        content: "";
        position: absolute;
        inset: auto -34px -56px auto;
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: var(--accent);
        opacity: .09;
    }

    .support-kpi-grid .kpi-icon {
        width: 48px;
        height: 48px;
        border-radius: 18px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: var(--accent);
        background: color-mix(in srgb, var(--accent) 14%, white);
        box-shadow: inset 0 1px 0 rgba(255,255,255,.8);
        font-size: 19px;
        position: relative;
        z-index: 1;
    }

    .support-kpi-grid .kpi-label {
        color: #64748b;
        font-weight: 800;
        font-size: 13px;
        margin-top: 18px;
        position: relative;
        z-index: 1;
    }

    .support-kpi-grid .kpi-value {
        color: #0f172a;
        font-size: 30px;
        font-weight: 950;
        letter-spacing: -.03em;
        line-height: 1.05;
        margin-top: 4px;
        position: relative;
        z-index: 1;
    }
</style>
@endsection

@section('content')
<div class="page-header">
    <h1>Dashboard</h1>
    <p class="text-muted">Live operational status -- running orders, tickets, and driver assignment.</p>
</div>

<div class="support-kpi-grid">
    <div class="kpi-card" style="--accent:#3b82f6;--card-glow:rgba(59,130,246,.15);">
        <div class="kpi-icon"><i class="fas fa-bolt"></i></div>
        <div class="kpi-label">Running Orders</div>
        <div class="kpi-value">{{ number_format($stats['running_orders']) }}</div>
    </div>

    <div class="kpi-card" style="--accent:#8b5cf6;--card-glow:rgba(139,92,246,.16);">
        <div class="kpi-icon"><i class="fas fa-headset"></i></div>
        <div class="kpi-label">Open Tickets</div>
        <div class="kpi-value">{{ number_format($stats['open_tickets']) }}</div>
    </div>

    <div class="kpi-card" style="--accent:#22c55e;--card-glow:rgba(34,197,94,.14);">
        <div class="kpi-icon"><i class="fas fa-motorcycle"></i></div>
        <div class="kpi-label">Drivers Assigned</div>
        <div class="kpi-value">{{ number_format($stats['drivers_assigned']) }}</div>
    </div>

    <div class="kpi-card" style="--accent:#f59e0b;--card-glow:rgba(245,158,11,.16);">
        <div class="kpi-icon"><i class="fas fa-clock"></i></div>
        <div class="kpi-label">Drivers Pending to Accept</div>
        <div class="kpi-value">{{ number_format($stats['drivers_pending']) }}</div>
    </div>

    <div class="kpi-card" style="--accent:#14b8a6;--card-glow:rgba(20,184,166,.14);">
        <div class="kpi-icon"><i class="fas fa-circle-check"></i></div>
        <div class="kpi-label">Delivered Today</div>
        <div class="kpi-value">{{ number_format($stats['delivered_today']) }}</div>
    </div>

    <div class="kpi-card" style="--accent:#ef4444;--card-glow:rgba(239,68,68,.15);">
        <div class="kpi-icon"><i class="fas fa-ban"></i></div>
        <div class="kpi-label">Cancelled Today</div>
        <div class="kpi-value">{{ number_format($stats['cancelled_today']) }}</div>
    </div>
</div>
@endsection
