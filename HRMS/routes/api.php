<?php

use App\Http\Controllers\Api\IngestController;
use Illuminate\Support\Facades\Route;

// Inbound identity sync from admin/ (hr_manager & employee users).
// HMAC-verified per-request in the controller against INTEGRATION_ADMIN_SECRET.
Route::post('/ingest/user', [IngestController::class, 'user']); // user.upserted / user.deleted
