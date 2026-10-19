<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Global API Rate Limit
    |--------------------------------------------------------------------------
    |
    | Fallback rate limit applied to every route in the "api" middleware group
    | (see bootstrap/app.php -> throttleApi() and the "api" limiter registered
    | in App\Providers\AppServiceProvider). The value is requests per minute,
    | counted per authenticated user, or per client IP when unauthenticated.
    |
    | Individual routes may still declare their own stricter throttle (e.g.
    | throttle:5,1 on OTP endpoints); those continue to apply on top of this
    | ceiling. Set API_RATE_LIMIT=0 to disable the global limiter entirely.
    |
    */

    'rate_limit' => (int) env('API_RATE_LIMIT', 240),

];
