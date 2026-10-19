@extends('layouts.app')
@section('title', $employee->exists ? 'Edit employee' : 'Add employee')
@section('content')
@php
    $isEdit = $employee->exists;
    $structure = $isEdit ? $employee->currentStructure : null;
    $earnings = old('earn_code') ? null : ($structure->earnings ?? [['code' => 'basic', 'label' => 'Basic', 'calc' => 'flat', 'value' => 0]]);
@endphp

<div class="settings-hero">
    <div>
        <div class="settings-eyebrow">HR</div>
        <h1>{{ $isEdit ? 'Edit employee' : 'Add employee' }}</h1>
        <p>{{ $isEdit ? 'Update details and salary structure.' : 'A login for the self-service portal is created automatically.' }}</p>
    </div>
    <a href="{{ route('hr.employees.index') }}" class="btn btn-outline-secondary">Back</a>
</div>

<form method="POST" action="{{ $isEdit ? route('hr.employees.update', $employee) : route('hr.employees.store') }}">
    @csrf
    @if($isEdit) @method('PUT') @endif

    <div class="settings-card">
        <div class="settings-card-header">Identity</div>
        <div class="settings-card-body settings-grid">
            <div class="settings-field settings-span-3"><label class="form-label small">Employee code *</label><input name="employee_code" class="form-control" value="{{ old('employee_code', $employee->employee_code) }}" required></div>
            <div class="settings-field settings-span-3"><label class="form-label small">First name *</label><input name="first_name" class="form-control" value="{{ old('first_name', $employee->first_name) }}" required></div>
            <div class="settings-field settings-span-3"><label class="form-label small">Last name</label><input name="last_name" class="form-control" value="{{ old('last_name', $employee->last_name) }}"></div>
            <div class="settings-field settings-span-3"><label class="form-label small">Gender</label>
                <select name="gender" class="form-select">
                    <option value="">—</option>
                    @foreach(['male','female','other'] as $g)<option value="{{ $g }}" @selected(old('gender',$employee->gender)===$g)>{{ ucfirst($g) }}</option>@endforeach
                </select>
            </div>
            <div class="settings-field settings-span-4"><label class="form-label small">Work email (login) *</label><input type="email" name="work_email" class="form-control" value="{{ old('work_email', $employee->user?->email) }}" required></div>
            <div class="settings-field settings-span-4"><label class="form-label small">Personal email</label><input type="email" name="personal_email" class="form-control" value="{{ old('personal_email', $employee->personal_email) }}"></div>
            <div class="settings-field settings-span-4"><label class="form-label small">Phone</label><input name="phone" class="form-control" value="{{ old('phone', $employee->phone) }}"></div>
            <div class="settings-field settings-span-3"><label class="form-label small">Date of birth</label><input type="date" name="dob" class="form-control" value="{{ old('dob', optional($employee->dob)->toDateString()) }}"></div>
            <div class="settings-field settings-span-3"><label class="form-label small">Date of joining *</label><input type="date" name="date_of_joining" class="form-control" value="{{ old('date_of_joining', optional($employee->date_of_joining)->toDateString() ?: now()->toDateString()) }}" required></div>
            <div class="settings-field settings-span-3"><label class="form-label small">Employment type *</label>
                <select name="employment_type" class="form-select">
                    @foreach(['full_time','part_time','contract','intern'] as $t)<option value="{{ $t }}" @selected(old('employment_type',$employee->employment_type ?: 'full_time')===$t)>{{ ucfirst(str_replace('_',' ',$t)) }}</option>@endforeach
                </select>
            </div>
            <div class="settings-field settings-span-3"><label class="form-label small">Status</label>
                <select name="status" class="form-select">
                    @foreach(['active','on_notice','resigned','terminated','on_leave'] as $s)<option value="{{ $s }}" @selected(old('status',$employee->status ?: 'active')===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>@endforeach
                </select>
            </div>
            <div class="settings-field settings-span-6"><label class="form-label small">Department</label>
                <select name="department_id" class="form-select">
                    <option value="">—</option>
                    @foreach($departments as $d)<option value="{{ $d->id }}" @selected(old('department_id',$employee->department_id)==$d->id)>{{ $d->name }}</option>@endforeach
                </select>
            </div>
            <div class="settings-field settings-span-6"><label class="form-label small">Designation</label>
                <select name="designation_id" class="form-select">
                    <option value="">—</option>
                    @foreach($designations as $d)<option value="{{ $d->id }}" @selected(old('designation_id',$employee->designation_id)==$d->id)>{{ $d->name }}</option>@endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="settings-card">
        <div class="settings-card-header">Statutory &amp; bank</div>
        <div class="settings-card-body settings-grid">
            <div class="settings-field settings-span-3"><label class="form-label small">PAN</label><input name="pan" class="form-control text-uppercase" value="{{ old('pan', $employee->pan) }}"></div>
            <div class="settings-field settings-span-3"><label class="form-label small">Aadhaar (last 4)</label><input name="aadhaar_last4" maxlength="4" class="form-control" value="{{ old('aadhaar_last4', $employee->aadhaar_last4) }}"></div>
            <div class="settings-field settings-span-3"><label class="form-label small">PF UAN</label><input name="pf_uan" class="form-control" value="{{ old('pf_uan', $employee->pf_uan) }}"></div>
            <div class="settings-field settings-span-3"><label class="form-label small">ESI IP number</label><input name="esi_ic_number" class="form-control" value="{{ old('esi_ic_number', $employee->esi_ic_number) }}"></div>
            <div class="settings-field settings-span-3"><label class="form-label small">Tax regime *</label>
                <select name="tax_regime" class="form-select">
                    @foreach(['new','old'] as $r)<option value="{{ $r }}" @selected(old('tax_regime',$employee->tax_regime ?: 'new')===$r)>{{ ucfirst($r) }}</option>@endforeach
                </select>
            </div>
            <div class="settings-field settings-span-3"><label class="form-label small">Bank account name</label><input name="bank_account_name" class="form-control" value="{{ old('bank_account_name', $employee->bank_account_name) }}"></div>
            <div class="settings-field settings-span-3"><label class="form-label small">Bank account no.</label><input name="bank_account_no" class="form-control" value="{{ old('bank_account_no', $employee->bank_account_no) }}"></div>
            <div class="settings-field settings-span-3"><label class="form-label small">IFSC</label><input name="bank_ifsc" class="form-control text-uppercase" value="{{ old('bank_ifsc', $employee->bank_ifsc) }}"></div>
        </div>
    </div>

    <div class="settings-card">
        <div class="settings-card-header">Salary structure</div>
        <div class="settings-card-body settings-grid">
            <div class="settings-field settings-span-3"><label class="form-label small">Effective from</label><input type="date" name="structure_effective_from" class="form-control" value="{{ old('structure_effective_from', optional($structure?->effective_from)->toDateString() ?: now()->toDateString()) }}"></div>
            <div class="settings-field settings-span-3"><label class="form-label small">CTC (annual)</label><input type="number" step="0.01" name="ctc_annual" class="form-control" value="{{ old('ctc_annual', $structure->ctc_annual ?? $employee->ctc_annual) }}"></div>
            <div class="settings-field settings-span-12">
                <table class="grid" id="earn-table">
                    <thead><tr><th>Code</th><th>Label</th><th>Calc</th><th>Value</th><th></th></tr></thead>
                    <tbody>
                    @php $rows = old('earn_code') ? array_keys(old('earn_code')) : array_keys($earnings ?? []); @endphp
                    @foreach($rows as $i)
                        <tr>
                            <td><input name="earn_code[]" class="form-control form-control-sm" value="{{ old("earn_code.$i", $earnings[$i]['code'] ?? '') }}"></td>
                            <td><input name="earn_label[]" class="form-control form-control-sm" value="{{ old("earn_label.$i", $earnings[$i]['label'] ?? '') }}"></td>
                            <td>
                                <select name="earn_calc[]" class="form-select form-select-sm">
                                    <option value="flat" @selected(old("earn_calc.$i", $earnings[$i]['calc'] ?? 'flat')==='flat')>Flat / month</option>
                                    <option value="pct_basic" @selected(old("earn_calc.$i", $earnings[$i]['calc'] ?? '')==='pct_basic')>% of Basic</option>
                                </select>
                            </td>
                            <td><input name="earn_value[]" type="number" step="0.01" class="form-control form-control-sm" value="{{ old("earn_value.$i", $earnings[$i]['value'] ?? 0) }}"></td>
                            <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">&times;</button></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                <button type="button" class="btn btn-sm btn-outline-secondary mt-2" onclick="addEarnRow()"><i class="fas fa-plus me-1"></i>Add component</button>
                <p class="small text-muted mt-2 mb-0">One row must have code <code>basic</code>. PF/ESI/PT/TDS are computed automatically per the Settings rates.</p>
            </div>
        </div>
    </div>

    <button class="btn btn-success"><i class="fas fa-check me-2"></i>{{ $isEdit ? 'Save changes' : 'Create employee' }}</button>
</form>

<script>
function addEarnRow() {
    const tb = document.querySelector('#earn-table tbody');
    const tr = document.createElement('tr');
    tr.innerHTML = `<td><input name="earn_code[]" class="form-control form-control-sm"></td>
        <td><input name="earn_label[]" class="form-control form-control-sm"></td>
        <td><select name="earn_calc[]" class="form-select form-select-sm"><option value="flat">Flat / month</option><option value="pct_basic">% of Basic</option></select></td>
        <td><input name="earn_value[]" type="number" step="0.01" class="form-control form-control-sm" value="0"></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">&times;</button></td>`;
    tb.appendChild(tr);
}
</script>
@endsection
