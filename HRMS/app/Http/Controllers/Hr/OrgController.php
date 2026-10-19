<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\HrDepartment;
use App\Models\HrDesignation;
use Illuminate\Http\Request;

class OrgController extends Controller
{
    public function index()
    {
        return view('hr.org.index', [
            'departments' => HrDepartment::withCount('employees')->orderBy('name')->get(),
            'designations' => HrDesignation::withCount('employees')->orderBy('name')->get(),
        ]);
    }

    public function storeDepartment(Request $request)
    {
        HrDepartment::create($request->validate(['name' => 'required|string|max:120', 'code' => 'nullable|string|max:20']) + ['is_active' => true]);

        return back()->with('success', 'Department added.');
    }

    public function updateDepartment(Request $request, HrDepartment $department)
    {
        $department->update($request->validate(['name' => 'required|string|max:120', 'code' => 'nullable|string|max:20', 'is_active' => 'nullable|boolean']) + ['is_active' => $request->boolean('is_active')]);

        return back()->with('success', 'Department updated.');
    }

    public function storeDesignation(Request $request)
    {
        HrDesignation::create($request->validate(['name' => 'required|string|max:120', 'level' => 'nullable|string|max:40']) + ['is_active' => true]);

        return back()->with('success', 'Designation added.');
    }

    public function updateDesignation(Request $request, HrDesignation $designation)
    {
        $designation->update($request->validate(['name' => 'required|string|max:120', 'level' => 'nullable|string|max:40', 'is_active' => 'nullable|boolean']) + ['is_active' => $request->boolean('is_active')]);

        return back()->with('success', 'Designation updated.');
    }
}
