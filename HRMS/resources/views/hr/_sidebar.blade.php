@php
    $groups = [
        ['title' => 'Overview', 'items' => [
            ['hr.dashboard', 'fa-gauge-high', 'Dashboard'],
        ]],
        ['title' => 'People', 'items' => [
            ['hr.employees.index', 'fa-users', 'Employees'],
            ['hr.org.index', 'fa-sitemap', 'Departments'],
            ['hr.leave.index', 'fa-plane-departure', 'Leave'],
        ]],
        ['title' => 'Payroll', 'items' => [
            ['hr.payroll.index', 'fa-money-check-dollar', 'Payroll Runs'],
            ['hr.payslips.index', 'fa-file-invoice', 'Payslips'],
            ['hr.reports.index', 'fa-chart-column', 'Statutory Reports'],
        ]],
        ['title' => 'Configure', 'items' => [
            ['hr.settings.index', 'fa-gear', 'Settings'],
        ]],
    ];
@endphp
<aside class="sidebar">
    <div class="sidebar-logo-section">
        <div class="sidebar-logo-icon"><i class="fas fa-user-group"></i></div>
        <div class="sidebar-logo-text"><h2>HRMS</h2><small>{{ config('app.name') }}</small></div>
    </div>
    <div class="sidebar-nav-wrapper">
        @foreach($groups as $group)
            <div class="sidebar-section-title">{{ $group['title'] }}</div>
            <ul class="sidebar-nav">
                @foreach($group['items'] as [$route, $icon, $label])
                    <li>
                        <a class="sidebar-nav-link {{ request()->routeIs($route) || request()->routeIs(str_replace('.index', '.*', $route)) ? 'active' : '' }}" href="{{ route($route) }}">
                            <i class="fas {{ $icon }}"></i><span>{{ $label }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endforeach
        <div class="sidebar-section-title">Me</div>
        <ul class="sidebar-nav">
            <li>
                <a class="sidebar-nav-link" href="{{ route('employee.dashboard') }}"><i class="fas fa-id-badge"></i><span>My Portal</span></a>
            </li>
            <li>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="sidebar-nav-link"><i class="fas fa-right-from-bracket"></i><span>Sign out</span></button>
                </form>
            </li>
        </ul>
    </div>
</aside>
