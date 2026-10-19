@extends('layouts.app')
@section('title', 'Leave')
@section('content')
<div class="settings-hero">
    <div>
        <div class="settings-eyebrow">Self-service</div>
        <h1>Leave</h1>
        <p>Apply for leave and track your requests. Weekends are excluded from the day count.</p>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-5">
        <div class="settings-card">
            <div class="settings-card-header">Apply</div>
            <div class="settings-card-body">
                <form method="POST" action="{{ route('employee.leave.apply') }}" class="settings-grid">
                    @csrf
                    <div class="settings-field settings-span-12"><label class="form-label small">Leave type</label>
                        <select name="leave_type_id" class="form-select" required>
                            @foreach($types as $tp)<option value="{{ $tp->id }}">{{ $tp->name }} @unless($tp->paid)(unpaid)@endunless</option>@endforeach
                        </select>
                    </div>
                    <div class="settings-field settings-span-6"><label class="form-label small">From</label><input type="date" name="start_date" class="form-control" required></div>
                    <div class="settings-field settings-span-6"><label class="form-label small">To</label><input type="date" name="end_date" class="form-control" required></div>
                    <div class="settings-field settings-span-12"><label class="form-label small">Reason</label><textarea name="reason" class="form-control" rows="2"></textarea></div>
                    <div class="settings-field settings-span-12"><button class="btn btn-success">Submit request</button></div>
                </form>
            </div>
        </div>
        <div class="settings-card">
            <div class="settings-card-header">Balances</div>
            <div class="settings-card-body">
                <table class="grid">
                    <tbody>
                    @foreach($balances as $b)
                        <tr><td>{{ $b->type->name }}</td><td class="num">{{ number_format($b->available, 1) }} left</td></tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-7">
        <div class="table-card">
            <div class="card-header">My requests</div>
            <table class="grid">
                <thead><tr><th>Type</th><th>From</th><th>To</th><th class="num">Days</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse($requests as $r)
                    <tr>
                        <td>{{ $r->type->name }}</td>
                        <td>{{ $r->start_date->format('d M Y') }}</td>
                        <td>{{ $r->end_date->format('d M Y') }}</td>
                        <td class="num">{{ (float) $r->days }}</td>
                        <td><span class="badge bg-{{ ['pending'=>'warning text-dark','approved'=>'success','rejected'=>'danger','cancelled'=>'secondary'][$r->status] ?? 'secondary' }}">{{ $r->status }}</span></td>
                        <td>
                            @if(in_array($r->status, ['pending','approved']))
                                <form method="POST" action="{{ route('employee.leave.cancel', $r) }}" onsubmit="return confirm('Cancel this request?')">
                                    @csrf<button class="btn btn-sm btn-outline-danger">Cancel</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No requests yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
