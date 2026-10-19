@extends('layouts.app')
@section('title', 'Employees')
@section('content')
<div class="settings-hero">
    <div>
        <div class="settings-eyebrow">HR</div>
        <h1>Employees</h1>
        <p>Every employee here also gets a login for the self-service portal.</p>
    </div>
    <a href="{{ route('hr.employees.create') }}" class="btn btn-success"><i class="fas fa-plus me-2"></i>Add employee</a>
</div>

<div class="table-card mb-3">
    <div class="settings-card-body">
        <form method="GET" class="row g-2">
            <div class="col-md-5"><input name="q" value="{{ request('q') }}" class="form-control" placeholder="Name or code"></div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">Any status</option>
                    @foreach(['active','on_notice','resigned','terminated','on_leave'] as $s)
                        <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Filter</button></div>
        </form>
    </div>
</div>

<div class="table-card">
    <table class="grid">
        <thead><tr><th>Code</th><th>Name</th><th>Department</th><th>Designation</th><th>Status</th><th>Login</th><th></th></tr></thead>
        <tbody>
        @forelse($employees as $e)
            <tr>
                <td>{{ $e->employee_code }}</td>
                <td>{{ $e->name }}</td>
                <td>{{ $e->department?->name ?: '—' }}</td>
                <td>{{ $e->designation?->name ?: '—' }}</td>
                <td><span class="badge bg-{{ $e->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst(str_replace('_',' ',$e->status)) }}</span></td>
                <td class="small text-muted">{{ $e->user?->email ?: '—' }}</td>
                <td><a href="{{ route('hr.employees.show', $e) }}" class="btn btn-sm btn-outline-success">Open</a></td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-muted py-4">No employees found.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-3">{{ $employees->links() }}</div>
@endsection
