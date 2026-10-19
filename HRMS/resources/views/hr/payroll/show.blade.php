@extends('layouts.app')
@section('title', 'Payroll ' . $run->period)
@section('content')
@php $c = fn($n) => $symbol.' '.number_format((float) $n, $decimals); $t = $run->totals ?? []; @endphp

<div class="settings-hero">
    <div>
        <div class="settings-eyebrow">Payroll run</div>
        <h1>{{ \Illuminate\Support\Carbon::parse($run->period.'-01')->format('F Y') }}
            <span class="badge bg-{{ in_array($run->status,['paid','posted']) ? 'success' : ($run->status==='approved' ? 'primary' : 'secondary') }}">{{ $run->status }}</span>
        </h1>
        <p>{{ $t['headcount'] ?? 0 }} employees · gross {{ $c($t['gross'] ?? 0) }} · net {{ $c($t['net'] ?? 0) }}</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('hr.payroll.register', $run) }}" class="btn btn-outline-secondary btn-sm">Register .xlsx</a>
        <a href="{{ route('hr.payroll.bank-advice', $run) }}" class="btn btn-outline-secondary btn-sm">Bank advice .xlsx</a>
        @if($run->isEditable())
            <form method="POST" action="{{ route('hr.payroll.recalculate', $run) }}">@csrf<button class="btn btn-outline-primary btn-sm">Recalculate</button></form>
            <form method="POST" action="{{ route('hr.payroll.approve', $run) }}" onsubmit="return confirm('Approve this run? An accrual journal will be queued to Accounts.')">@csrf<button class="btn btn-success btn-sm">Approve</button></form>
            <form method="POST" action="{{ route('hr.payroll.cancel', $run) }}" onsubmit="return confirm('Cancel this draft run?')">@csrf<button class="btn btn-outline-danger btn-sm">Cancel</button></form>
        @elseif(in_array($run->status, ['approved','posted']))
            <form method="POST" action="{{ route('hr.payroll.mark-paid', $run) }}" class="d-flex gap-2" onsubmit="return confirm('Mark paid? A payment journal will be queued to Accounts.')">
                @csrf<input type="date" name="pay_date" class="form-control form-control-sm" value="{{ now()->toDateString() }}">
                <button class="btn btn-success btn-sm">Mark paid</button>
            </form>
        @endif
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-2"><div class="stat-card"><div class="k">PF (EE+ER)</div><div class="v" style="font-size:16px">{{ $c(($t['pf_ee'] ?? 0) + ($t['pf_er'] ?? 0)) }}</div></div></div>
    <div class="col-6 col-md-2"><div class="stat-card"><div class="k">ESI (EE+ER)</div><div class="v" style="font-size:16px">{{ $c(($t['esi_ee'] ?? 0) + ($t['esi_er'] ?? 0)) }}</div></div></div>
    <div class="col-6 col-md-2"><div class="stat-card"><div class="k">PT</div><div class="v" style="font-size:16px">{{ $c($t['pt'] ?? 0) }}</div></div></div>
    <div class="col-6 col-md-2"><div class="stat-card"><div class="k">TDS 192</div><div class="v" style="font-size:16px">{{ $c($t['tds_192'] ?? 0) }}</div></div></div>
    <div class="col-6 col-md-2"><div class="stat-card"><div class="k">Deductions</div><div class="v" style="font-size:16px">{{ $c($t['deductions'] ?? 0) }}</div></div></div>
    <div class="col-6 col-md-2"><div class="stat-card"><div class="k">Employer cost</div><div class="v" style="font-size:16px">{{ $c($t['employer_cost'] ?? 0) }}</div></div></div>
</div>

<div class="table-card">
    <table class="grid">
        <thead><tr><th>Employee</th><th class="num">Paid / LOP</th><th class="num">Gross</th><th class="num">PF</th><th class="num">ESI</th><th class="num">PT</th><th class="num">TDS 192</th><th class="num">Net</th><th></th></tr></thead>
        <tbody>
        @foreach($run->payslips as $s)
            @php $d = fn($code) => collect($s->deductions)->firstWhere('code', $code)['amount'] ?? 0; @endphp
            <tr>
                <td>{{ $s->employee->name }}<br><span class="small text-muted">{{ $s->employee->employee_code }}</span></td>
                <td class="num">{{ (float) $s->paid_days }} / {{ (float) $s->lop_days }}</td>
                <td class="num">{{ number_format((float) $s->gross, 2) }}</td>
                <td class="num">{{ number_format((float) $d('pf'), 2) }}</td>
                <td class="num">{{ number_format((float) $d('esi'), 2) }}</td>
                <td class="num">{{ number_format((float) $d('pt'), 2) }}</td>
                <td class="num">{{ number_format((float) $s->tds_192, 2) }}</td>
                <td class="num"><strong>{{ number_format((float) $s->net_pay, 2) }}</strong></td>
                <td>
                    @if($run->isEditable())
                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#e{{ $s->id }}">Edit</button>
                    @elseif(in_array($run->status, ['approved','paid','posted']))
                        <a href="{{ route('hr.payslips.pdf', $s) }}" class="btn btn-sm btn-outline-success">PDF</a>
                    @endif
                </td>
            </tr>
            @if($run->isEditable())
                <tr class="collapse" id="e{{ $s->id }}">
                    <td colspan="9">
                        <form method="POST" action="{{ route('hr.payroll.payslip.update', [$run, $s]) }}" class="row g-2">
                            @csrf @method('PUT')
                            <div class="col-md-3"><label class="form-label small">LOP days</label><input name="lop_days" type="number" step="0.5" class="form-control form-control-sm" value="{{ (float) $s->lop_days }}"></div>
                            <div class="col-md-3"><label class="form-label small">TDS 192 override</label><input name="tds_192" type="number" step="0.01" class="form-control form-control-sm" value="{{ (float) $s->tds_192 }}"></div>
                            <div class="col-md-2 d-flex align-items-end"><button class="btn btn-sm btn-success w-100">Save</button></div>
                        </form>
                    </td>
                </tr>
            @endif
        @endforeach
        </tbody>
    </table>
</div>
@endsection
