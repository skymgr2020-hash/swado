<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\HrSetting;
use Illuminate\Http\Request;

class HrSettingsController extends Controller
{
    public function index()
    {
        return view('hr.settings', [
            'settings' => HrSetting::pluck('value', 'key')->toArray(),
            'can_edit' => auth()->user()->hasAnyRole(['hr_manager', 'admin', 'super_admin']),
        ]);
    }

    public function update(Request $request)
    {
        abort_unless(auth()->user()->hasAnyRole(['hr_manager', 'admin', 'super_admin']), 403);

        $data = $request->validate([
            'pay_day' => 'nullable|integer|min:1|max:28',
            'payment_account' => 'nullable|string|max:12',
            'default_tax_regime' => 'nullable|in:old,new',
            'pf_employee_rate' => 'nullable|numeric|min:0|max:100',
            'pf_employer_rate' => 'nullable|numeric|min:0|max:100',
            'pf_wage_ceiling' => 'nullable|numeric|min:0',
            'eps_rate' => 'nullable|numeric|min:0|max:100',
            'esi_employee_rate' => 'nullable|numeric|min:0|max:100',
            'esi_employer_rate' => 'nullable|numeric|min:0|max:100',
            'esi_wage_ceiling' => 'nullable|numeric|min:0',
            'pt_mode' => 'nullable|in:flat,slab,none',
            'pt_flat_amount' => 'nullable|numeric|min:0',
            'tds192_mode' => 'nullable|in:flat,projected,manual',
            'tds192_flat_rate' => 'nullable|numeric|min:0|max:100',
            'employer_legal_name' => 'nullable|string|max:255',
            'employer_tan' => 'nullable|string|max:15',
            'employer_pan' => 'nullable|string|max:15',
        ]);

        foreach ($data as $k => $v) {
            if ($v !== null) {
                HrSetting::put($k, $v);
            }
        }

        return back()->with('success', 'HR settings saved.');
    }
}
