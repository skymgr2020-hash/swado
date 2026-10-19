@extends('layouts.app')
@section('title', 'My Portal')
@section('content')
@php $c = fn($n) => $symbol.' '.number_format((float) $n, $decimals); @endphp

<div class="settings-hero">
    <div>
        <span class="settings-eyebrow"><i class="fas fa-id-badge"></i> Self-service</span>
        <h1>Hi, {{ $employee->first_name }}</h1>
        <p>{{ $employee->designation?->name ?: 'Employee' }} @if($employee->department) · {{ $employee->department->name }} @endif · {{ $employee->employee_code }}</p>
    </div>
</div>

<div class="row g-3 mb-4">
    @foreach([
        ['Latest net pay', $latest_payslip ? $c($latest_payslip->net_pay) : '—', 'fa-wallet'],
        ['Latest period', $latest_payslip ? \Illuminate\Support\Carbon::parse($latest_payslip->run->period.'-01')->format('M Y') : '—', 'fa-calendar-check'],
        ['Pending leave', $pending_leave, 'fa-plane-departure'],
        ['Leave types', $balances->count(), 'fa-layer-group'],
    ] as [$k, $v, $icon])
        <div class="col-6 col-lg-3">
            <div class="stat-card h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div><div class="k">{{ $k }}</div><div class="v" style="font-size:18px">{{ $v }}</div></div>
                    <div class="icon"><i class="fas {{ $icon }}"></i></div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="table-card h-100">
            <div class="card-header"><h5>Leave balances</h5></div>
            <table class="grid">
                <thead><tr><th>Type</th><th class="num">Opening</th><th class="num">Accrued</th><th class="num">Used</th><th class="num">Available</th></tr></thead>
                <tbody>
                @forelse($balances as $b)
                    <tr>
                        <td>{{ $b->type->name }}</td>
                        <td class="num">{{ number_format((float) $b->opening, 1) }}</td>
                        <td class="num">{{ number_format((float) $b->accrued, 1) }}</td>
                        <td class="num">{{ number_format((float) $b->used, 1) }}</td>
                        <td class="num"><strong>{{ number_format($b->available, 1) }}</strong></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-3">No balances.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="table-card h-100">
            <div class="card-header"><h5>Quick links</h5></div>
            <div class="settings-card-body d-flex flex-column gap-2">
                <a href="{{ route('employee.payslips') }}" class="btn btn-outline-success"><i class="fas fa-file-invoice me-2"></i>My payslips</a>
                <a href="{{ route('employee.form16') }}" class="btn btn-outline-success"><i class="fas fa-file-contract me-2"></i>Form 16</a>
                <a href="{{ route('employee.leave') }}" class="btn btn-outline-success"><i class="fas fa-plane-departure me-2"></i>Apply for leave</a>
                <a href="{{ route('employee.profile') }}" class="btn btn-outline-secondary"><i class="fas fa-id-card me-2"></i>Update profile</a>
            </div>
        </div>
    </div>
</div>
@endsection
