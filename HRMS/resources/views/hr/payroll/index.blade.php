@extends('layouts.app')
@section('title', 'Payroll Runs')
@section('content')
<div class="settings-hero">
    <div>
        <div class="settings-eyebrow">HR</div>
        <h1>Payroll Runs</h1>
        <p>Open a run for a month, review payslips, approve (accrues in Accounts) and mark paid.</p>
    </div>
    <form method="POST" action="{{ route('hr.payroll.store') }}" class="d-flex gap-2">
        @csrf
        <input type="month" name="period" class="form-control" value="{{ now()->format('Y-m') }}" required>
        <button class="btn btn-success"><i class="fas fa-plus me-2"></i>Open run</button>
    </form>
</div>

<div class="table-card">
    <table class="grid">
        <thead><tr><th>Period</th><th>Status</th><th class="num">Headcount</th><th class="num">Gross</th><th class="num">Net</th><th class="num">Employer cost</th><th>Accounts ref</th><th></th></tr></thead>
        <tbody>
        @forelse($runs as $r)
            <tr>
                <td>{{ \Illuminate\Support\Carbon::parse($r->period.'-01')->format('M Y') }}</td>
                <td><span class="badge bg-{{ in_array($r->status,['paid','posted']) ? 'success' : ($r->status==='approved' ? 'primary' : 'secondary') }}">{{ $r->status }}</span></td>
                <td class="num">{{ $r->payslips_count }}</td>
                <td class="num">{{ number_format((float) ($r->totals['gross'] ?? 0), 2) }}</td>
                <td class="num">{{ number_format((float) ($r->totals['net'] ?? 0), 2) }}</td>
                <td class="num">{{ number_format((float) ($r->totals['employer_cost'] ?? 0), 2) }}</td>
                <td class="small text-muted">{{ $r->accrual_journal_ref ? '✓ accrual' : '—' }} {{ $r->payment_journal_ref ? '· ✓ paid' : '' }}</td>
                <td><a href="{{ route('hr.payroll.show', $r) }}" class="btn btn-sm btn-outline-success">Open</a></td>
            </tr>
        @empty
            <tr><td colspan="8" class="text-center text-muted py-4">No payroll runs yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $runs->links() }}</div>
@endsection
