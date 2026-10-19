@extends('layouts.app')
@section('title', 'Payslips')
@section('content')
<div class="settings-hero">
    <div>
        <div class="settings-eyebrow">HR</div>
        <h1>Payslips</h1>
        <p>All generated payslips across payroll runs.</p>
    </div>
</div>

<div class="table-card">
    <table class="grid">
        <thead><tr><th>Period</th><th>Employee</th><th>Run status</th><th class="num">Gross</th><th class="num">Net</th><th></th></tr></thead>
        <tbody>
        @forelse($payslips as $p)
            <tr>
                <td>{{ \Illuminate\Support\Carbon::parse($p->run->period.'-01')->format('M Y') }}</td>
                <td>{{ $p->employee->name }} <span class="small text-muted">({{ $p->employee->employee_code }})</span></td>
                <td><span class="badge bg-secondary">{{ $p->run->status }}</span></td>
                <td class="num">{{ number_format((float) $p->gross, 2) }}</td>
                <td class="num">{{ number_format((float) $p->net_pay, 2) }}</td>
                <td>@if(in_array($p->run->status, ['approved','paid','posted']))<a href="{{ route('hr.payslips.pdf', $p) }}" class="btn btn-sm btn-outline-success">PDF</a>@endif</td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-4">No payslips yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $payslips->links() }}</div>
@endsection
