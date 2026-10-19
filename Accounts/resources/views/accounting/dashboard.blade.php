@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
@php $m = fn ($v) => $symbol . number_format((float) $v, $decimals); @endphp

<div class="settings-hero">
    <div>
        <span class="settings-eyebrow"><i class="fas fa-calculator"></i> Business Accounting</span>
        <h1>Accounts Overview</h1>
        <p>{{ $from }} &rarr; {{ $to }} &middot; Financial year {{ $fy }}. Live from the general ledger, tax ledger and compliance calendar.</p>
    </div>
    <a href="{{ route('accounting.gl.balance-sheet') }}" class="btn btn-light"><i class="fas fa-scale-unbalanced me-2"></i>Balance Sheet</a>
</div>

@unless($accounting_on)
    <div class="alert alert-warning mb-4"><i class="fas fa-triangle-exclamation me-2"></i>The general ledger is off. Financial statements stay empty until it is enabled in the admin business settings and <code>php artisan ledger:reconcile</code> has run.</div>
@endunless

<div class="row g-3 mb-4">
    @foreach([
        ['Bank &amp; cash', $m($bank_balance), 'fa-building-columns', 'primary', null],
        ['Net profit (FY to date)', $m($net_profit_fytd), 'fa-arrow-trend-up', $net_profit_fytd >= 0 ? 'success' : 'danger', $net_profit_fytd >= 0 ? 'text-success' : 'text-danger'],
        ['GST output (period)', $m($gst_payable), 'fa-file-invoice', 'info', null],
        ['TDS + TCS (period)', $m($tds_payable + $tcs_payable), 'fa-hand-holding-dollar', 'warning', null],
    ] as [$label, $value, $icon, $tone, $vClass])
        <div class="col-6 col-lg-3">
            <div class="stat-card h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="k">{!! $label !!}</div>
                        <div class="v {{ $vClass }}">{{ $value }}</div>
                    </div>
                    <div class="icon" style="background: rgba(139,92,246,.10)"><i class="fas {{ $icon }}"></i></div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="table-card h-100">
            <div class="card-header"><h5>Jump to</h5></div>
            <div class="settings-card-body d-flex flex-wrap gap-2">
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('accounting.gl.balance-sheet') }}">Balance Sheet</a>
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('accounting.gl.profit-loss') }}">Profit &amp; Loss</a>
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('accounting.gl.cash-flow') }}">Cash Flow</a>
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('accounting.gl.trial-balance') }}">Trial Balance</a>
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('accounting.gl.journals') }}">Journals</a>
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('accounting.gst') }}">GST</a>
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('accounting.tds') }}">TDS</a>
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('accounting.compliance') }}">Compliance</a>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="table-card h-100">
            <div class="card-header"><h5>Attention</h5></div>
            <table class="grid">
                <tr><td>Balance sheet</td><td class="num">{!! $balanced ? '<span class="badge bg-success">Balanced</span>' : '<span class="badge bg-warning text-dark">Unreconciled '.$m($suspense).'</span>' !!}</td></tr>
                <tr><td>Gig cess not remitted</td><td class="num">{{ $m($unfiled_cess) }}</td></tr>
                <tr><td>Compliance — pending</td><td class="num">{{ (int) ($compliance['pending'] ?? 0) }}</td></tr>
                <tr><td>Compliance — overdue</td><td class="num text-danger">{{ (int) ($compliance['overdue'] ?? 0) }}</td></tr>
            </table>
        </div>
    </div>
</div>
@endsection
