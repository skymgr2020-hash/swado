@extends('layouts.app')
@section('title', 'Leave')
@section('content')
<div class="settings-hero">
    <div>
        <div class="settings-eyebrow">HR</div>
        <h1>Leave</h1>
        <p>Approve requests, manage leave types. Approved unpaid leave reduces paid days on the next payroll recalculation.</p>
    </div>
</div>

<div class="table-card mb-4">
    <div class="card-header">Pending requests</div>
    <table class="grid">
        <thead><tr><th>Employee</th><th>Type</th><th>From</th><th>To</th><th class="num">Days</th><th>Reason</th><th></th></tr></thead>
        <tbody>
        @forelse($pending as $r)
            <tr>
                <td>{{ $r->employee->name }}</td>
                <td>{{ $r->type->name }} @unless($r->type->paid)<span class="badge bg-warning text-dark">unpaid</span>@endunless</td>
                <td>{{ $r->start_date->format('d M Y') }}</td>
                <td>{{ $r->end_date->format('d M Y') }}</td>
                <td class="num">{{ (float) $r->days }}</td>
                <td class="small">{{ $r->reason ?: '—' }}</td>
                <td>
                    <form method="POST" action="{{ route('hr.leave.decide', $r) }}" class="d-flex gap-1">
                        @csrf
                        <input type="hidden" name="decision" value="approve">
                        <button class="btn btn-sm btn-success">Approve</button>
                    </form>
                    <form method="POST" action="{{ route('hr.leave.decide', $r) }}" class="d-flex gap-1 mt-1">
                        @csrf
                        <input type="hidden" name="decision" value="reject">
                        <button class="btn btn-sm btn-outline-danger">Reject</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-muted py-3">No pending requests.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="row g-3">
    <div class="col-md-7">
        <div class="settings-card">
            <div class="settings-card-header">Leave types</div>
            <div class="settings-card-body">
                <table class="grid mb-3">
                    <thead><tr><th>Name</th><th>Code</th><th>Accrual</th><th class="num">Quota</th><th>Paid</th></tr></thead>
                    <tbody>
                    @foreach($types as $tp)
                        <tr>
                            <td>{{ $tp->name }}</td><td>{{ $tp->code }}</td>
                            <td>{{ str_replace('_',' ',$tp->accrual) }}</td>
                            <td class="num">{{ (float) $tp->annual_quota }}</td>
                            <td>{{ $tp->paid ? 'Yes' : 'No' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                <form method="POST" action="{{ route('hr.leave.types.store') }}" class="row g-2">
                    @csrf
                    <div class="col-md-4"><input name="name" class="form-control form-control-sm" placeholder="Name" required></div>
                    <div class="col-md-2"><input name="code" class="form-control form-control-sm" placeholder="Code" required></div>
                    <div class="col-md-3">
                        <select name="accrual" class="form-select form-select-sm">
                            <option value="annual_fixed">Annual fixed</option>
                            <option value="monthly_accrual">Monthly accrual</option>
                            <option value="unpaid">Unpaid</option>
                        </select>
                    </div>
                    <div class="col-md-2"><input name="annual_quota" type="number" step="0.5" class="form-control form-control-sm" placeholder="Quota"></div>
                    <div class="col-md-1"><button class="btn btn-sm btn-success w-100">Add</button></div>
                    <div class="col-12 form-check ms-2"><input type="checkbox" name="paid" value="1" class="form-check-input" id="lp" checked><label for="lp" class="form-check-label small">Paid leave</label></div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="settings-card">
            <div class="settings-card-header">Balances ({{ now()->year }})</div>
            <div class="settings-card-body" style="max-height:420px;overflow:auto">
                @forelse($balances as $empId => $rows)
                    <p class="mb-1 fw-semibold">{{ $rows->first()->employee->name }}</p>
                    <table class="grid mb-3">
                        <tbody>
                        @foreach($rows as $b)
                            <tr><td>{{ $b->type->name }}</td><td class="num">{{ number_format($b->available, 1) }} left</td></tr>
                        @endforeach
                        </tbody>
                    </table>
                @empty
                    <p class="text-muted mb-0">No balances yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
