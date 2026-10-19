@php
    $taxCfg = app(\App\Services\Tax\TaxConfig::class);
    $showTax = $taxCfg->gstRegistered() || $taxCfg->tdsRegistered();

    $groups = [
        ['title' => 'Overview', 'items' => [
            ['dashboard', 'fa-gauge-high', 'Dashboard'],
        ]],
    ];
    $taxItems = [];
    if ($showTax) {
        $taxItems[] = ['accounting.gst', 'fa-file-invoice', 'GST'];
        $taxItems[] = ['accounting.tds', 'fa-hand-holding-dollar', 'TDS'];
        $taxItems[] = ['accounting.tcs', 'fa-percent', 'TCS'];
        $taxItems[] = ['accounting.settlements', 'fa-scale-balanced', 'Settlements'];
        $taxItems[] = ['accounting.ledger', 'fa-book', 'Tax Ledger'];
    }
    if ($taxCfg->gigCessEnabled()) {
        $taxItems[] = ['accounting.cess', 'fa-hand-holding-heart', 'Cess'];
    }
    if ($taxItems) {
        $groups[] = ['title' => 'Tax & Statutory', 'items' => $taxItems];
    }
    $groups[] = ['title' => 'Financial Statements', 'items' => [
        ['accounting.gl.balance-sheet', 'fa-scale-unbalanced', 'Balance Sheet'],
        ['accounting.gl.profit-loss', 'fa-chart-line', 'Profit & Loss'],
        ['accounting.gl.cash-flow', 'fa-money-bill-transfer', 'Cash Flow'],
        ['accounting.gl.trial-balance', 'fa-list-ol', 'Trial Balance'],
    ]];
    $groups[] = ['title' => 'Ledger', 'items' => [
        ['accounting.gl.journals', 'fa-file-lines', 'Journals'],
        ['accounting.gl.chart', 'fa-sitemap', 'Chart of Accounts'],
        ['accounting.compliance', 'fa-clipboard-list', 'Compliance'],
        ['accounting.documents', 'fa-folder-open', 'Documents'],
    ]];
@endphp
<aside class="sidebar">
    <div class="sidebar-logo-section">
        <div class="sidebar-logo-icon"><i class="fas fa-calculator"></i></div>
        <div class="sidebar-logo-text"><h2>Accounts</h2><small>{{ config('app.name') }}</small></div>
    </div>
    <div class="sidebar-nav-wrapper">
        @foreach($groups as $group)
            <div class="sidebar-section-title">{{ $group['title'] }}</div>
            <ul class="sidebar-nav">
                @foreach($group['items'] as [$route, $icon, $label])
                    <li>
                        <a class="sidebar-nav-link {{ request()->routeIs($route) || request()->routeIs($route.'.*') ? 'active' : '' }}" href="{{ route($route) }}">
                            <i class="fas {{ $icon }}"></i><span>{{ $label }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endforeach
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
