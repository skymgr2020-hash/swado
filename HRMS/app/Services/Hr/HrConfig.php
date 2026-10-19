<?php

namespace App\Services\Hr;

use App\Models\HrSetting;

/**
 * Typed accessors over the hr_settings table — pay cycle + statutory rates.
 * India defaults; every value admin-overridable; every computed payslip line
 * stays editable before a run is approved.
 */
class HrConfig
{
    public function payFrequency(): string
    {
        return 'monthly';
    }

    public function payDay(): int
    {
        return max(1, min(28, (int) HrSetting::get('pay_day', 1)));
    }

    public function paymentAccountCode(): string
    {
        $v = trim((string) HrSetting::get('payment_account', '1000'));

        return $v !== '' ? $v : '1000';
    }

    public function pfEmployeeRate(): float { return (float) HrSetting::get('pf_employee_rate', 12); }
    public function pfEmployerRate(): float { return (float) HrSetting::get('pf_employer_rate', 12); }
    public function pfWageCeiling(): float { return (float) HrSetting::get('pf_wage_ceiling', 15000); }
    public function epsRate(): float { return (float) HrSetting::get('eps_rate', 8.33); }
    public function esiEmployeeRate(): float { return (float) HrSetting::get('esi_employee_rate', 0.75); }
    public function esiEmployerRate(): float { return (float) HrSetting::get('esi_employer_rate', 3.25); }
    public function esiWageCeiling(): float { return (float) HrSetting::get('esi_wage_ceiling', 21000); }

    public function ptMode(): string
    {
        $m = (string) HrSetting::get('pt_mode', 'flat');

        return in_array($m, ['flat', 'slab', 'none'], true) ? $m : 'flat';
    }

    public function ptFlatAmount(): float { return (float) HrSetting::get('pt_flat_amount', 200); }

    public function ptForGross(float $gross): float
    {
        return match ($this->ptMode()) {
            'none' => 0.0,
            'flat' => $this->ptFlatAmount(),
            default => $gross <= 7500 ? 0.0 : ($gross <= 10000 ? 175.0 : 200.0),
        };
    }

    public function tds192Mode(): string
    {
        $m = (string) HrSetting::get('tds192_mode', 'manual');

        return in_array($m, ['flat', 'projected', 'manual'], true) ? $m : 'manual';
    }

    public function tds192FlatRate(): float { return (float) HrSetting::get('tds192_flat_rate', 0); }

    public function defaultTaxRegime(): string
    {
        return (string) HrSetting::get('default_tax_regime', 'new') === 'old' ? 'old' : 'new';
    }

    public function projectedMonthlyTds(float $monthlyTaxable): float
    {
        $annual = max(0, $monthlyTaxable * 12 - 75000);
        $regime = $this->defaultTaxRegime();

        $slabs = $regime === 'old'
            ? [[250000, 0.0], [500000, 0.05], [1000000, 0.20], [PHP_INT_MAX, 0.30]]
            : [[400000, 0.0], [800000, 0.05], [1200000, 0.10], [1600000, 0.15], [2000000, 0.20], [2400000, 0.25], [PHP_INT_MAX, 0.30]];

        $tax = 0.0;
        $prev = 0.0;
        foreach ($slabs as [$upper, $rate]) {
            if ($annual <= $prev) {
                break;
            }
            $tax += (min($annual, $upper) - $prev) * $rate;
            $prev = $upper;
        }
        if (($regime === 'new' && $annual <= 700000) || ($regime === 'old' && $annual <= 500000)) {
            $tax = 0.0;
        }

        return round($tax * 1.04 / 12, 2);
    }

    public function fyStartMonth(): int
    {
        return 4;
    }

    public function fy(\Illuminate\Support\Carbon|string|null $date = null): string
    {
        $date = $date instanceof \Illuminate\Support\Carbon ? $date : \Illuminate\Support\Carbon::parse($date ?: now());
        $start = $this->fyStartMonth();
        $startYear = $date->month >= $start ? $date->year : $date->year - 1;

        return $startYear . '-' . str_pad((string) (($startYear + 1) % 100), 2, '0', STR_PAD_LEFT);
    }

    public function currency(): string
    {
        return (string) HrSetting::get('currency_symbol', '₹');
    }
}
