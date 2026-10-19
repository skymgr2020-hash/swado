@extends('layouts.app')
@section('title', 'Departments & Designations')
@section('content')
<div class="settings-hero">
    <div>
        <div class="settings-eyebrow">HR</div>
        <h1>Departments &amp; Designations</h1>
        <p>Organisational structure used across employees and payroll.</p>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="settings-card">
            <div class="settings-card-header">Departments</div>
            <div class="settings-card-body">
                <table class="grid mb-3">
                    <thead><tr><th>Name</th><th>Code</th><th class="num">Staff</th><th>Active</th></tr></thead>
                    <tbody>
                    @forelse($departments as $d)
                        <tr>
                            <td>{{ $d->name }}</td><td>{{ $d->code ?: '—' }}</td>
                            <td class="num">{{ $d->employees_count }}</td>
                            <td>{{ $d->is_active ? 'Yes' : 'No' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">None yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                <form method="POST" action="{{ route('hr.org.departments.store') }}" class="row g-2">
                    @csrf
                    <div class="col-6"><input name="name" class="form-control form-control-sm" placeholder="Name" required></div>
                    <div class="col-4"><input name="code" class="form-control form-control-sm" placeholder="Code"></div>
                    <div class="col-2"><button class="btn btn-sm btn-success w-100">Add</button></div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="settings-card">
            <div class="settings-card-header">Designations</div>
            <div class="settings-card-body">
                <table class="grid mb-3">
                    <thead><tr><th>Name</th><th>Level</th><th class="num">Staff</th><th>Active</th></tr></thead>
                    <tbody>
                    @forelse($designations as $d)
                        <tr>
                            <td>{{ $d->name }}</td><td>{{ $d->level ?: '—' }}</td>
                            <td class="num">{{ $d->employees_count }}</td>
                            <td>{{ $d->is_active ? 'Yes' : 'No' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-3">None yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                <form method="POST" action="{{ route('hr.org.designations.store') }}" class="row g-2">
                    @csrf
                    <div class="col-6"><input name="name" class="form-control form-control-sm" placeholder="Name" required></div>
                    <div class="col-4"><input name="level" class="form-control form-control-sm" placeholder="Level"></div>
                    <div class="col-2"><button class="btn btn-sm btn-success w-100">Add</button></div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
