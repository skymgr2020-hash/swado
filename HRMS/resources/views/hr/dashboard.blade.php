@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
@php $c = fn($n) => $symbol.' '.number_format((float) $n, $decimals); @endphp

<div class="settings-hero">
    <div>
        <span class="settings-eyebrow"><i class="fas fa-user-group"></i> HR &amp; Payroll</span>
        <h1>Dashboard</h1>
        <p>Headcount, the last payroll run and statutory dues at a glance.</p>
    </div>
    <a href="{{ route('hr.payroll.index') }}" class="btn btn-success"><i class="fas fa-money-check-dollar me-2"></i>Payroll runs</a>
</div>

<div class="row g-3 mb-4">
    @foreach([
        ['Active employees', $headcount, 'fa-user-check'],
        ['On roll (total)', $total_employees, 'fa-users'],
        ['Pending leave', $pending_leave, 'fa-plane-departure'],
        ['Last run cost', $c($monthly_cost), 'fa-sack-dollar'],
    ] as [$k, $v, $icon])
        <div class="col-6 col-lg-3">
            <div class="stat-card h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div><div class="k">{{ $k }}</div><div class="v">{{ $v }}</div></div>
                    <div class="icon"><i class="fas {{ $icon }}"></i></div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="table-card h-100">
            <div class="card-header"><h5>Last payroll run</h5></div>
            <div class="settings-card-body">
                @if($last_run)
                    <p class="mb-2"><strong>{{ \Illuminate\Support\Carbon::parse($last_run->period.'-01')->format('F Y') }}</strong>
                        <span class="badge bg-{{ in_array($last_run->status,['paid','posted']) ? 'success' : ($last_run->status==='approved' ? 'primary' : 'secondary') }} text-uppercase">{{ $last_run->status }}</span></p>
                    <table class="grid">
                        <tr><td>Headcount</td><td class="num">{{ $last_run->totals['headcount'] ?? 0 }}</td></tr>
                        <tr><td>Gross</td><td class="num">{{ $c($last_run->totals['gross'] ?? 0) }}</td></tr>
                        <tr><td>Net pay</td><td class="num">{{ $c($last_run->totals['net'] ?? 0) }}</td></tr>
                        <tr><td>Employer cost</td><td class="num">{{ $c($last_run->totals['employer_cost'] ?? 0) }}</td></tr>
                    </table>
                    <a href="{{ route('hr.payroll.show', $last_run) }}" class="btn btn-sm btn-outline-success mt-3">Open run</a>
                @else
                    <p class="text-muted mb-0">No payroll run yet.</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="table-card h-100">
            <div class="card-header"><h5>Statutory dues (last run)</h5></div>
            <table class="grid">
                <tr><td>Provident Fund (EE + ER)</td><td class="num">{{ $c($statutory_due['pf']) }}</td></tr>
                <tr><td>ESI (EE + ER)</td><td class="num">{{ $c($statutory_due['esi']) }}</td></tr>
                <tr><td>Professional Tax</td><td class="num">{{ $c($statutory_due['pt']) }}</td></tr>
                <tr><td>TDS u/s 192</td><td class="num">{{ $c($statutory_due['tds']) }}</td></tr>
            </table>
            <div class="settings-card-body pt-0">
                <a href="{{ route('hr.reports.index') }}" class="btn btn-sm btn-outline-secondary">Statutory reports</a>
            </div>
        </div>
    </div>
</div>
@endsection
