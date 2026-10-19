@extends('layouts.app')
@section('title', 'Statutory Reports')
@section('content')
<div class="settings-hero">
    <div>
        <div class="settings-eyebrow">HR</div>
        <h1>Statutory Reports</h1>
        <p>PF ECR, ESI, PT worksheets per run; Form 24Q &amp; Form 16 for the financial year.</p>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="settings-card">
            <div class="settings-card-header">Monthly contribution worksheets</div>
            <div class="settings-card-body">
                <form method="GET" action="{{ route('hr.reports.statutory', 'pf') }}" id="statForm" class="row g-2">
                    <div class="col-md-5">
                        <label class="form-label small">Payroll run</label>
                        <select name="period" class="form-select">
                            @foreach($runs as $r)
                                <option value="{{ $r->period }}">{{ \Illuminate\Support\Carbon::parse($r->period.'-01')->format('M Y') }} ({{ $r->status }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-7 d-flex align-items-end gap-2">
                        <button formaction="{{ route('hr.reports.statutory', 'pf') }}" class="btn btn-outline-success btn-sm">PF ECR</button>
                        <button formaction="{{ route('hr.reports.statutory', 'esi') }}" class="btn btn-outline-success btn-sm">ESI</button>
                        <button formaction="{{ route('hr.reports.statutory', 'pt') }}" class="btn btn-outline-success btn-sm">PT</button>
                    </div>
                </form>
                @if($runs->isEmpty())<p class="small text-muted mt-2 mb-0">Approve a payroll run first.</p>@endif
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="settings-card">
            <div class="settings-card-header">Annual — FY {{ $fy }}</div>
            <div class="settings-card-body">
                <form method="GET" action="{{ route('hr.reports.form24q') }}" class="mb-3 d-flex gap-2">
                    <input name="fy" class="form-control form-control-sm" value="{{ $fy }}">
                    <button class="btn btn-outline-success btn-sm">Form 24Q .xlsx</button>
                </form>
                <form method="GET" action="{{ route('hr.reports.form16', $employees->first() ?: 0) }}" id="f16" class="d-flex gap-2">
                    <select name="employee" class="form-select form-select-sm" onchange="document.getElementById('f16').action='{{ url('hr/reports/form16') }}/'+this.value">
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}">{{ $e->name }} ({{ $e->employee_code }})</option>
                        @endforeach
                    </select>
                    <input name="fy" class="form-control form-control-sm" value="{{ $fy }}" style="max-width:110px">
                    <button class="btn btn-outline-success btn-sm">Form 16 PDF</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
