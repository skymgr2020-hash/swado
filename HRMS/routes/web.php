<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Employee\SelfServiceController;
use App\Http\Controllers\Hr\DashboardController;
use App\Http\Controllers\Hr\EmployeeController;
use App\Http\Controllers\Hr\HrReportController;
use App\Http\Controllers\Hr\HrSettingsController;
use App\Http\Controllers\Hr\LeaveController;
use App\Http\Controllers\Hr\OrgController;
use App\Http\Controllers\Hr\PayrollRunController;
use App\Http\Controllers\Hr\PayslipController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? (auth()->user()->hasAnyRole(['hr_manager', 'admin', 'super_admin']) ? 'hr.dashboard' : 'employee.dashboard') : 'login'));

Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout')->middleware('auth');

/* ----------------------------- HR workspace ----------------------------- */
Route::middleware(['auth', 'role:hr_manager|admin|super_admin'])->prefix('hr')->name('hr.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
    Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
    Route::get('/employees/{employee}', [EmployeeController::class, 'show'])->name('employees.show');
    Route::get('/employees/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
    Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
    Route::post('/employees/{employee}/deactivate', [EmployeeController::class, 'deactivate'])->name('employees.deactivate');

    Route::get('/org', [OrgController::class, 'index'])->name('org.index');
    Route::post('/org/departments', [OrgController::class, 'storeDepartment'])->name('org.departments.store');
    Route::put('/org/departments/{department}', [OrgController::class, 'updateDepartment'])->name('org.departments.update');
    Route::post('/org/designations', [OrgController::class, 'storeDesignation'])->name('org.designations.store');
    Route::put('/org/designations/{designation}', [OrgController::class, 'updateDesignation'])->name('org.designations.update');

    Route::get('/payroll', [PayrollRunController::class, 'index'])->name('payroll.index');
    Route::post('/payroll', [PayrollRunController::class, 'store'])->name('payroll.store');
    Route::get('/payroll/{run}', [PayrollRunController::class, 'show'])->name('payroll.show');
    Route::put('/payroll/{run}/payslips/{payslip}', [PayrollRunController::class, 'updatePayslip'])->name('payroll.payslip.update');
    Route::post('/payroll/{run}/recalculate', [PayrollRunController::class, 'recalculate'])->name('payroll.recalculate');
    Route::post('/payroll/{run}/approve', [PayrollRunController::class, 'approve'])->name('payroll.approve');
    Route::post('/payroll/{run}/mark-paid', [PayrollRunController::class, 'markPaid'])->name('payroll.mark-paid');
    Route::post('/payroll/{run}/cancel', [PayrollRunController::class, 'cancel'])->name('payroll.cancel');
    Route::get('/payroll/{run}/register', [PayrollRunController::class, 'register'])->name('payroll.register');
    Route::get('/payroll/{run}/bank-advice', [PayrollRunController::class, 'bankAdvice'])->name('payroll.bank-advice');

    Route::get('/payslips', [PayslipController::class, 'index'])->name('payslips.index');
    Route::get('/payslips/{payslip}/pdf', [PayslipController::class, 'pdf'])->name('payslips.pdf');

    Route::get('/leave', [LeaveController::class, 'index'])->name('leave.index');
    Route::post('/leave/{leaveRequest}/decide', [LeaveController::class, 'decide'])->name('leave.decide');
    Route::post('/leave/types', [LeaveController::class, 'storeType'])->name('leave.types.store');
    Route::put('/leave/types/{leaveType}', [LeaveController::class, 'updateType'])->name('leave.types.update');

    Route::get('/reports', [HrReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/statutory/{kind}', [HrReportController::class, 'statutory'])->name('reports.statutory');
    Route::get('/reports/form24q', [HrReportController::class, 'form24q'])->name('reports.form24q');
    Route::get('/reports/form16/{employee}', [HrReportController::class, 'form16'])->name('reports.form16');

    Route::get('/settings', [HrSettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [HrSettingsController::class, 'update'])->name('settings.update');
});

/* --------------------------- Employee self-service --------------------------- */
Route::middleware(['auth', 'role:employee|hr_manager|admin|super_admin'])->prefix('me')->name('employee.')->group(function () {
    Route::get('/', [SelfServiceController::class, 'dashboard'])->name('dashboard');
    Route::get('/payslips', [SelfServiceController::class, 'payslips'])->name('payslips');
    Route::get('/payslips/{payslip}/pdf', [SelfServiceController::class, 'payslipPdf'])->name('payslips.pdf');
    Route::get('/form16', [SelfServiceController::class, 'form16'])->name('form16');
    Route::get('/form16/{fy}/pdf', [SelfServiceController::class, 'form16Pdf'])->name('form16.pdf');
    Route::get('/leave', [SelfServiceController::class, 'leave'])->name('leave');
    Route::post('/leave', [SelfServiceController::class, 'applyLeave'])->name('leave.apply');
    Route::post('/leave/{leaveRequest}/cancel', [SelfServiceController::class, 'cancelLeave'])->name('leave.cancel');
    Route::get('/profile', [SelfServiceController::class, 'profile'])->name('profile');
    Route::put('/profile', [SelfServiceController::class, 'updateProfile'])->name('profile.update');
});
