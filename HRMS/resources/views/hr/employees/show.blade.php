@extends('layouts.app')
@section('title', $employee->name)
@section('content')
@php $sym = \App\Models\HrSetting::get('currency_symbol', '₹'); @endphp

<div class="settings-hero">
    <div>
        <div class="settings-eyebrow">Employee · {{ $employee->employee_code }}</div>
        <h1>{{ $employee->name }}</h1>
        <p>{{ $employee->designation?->name ?: '—' }} @if($employee->department) · {{ $employee->department->name }} @endif · joined {{ optional($employee->date_of_joining)->format('d M Y') }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('hr.employees.edit', $employee) }}" class="btn btn-outline-success">Edit</a>
        <a href="{{ route('hr.employees.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-5">
        <div class="table-card">
            <div class="card-header">Details</div>
            <div class="settings-card-body">
                <table class="grid">
                    <tr><td>Status</td><td><span class="badge bg-{{ $employee->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst(str_replace('_',' ',$employee->status)) }}</span></td></tr>
                    <tr><td>Login email</td><td>{{ $employee->user?->email ?: '—' }}</td></tr>
                    <tr><td>Phone</td><td>{{ $employee->phone ?: '—' }}</td></tr>
                    <tr><td>PAN</td><td>{{ $employee->pan ?: '—' }}</td></tr>
                    <tr><td>PF UAN</td><td>{{ $employee->pf_uan ?: '—' }}</td></tr>
                    <tr><td>ESI IP</td><td>{{ $employee->esi_ic_number ?: '—' }}</td></tr>
                    <tr><td>Bank</td><td>{{ $employee->bank_account_no ?: '—' }} / {{ $employee->bank_ifsc ?: '—' }}</td></tr>
                    <tr><td>Tax regime</td><td>{{ ucfirst($employee->tax_regime) }}</td></tr>
                </table>
                <form method="POST" action="{{ route('hr.employees.deactivate', $employee) }}" class="mt-3 d-flex gap-2"
                      onsubmit="return confirm('Mark this employee as exited?')">
                    @csrf
                    <select name="status" class="form-select form-select-sm">
                        <option value="resigned">Resigned</option>
                        <option value="terminated">Terminated</option>
                        <option value="on_notice">On notice</option>
                    </select>
                    <input type="date" name="date_of_exit" class="form-control form-control-sm">
                    <button class="btn btn-sm btn-outline-danger">Apply</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="table-card mb-3">
            <div class="card-header">Current salary structure</div>
            <div class="settings-card-body">
                @php $cur = $employee->structures->firstWhere('is_current', true); @endphp
                @if($cur)
                    <p class="small text-muted mb-2">Effective {{ optional($cur->effective_from)->format('d M Y') }} · CTC {{ $sym }} {{ number_format((float) $cur->ctc_annual, 2) }}</p>
                    <table class="grid">
                        <thead><tr><th>Component</th><th>Calc</th><th class="num">Value</th></tr></thead>
                        <tbody>
                        @foreach($cur->earnings as $e)
                            <tr><td>{{ $e['label'] ?? $e['code'] }}</td><td>{{ $e['calc'] === 'pct_basic' ? '% of Basic' : 'Flat' }}</td><td class="num">{{ number_format((float) $e['value'], 2) }}</td></tr>
                        @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="text-muted mb-0">No salary structure set. <a href="{{ route('hr.employees.edit', $employee) }}">Add one</a>.</p>
                @endif
            </div>
        </div>
        <div class="table-card">
            <div class="card-header">Payslip history</div>
            <table class="grid">
                <thead><tr><th>Period</th><th>Status</th><th class="num">Gross</th><th class="num">Net</th><th></th></tr></thead>
                <tbody>
                @forelse($employee->payslips->sortByDesc(fn($p) => $p->run->period) as $p)
                    <tr>
                        <td>{{ \Illuminate\Support\Carbon::parse($p->run->period.'-01')->format('M Y') }}</td>
                        <td><span class="badge bg-secondary">{{ $p->run->status }}</span></td>
                        <td class="num">{{ number_format((float) $p->gross, 2) }}</td>
                        <td class="num">{{ number_format((float) $p->net_pay, 2) }}</td>
                        <td>@if(in_array($p->run->status, ['approved','paid','posted']))<a href="{{ route('hr.payslips.pdf', $p) }}" class="btn btn-sm btn-outline-success">PDF</a>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-3">No payslips yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
