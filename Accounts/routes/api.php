<?php

use App\Http\Controllers\Api\IngestController;
use Illuminate\Support\Facades\Route;

// Inbound from admin/ (money events, identity) and HRMS/ (payroll journals).
// HMAC-verified per-request in the controller against the matching shared secret.
Route::post('/ingest/journal', [IngestController::class, 'journal']);        // journal.posted (admin + hrms)
Route::post('/ingest/ledger-event', [IngestController::class, 'ledgerEvent']); // tax.accrued, ping (admin)
Route::post('/ingest/user', [IngestController::class, 'user']);             // user.upserted / user.deleted (admin)
