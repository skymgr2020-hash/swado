<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #1f2733; }
        h1 { font-size: 14px; margin: 0 0 2px; }
        .muted { color: #777; font-size: 9px; }
        .box { border: 1px solid #333; padding: 10px 14px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 5px 8px; text-align: left; }
        th { background: #eef1f4; font-size: 9px; text-transform: uppercase; }
        td.amt, th.amt { text-align: right; }
        .tot td { font-weight: bold; background: #f7f9fb; }
        .sec { font-weight: bold; margin: 12px 0 4px; font-size: 11px; }
    </style>
</head>
<body>
    <h1>FORM 16 — PART B</h1>
    <div class="muted">
        [See rule 31(1)(a)] &nbsp; Certificate under section 203 of the Income-tax Act, 1961 for tax deducted
        at source on salary. Financial Year {{ $fy }} &nbsp;·&nbsp; Assessment Year {{ $assessment_year }}
    </div>

    <div class="box" style="margin-top:10px">
        <table style="border:none">
            <tr style="border:none">
                <td style="border:none; width:50%">
                    <strong>Employer</strong><br>
                    {{ $employer['name'] }}<br>
                    PAN: {{ $employer['pan'] ?: '—' }} &nbsp; TAN: {{ $employer['tan'] ?: '—' }}
                </td>
                <td style="border:none">
                    <strong>Employee</strong><br>
                    {{ $employee->name }} ({{ $employee->employee_code }})<br>
                    Designation: {{ $employee->designation?->name ?: '—' }}<br>
                    PAN: {{ $employee->pan ?: '—' }} &nbsp; Regime: {{ $regime }}
                </td>
            </tr>
        </table>
    </div>

    <div class="sec">1. Gross Salary (month-wise)</div>
    <table>
        <tr><th>Month</th><th class="amt">Gross</th><th class="amt">PF</th><th class="amt">PT</th><th class="amt">TDS u/s 192</th></tr>
        @foreach($months as $s)
            <tr>
                <td>{{ \Illuminate\Support\Carbon::parse($s->run->period.'-01')->format('M Y') }}</td>
                <td class="amt">{{ number_format((float) $s->gross, 2) }}</td>
                <td class="amt">{{ number_format((float) (collect($s->deductions)->firstWhere('code','pf')['amount'] ?? 0), 2) }}</td>
                <td class="amt">{{ number_format((float) (collect($s->deductions)->firstWhere('code','pt')['amount'] ?? 0), 2) }}</td>
                <td class="amt">{{ number_format((float) $s->tds_192, 2) }}</td>
            </tr>
        @endforeach
        <tr class="tot">
            <td>Total</td>
            <td class="amt">{{ number_format($totals['gross'], 2) }}</td>
            <td class="amt">{{ number_format($totals['pf'], 2) }}</td>
            <td class="amt">{{ number_format($totals['pt'], 2) }}</td>
            <td class="amt">{{ number_format($totals['tds'], 2) }}</td>
        </tr>
    </table>

    <div class="sec">2. Computation of Taxable Income</div>
    <table>
        <tr><td>(a) Gross Salary</td><td class="amt">{{ number_format($totals['gross'], 2) }}</td></tr>
        <tr><td>(b) Less: Standard Deduction u/s 16(ia)</td><td class="amt">{{ number_format($totals['std_deduction'], 2) }}</td></tr>
        <tr><td>(c) Less: Tax on Employment (Professional Tax) u/s 16(iii)</td><td class="amt">{{ number_format($totals['pt'], 2) }}</td></tr>
        <tr><td>(d) Less: Deduction u/s 80C (Employee PF)</td><td class="amt">{{ number_format($totals['pf'], 2) }}</td></tr>
        <tr class="tot"><td>(e) Total Taxable Income</td><td class="amt">{{ number_format($totals['taxable'], 2) }}</td></tr>
        <tr class="tot"><td>(f) Total Tax Deducted at Source u/s 192</td><td class="amt">{{ number_format($totals['tds'], 2) }}</td></tr>
    </table>

    <p class="muted" style="margin-top:16px">
        I confirm that the above information is true and correct as per the payroll records of {{ $employer['name'] }}
        for the financial year {{ $fy }}. This is a system-generated Part B; Part A (with TRACES verification) is to be
        downloaded from the TRACES portal after the Form 24Q filing for Q4.
    </p>
    <p class="muted">Generated {{ now()->format('d M Y, H:i') }}.</p>
</body>
</html>
