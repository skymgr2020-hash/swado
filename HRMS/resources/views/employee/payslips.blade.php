@extends('layouts.app')
@section('title', 'My Payslips')
@section('content')
@php $c = fn($n) => $symbol.' '.number_format((float) $n, $decimals); @endphp
<div class="settings-hero">
    <div>
        <div class="settings-eyebrow">Self-service</div>
        <h1>My Payslips</h1>
        <p>Only approved and paid months are shown.</p>
    </div>
</div>

<div class="table-card">
    <table class="grid">
        <thead><tr><th>Period</th><th>Status</th><th class="num">Paid days</th><th class="num">Gross</th><th class="num">Deductions</th><th class="num">Net</th><th></th></tr></thead>
        <tbody>
        @forelse($payslips as $p)
            <tr>
                <td>{{ \Illuminate\Support\Carbon::parse($p->run->period.'-01')->format('F Y') }}</td>
                <td><span class="badge bg-secondary">{{ $p->run->status }}</span></td>
                <td class="num">{{ (float) $p->paid_days }}</td>
                <td class="num">{{ number_format((float) $p->gross, 2) }}</td>
                <td class="num">{{ number_format((float) $p->total_deductions, 2) }}</td>
                <td class="num"><strong>{{ number_format((float) $p->net_pay, 2) }}</strong></td>
                <td><a href="{{ route('employee.payslips.pdf', $p) }}" class="btn btn-sm btn-outline-success">PDF</a></td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-muted py-4">No payslips available yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $payslips->links() }}</div>
@endsection
