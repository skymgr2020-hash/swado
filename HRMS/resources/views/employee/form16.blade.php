@extends('layouts.app')
@section('title', 'Form 16')
@section('content')
<div class="settings-hero">
    <div>
        <div class="settings-eyebrow">Self-service</div>
        <h1>Form 16</h1>
        <p>Part B (salary &amp; tax computation) generated from your approved payslips.</p>
    </div>
</div>

<div class="table-card">
    <table class="grid">
        <thead><tr><th>Financial Year</th><th></th></tr></thead>
        <tbody>
        @forelse($years as $fy)
            <tr>
                <td>FY {{ $fy }}</td>
                <td><a href="{{ route('employee.form16.pdf', $fy) }}" class="btn btn-sm btn-outline-success">Download PDF</a></td>
            </tr>
        @empty
            <tr><td colspan="2" class="text-center text-muted py-4">No approved payslips yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
