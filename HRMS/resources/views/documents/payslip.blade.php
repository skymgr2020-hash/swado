<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2733; }
        .wrap { border: 1px solid #333; padding: 0; }
        .head { background: #33475b; color: #fff; padding: 12px 16px; }
        .head h1 { margin: 0; font-size: 15px; }
        .head .sub { font-size: 10px; color: #cfd8e3; }
        .meta { width: 100%; border-collapse: collapse; }
        .meta td { padding: 5px 16px; border-bottom: 1px solid #e0e0e0; vertical-align: top; }
        .meta .label { color: #777; font-size: 9px; text-transform: uppercase; }
        table.lines { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.lines th { background: #eef1f4; text-align: left; padding: 6px 16px; font-size: 9px; text-transform: uppercase; }
        table.lines td { padding: 5px 16px; border-bottom: 1px solid #eee; }
        table.lines td.amt, table.lines th.amt { text-align: right; }
        .tot td { font-weight: bold; border-top: 2px solid #333; background: #f7f9fb; }
        .net { padding: 12px 16px; background: #eaf6f0; font-size: 13px; font-weight: bold; }
        .foot { padding: 10px 16px; color: #888; font-size: 9px; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="head">
        <h1>{{ $employer['name'] }}</h1>
        <div class="sub">PAN {{ $employer['pan'] ?: '—' }} &nbsp;|&nbsp; TAN {{ $employer['tan'] ?: '—' }}</div>
        <div class="sub">Payslip for {{ \Illuminate\Support\Carbon::parse($run->period.'-01')->format('F Y') }}</div>
    </div>

    <table class="meta">
        <tr>
            <td style="width:50%"><div class="label">Employee</div>{{ $employee->name }} ({{ $employee->employee_code }})</td>
            <td><div class="label">Designation</div>{{ $employee->designation?->name ?: '—' }}</td>
        </tr>
        <tr>
            <td><div class="label">Department</div>{{ $employee->department?->name ?: '—' }}</td>
            <td><div class="label">PAN</div>{{ $employee->pan ?: '—' }}</td>
        </tr>
        <tr>
            <td><div class="label">PF UAN</div>{{ $employee->pf_uan ?: '—' }}</td>
            <td><div class="label">Bank A/c</div>{{ $employee->bank_account_no ?: '—' }} ({{ $employee->bank_ifsc ?: '—' }})</td>
        </tr>
        <tr>
            <td><div class="label">Paid Days / Working Days</div>{{ (float) $payslip->paid_days }} / {{ (int) $payslip->working_days }}</td>
            <td><div class="label">Loss of Pay Days</div>{{ (float) $payslip->lop_days }}</td>
        </tr>
    </table>

    <table class="lines">
        <tr><th>Earnings</th><th class="amt">Amount ({{ $symbol }})</th><th>Deductions</th><th class="amt">Amount ({{ $symbol }})</th></tr>
        @php
            $earn = collect($payslip->earnings)->values();
            $ded = collect($payslip->deductions)->values();
            $rows = max($earn->count(), $ded->count());
        @endphp
        @for($i = 0; $i < $rows; $i++)
            <tr>
                <td>{{ $earn[$i]['label'] ?? '' }}</td>
                <td class="amt">{{ isset($earn[$i]) ? number_format((float) $earn[$i]['amount'], 2) : '' }}</td>
                <td>{{ $ded[$i]['label'] ?? '' }}</td>
                <td class="amt">{{ isset($ded[$i]) ? number_format((float) $ded[$i]['amount'], 2) : '' }}</td>
            </tr>
        @endfor
        <tr class="tot">
            <td>Gross Earnings</td><td class="amt">{{ number_format((float) $payslip->gross, 2) }}</td>
            <td>Total Deductions</td><td class="amt">{{ number_format((float) $payslip->total_deductions, 2) }}</td>
        </tr>
    </table>

    <div class="net">Net Pay: {{ $symbol }} {{ number_format((float) $payslip->net_pay, 2) }}</div>

    <table class="lines">
        <tr><th>Employer Contributions</th><th class="amt">Amount ({{ $symbol }})</th></tr>
        <tr><td>Employer PF</td><td class="amt">{{ number_format((float) $payslip->employer_pf, 2) }}</td></tr>
        <tr><td>Employer ESI</td><td class="amt">{{ number_format((float) $payslip->employer_esi, 2) }}</td></tr>
        <tr class="tot"><td>Total Cost to Company</td><td class="amt">{{ number_format((float) $payslip->employer_cost, 2) }}</td></tr>
    </table>

    <div class="foot">
        This is a computer-generated payslip and does not require a signature.
        Generated {{ now()->format('d M Y, H:i') }}.
    </div>
</div>
</body>
</html>
