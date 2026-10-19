<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\TaxController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'));

Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout')->middleware('auth');

Route::middleware(['auth', 'role:accountant|admin|super_admin'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/overview', [TaxController::class, 'overview'])->name('accounting.overview');
    Route::get('/gst', [TaxController::class, 'gst'])->name('accounting.gst');
    Route::get('/tds', [TaxController::class, 'tds'])->name('accounting.tds');
    Route::get('/tcs', [TaxController::class, 'tcs'])->name('accounting.tcs');
    Route::get('/settlements', [TaxController::class, 'settlements'])->name('accounting.settlements');
    Route::get('/ledger', [TaxController::class, 'ledger'])->name('accounting.ledger');
    Route::get('/documents', [TaxController::class, 'documents'])->name('accounting.documents');
    Route::get('/compliance', [TaxController::class, 'compliance'])->name('accounting.compliance');
    Route::get('/compliance/export', [TaxController::class, 'complianceExport'])->name('accounting.compliance.export');
    Route::post('/compliance', [TaxController::class, 'updateCompliance'])->name('accounting.compliance.update');
    Route::get('/cess', [TaxController::class, 'cess'])->name('accounting.cess');
    Route::post('/mark-filed', [TaxController::class, 'markFiled'])->name('accounting.mark-filed');
    Route::get('/export/{doc}', [TaxController::class, 'export'])->name('accounting.export');

    Route::prefix('gl')->name('accounting.gl.')->group(function () {
        Route::get('/chart', [LedgerController::class, 'chart'])->name('chart');
        Route::post('/chart', [LedgerController::class, 'storeAccount'])->name('chart.store');
        Route::get('/journals', [LedgerController::class, 'journals'])->name('journals');
        Route::post('/journals', [LedgerController::class, 'storeJournal'])->name('journals.store');
        Route::post('/journals/{journal}/void', [LedgerController::class, 'voidJournal'])->name('journals.void');
        Route::get('/trial-balance', [LedgerController::class, 'trialBalance'])->name('trial-balance');
        Route::get('/balance-sheet', [LedgerController::class, 'balanceSheet'])->name('balance-sheet');
        Route::get('/profit-loss', [LedgerController::class, 'profitLoss'])->name('profit-loss');
        Route::get('/cash-flow', [LedgerController::class, 'cashFlow'])->name('cash-flow');
        Route::post('/close-period', [LedgerController::class, 'closePeriod'])->name('close-period');
        Route::get('/export/{doc}', [LedgerController::class, 'export'])->name('export');
    });
});
