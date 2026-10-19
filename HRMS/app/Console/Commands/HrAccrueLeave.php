<?php

namespace App\Console\Commands;

use App\Services\Hr\LeaveService;
use Illuminate\Console\Command;

class HrAccrueLeave extends Command
{
    protected $signature = 'hr:accrue-leave';

    protected $description = 'Credit the monthly-accrual leave quota to every active employee for the current year';

    public function handle(LeaveService $leave): int
    {
        $n = $leave->accrueMonthly();
        $this->info("Accrued monthly leave for {$n} employee/leave-type rows.");

        return self::SUCCESS;
    }
}
