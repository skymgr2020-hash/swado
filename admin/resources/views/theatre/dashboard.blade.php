@extends('layouts.admin')

@section('title', 'Theatre Dashboard')

@section('content')
<div class="page-header">
    <h1>{{ $theatre->name }}</h1>
    <p>{{ $theatre->code }} · {{ ucfirst($theatre->status) }} · In-seat food ordering scoped to this theatre only.</p>
</div>

<div class="row g-3 mb-4">
    @foreach([
        'Stalls' => $stats['restaurants'],
        'Screens' => $stats['screens'],
        'Active Runners' => $stats['runners'],
        'Orders' => $stats['orders'],
    ] as $label => $value)
        <div class="col-md-3">
            <div class="card"><div class="card-body"><div class="text-muted small">{{ $label }}</div><h3 class="mb-0">{{ number_format($value) }}</h3></div></div>
        </div>
    @endforeach
</div>

<div class="row g-4">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h5><i class="fas fa-utensils me-2 text-muted"></i>Stalls</h5>
                <p class="text-muted small">Create concession stalls and track their admin approval.</p>
                @if($capabilities['restaurants_view'] ?? false)
                    <a href="{{ route('theatre.restaurants') }}" class="btn btn-outline-primary btn-sm">Manage Stalls</a>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h5><i class="fas fa-film me-2 text-muted"></i>Screens &amp; Seat Maps</h5>
                <p class="text-muted small">Design seat maps and print a QR ordering sticker for each seat.</p>
                @if($capabilities['screens_view'] ?? false)
                    <a href="{{ route('theatre.screens') }}" class="btn btn-outline-primary btn-sm">Manage Screens</a>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <h5><i class="fas fa-person-walking me-2 text-muted"></i>Runners</h5>
                <p class="text-muted small">Manage the roster of runners who deliver to seats here.</p>
                @if($capabilities['runners_view'] ?? false)
                    <a href="{{ route('theatre.runners') }}" class="btn btn-outline-primary btn-sm">Manage Runners</a>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info mt-4">
    <i class="fas fa-circle-info me-2"></i>
    Set up approved stalls, seat maps and runners before sharing seat QR codes.
    Add show times to offer interval delivery. Track incoming orders in the Orders tab.
</div>
@endsection
