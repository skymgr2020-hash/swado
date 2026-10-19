@php
    $nav = [
        ['employee.dashboard', 'fa-gauge-high', 'Overview'],
        ['employee.payslips', 'fa-file-invoice', 'My Payslips'],
        ['employee.form16', 'fa-file-contract', 'Form 16'],
        ['employee.leave', 'fa-plane-departure', 'Leave'],
        ['employee.profile', 'fa-id-card', 'Profile'],
    ];
    $isHr = auth()->user()->hasAnyRole(['hr_manager', 'admin', 'super_admin']);
@endphp
<aside class="sidebar">
    <div class="sidebar-logo-section">
        <div class="sidebar-logo-icon"><i class="fas fa-id-badge"></i></div>
        <div class="sidebar-logo-text"><h2>My Portal</h2><small>{{ auth()->user()->name }}</small></div>
    </div>
    <div class="sidebar-nav-wrapper">
        <div class="sidebar-section-title">Self-service</div>
        <ul class="sidebar-nav">
            @foreach($nav as [$route, $icon, $label])
                <li>
                    <a class="sidebar-nav-link {{ request()->routeIs($route) ? 'active' : '' }}" href="{{ route($route) }}">
                        <i class="fas {{ $icon }}"></i><span>{{ $label }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
        @if($isHr)
            <div class="sidebar-section-title">Manage</div>
            <ul class="sidebar-nav">
                <li>
                    <a class="sidebar-nav-link" href="{{ route('hr.dashboard') }}"><i class="fas fa-user-group"></i><span>HR Workspace</span></a>
                </li>
            </ul>
        @endif
        <div class="sidebar-section-title">Account</div>
        <ul class="sidebar-nav">
            <li>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="sidebar-nav-link"><i class="fas fa-right-from-bracket"></i><span>Sign out</span></button>
                </form>
            </li>
        </ul>
    </div>
</aside>
