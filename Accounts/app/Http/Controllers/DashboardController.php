<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\ComplianceItem;
use App\Models\TaxLedgerEntry;
use App\Services\Accounting\FinancialStatementService;
use App\Services\Tax\TaxConfig;
use App\Services\Tax\TaxReportService;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function __construct(
        private readonly TaxReportService $reports,
        private readonly FinancialStatementService $statements,
        private readonly TaxConfig $config,
    ) {
    }

    public function index()
    {
        $to = now()->endOfDay();
        $from = now()->startOfMonth();
        $fyStart = Carbon::parse(substr($this->config->fy($to), 0, 4) . '-' . str_pad((string) $this->config->fyStartMonth(), 2, '0', STR_PAD_LEFT) . '-01');

        $overview = $this->reports->overview($from, $to);
        $pl = $this->statements->profitAndLoss($fyStart, $to);
        $bs = $this->statements->balanceSheet($to);

        $compliance = ComplianceItem::all()
            ->filter(fn ($i) => $i->appliesToBusiness($this->config))
            ->groupBy('status')->map->count();

        $suspense = collect($bs['liabilities'])->firstWhere('code', '1900*');

        return view('accounting.dashboard', [
            'symbol' => AppSetting::sanitizedCurrencySymbol(),
            'decimals' => AppSetting::currencyDecimals(),
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'fy' => $this->config->fy($to),
            'net_profit_fytd' => $pl['net_profit'],
            'bank_balance' => collect($bs['assets'])->whereIn('subtype', ['bank', 'cash'])->sum('amount'),
            'gst_payable' => $overview['gst_output_total'] ?? 0,
            'tds_payable' => ($overview['tds_194o'] ?? 0) + ($overview['tds_194c'] ?? 0),
            'tcs_payable' => $overview['tcs'] ?? 0,
            'unfiled_cess' => (float) TaxLedgerEntry::where('kind', TaxLedgerEntry::KIND_GIG_CESS)->where('status', '!=', 'filed')->sum('amount'),
            'compliance' => $compliance,
            'suspense' => $suspense['amount'] ?? 0,
            'balanced' => $bs['balanced'],
            'accounting_on' => true,
        ]);
    }
}
