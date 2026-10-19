@extends('layouts.app')
@section('title', 'Settings')
@section('content')
@php $g = fn($k, $d = '') => $settings[$k] ?? $d; @endphp
<div class="settings-hero">
    <div>
        <div class="settings-eyebrow">HR</div>
        <h1>Settings</h1>
        <p>Pay cycle, statutory rates and employer identifiers. Used by every payroll computation.</p>
    </div>
</div>

<form method="POST" action="{{ route('hr.settings.update') }}">
    @csrf
    <fieldset @disabled(!$can_edit)>
    <div class="settings-card">
        <div class="settings-card-header">Pay cycle</div>
        <div class="settings-card-body settings-grid">
            <div class="settings-field settings-span-3"><label class="form-label small">Pay day (1–28)</label><input name="pay_day" type="number" min="1" max="28" class="form-control" value="{{ $g('pay_day', 1) }}"></div>
            <div class="settings-field settings-span-3"><label class="form-label small">Payment account code (Accounts CoA)</label><input name="payment_account" class="form-control" value="{{ $g('payment_account', '1000') }}"></div>
            <div class="settings-field settings-span-3"><label class="form-label small">Default tax regime</label>
                <select name="default_tax_regime" class="form-select">
                    <option value="new" @selected($g('default_tax_regime','new')==='new')>New</option>
                    <option value="old" @selected($g('default_tax_regime')==='old')>Old</option>
                </select>
            </div>
        </div>
    </div>

    <div class="settings-card">
        <div class="settings-card-header">Provident Fund</div>
        <div class="settings-card-body settings-grid">
            <div class="settings-field settings-span-3"><label class="form-label small">Employee rate %</label><input name="pf_employee_rate" type="number" step="0.01" class="form-control" value="{{ $g('pf_employee_rate', 12) }}"></div>
            <div class="settings-field settings-span-3"><label class="form-label small">Employer rate %</label><input name="pf_employer_rate" type="number" step="0.01" class="form-control" value="{{ $g('pf_employer_rate', 12) }}"></div>
            <div class="settings-field settings-span-3"><label class="form-label small">Wage ceiling</label><input name="pf_wage_ceiling" type="number" step="1" class="form-control" value="{{ $g('pf_wage_ceiling', 15000) }}"></div>
            <div class="settings-field settings-span-3"><label class="form-label small">EPS rate %</label><input name="eps_rate" type="number" step="0.01" class="form-control" value="{{ $g('eps_rate', 8.33) }}"></div>
        </div>
    </div>

    <div class="settings-card">
        <div class="settings-card-header">ESI &amp; Professional Tax</div>
        <div class="settings-card-body settings-grid">
            <div class="settings-field settings-span-3"><label class="form-label small">ESI employee rate %</label><input name="esi_employee_rate" type="number" step="0.01" class="form-control" value="{{ $g('esi_employee_rate', 0.75) }}"></div>
            <div class="settings-field settings-span-3"><label class="form-label small">ESI employer rate %</label><input name="esi_employer_rate" type="number" step="0.01" class="form-control" value="{{ $g('esi_employer_rate', 3.25) }}"></div>
            <div class="settings-field settings-span-3"><label class="form-label small">ESI wage ceiling</label><input name="esi_wage_ceiling" type="number" step="1" class="form-control" value="{{ $g('esi_wage_ceiling', 21000) }}"></div>
            <div class="settings-field settings-span-3"><label class="form-label small">PT mode</label>
                <select name="pt_mode" class="form-select">
                    @foreach(['flat','slab','none'] as $m)<option value="{{ $m }}" @selected($g('pt_mode','flat')===$m)>{{ ucfirst($m) }}</option>@endforeach
                </select>
            </div>
            <div class="settings-field settings-span-3"><label class="form-label small">PT flat amount</label><input name="pt_flat_amount" type="number" step="0.01" class="form-control" value="{{ $g('pt_flat_amount', 200) }}"></div>
        </div>
    </div>

    <div class="settings-card">
        <div class="settings-card-header">TDS u/s 192 &amp; employer</div>
        <div class="settings-card-body settings-grid">
            <div class="settings-field settings-span-3"><label class="form-label small">TDS mode</label>
                <select name="tds192_mode" class="form-select">
                    @foreach(['manual','flat','projected'] as $m)<option value="{{ $m }}" @selected($g('tds192_mode','manual')===$m)>{{ ucfirst($m) }}</option>@endforeach
                </select>
            </div>
            <div class="settings-field settings-span-3"><label class="form-label small">TDS flat rate %</label><input name="tds192_flat_rate" type="number" step="0.01" class="form-control" value="{{ $g('tds192_flat_rate', 0) }}"></div>
            <div class="settings-field settings-span-6"><label class="form-label small">Employer legal name</label><input name="employer_legal_name" class="form-control" value="{{ $g('employer_legal_name') }}"></div>
            <div class="settings-field settings-span-3"><label class="form-label small">Employer TAN</label><input name="employer_tan" class="form-control text-uppercase" value="{{ $g('employer_tan') }}"></div>
            <div class="settings-field settings-span-3"><label class="form-label small">Employer PAN</label><input name="employer_pan" class="form-control text-uppercase" value="{{ $g('employer_pan') }}"></div>
        </div>
    </div>

    @if($can_edit)<button class="btn btn-success"><i class="fas fa-check me-2"></i>Save settings</button>@endif
    </fieldset>
</form>
@endsection
