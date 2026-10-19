@extends('layouts.app')
@section('title', 'Profile')
@section('content')
<div class="settings-hero">
    <div>
        <div class="settings-eyebrow">Self-service</div>
        <h1>Profile</h1>
        <p>You can update contact and bank details. Statutory identifiers are managed by HR.</p>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-5">
        <div class="table-card">
            <div class="card-header">On record</div>
            <div class="settings-card-body">
                <table class="grid">
                    <tr><td>Name</td><td>{{ $employee->name }}</td></tr>
                    <tr><td>Code</td><td>{{ $employee->employee_code }}</td></tr>
                    <tr><td>Department</td><td>{{ $employee->department?->name ?: '—' }}</td></tr>
                    <tr><td>Designation</td><td>{{ $employee->designation?->name ?: '—' }}</td></tr>
                    <tr><td>Date of joining</td><td>{{ optional($employee->date_of_joining)->format('d M Y') }}</td></tr>
                    <tr><td>PAN</td><td>{{ $employee->pan ?: '—' }}</td></tr>
                    <tr><td>PF UAN</td><td>{{ $employee->pf_uan ?: '—' }}</td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="settings-card">
            <div class="settings-card-header">Editable details</div>
            <div class="settings-card-body">
                <form method="POST" action="{{ route('employee.profile.update') }}" class="settings-grid">
                    @csrf @method('PUT')
                    <div class="settings-field settings-span-6"><label class="form-label small">Phone</label><input name="phone" class="form-control" value="{{ old('phone', $employee->phone) }}"></div>
                    <div class="settings-field settings-span-6"><label class="form-label small">Personal email</label><input type="email" name="personal_email" class="form-control" value="{{ old('personal_email', $employee->personal_email) }}"></div>
                    <div class="settings-field settings-span-6"><label class="form-label small">Bank account name</label><input name="bank_account_name" class="form-control" value="{{ old('bank_account_name', $employee->bank_account_name) }}"></div>
                    <div class="settings-field settings-span-6"><label class="form-label small">Bank account no.</label><input name="bank_account_no" class="form-control" value="{{ old('bank_account_no', $employee->bank_account_no) }}"></div>
                    <div class="settings-field settings-span-6"><label class="form-label small">IFSC</label><input name="bank_ifsc" class="form-control text-uppercase" value="{{ old('bank_ifsc', $employee->bank_ifsc) }}"></div>
                    <div class="settings-field settings-span-12"><button class="btn btn-success"><i class="fas fa-check me-2"></i>Save</button></div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
