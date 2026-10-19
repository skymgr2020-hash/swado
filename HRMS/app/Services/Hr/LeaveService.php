<?php

namespace App\Services\Hr;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LeaveService
{
    public function apply(Employee $emp, int $leaveTypeId, string $start, string $end, ?string $reason = null): LeaveRequest
    {
        $days = $this->workingDaysBetween($start, $end);
        abort_if($days <= 0, 422, 'Invalid leave range.');

        return DB::transaction(function () use ($emp, $leaveTypeId, $start, $end, $reason, $days) {
            $req = LeaveRequest::create([
                'employee_id' => $emp->id, 'leave_type_id' => $leaveTypeId,
                'start_date' => $start, 'end_date' => $end, 'days' => $days,
                'reason' => $reason, 'status' => 'pending',
            ]);
            $this->balance($emp, $leaveTypeId, (int) Carbon::parse($start)->year)->increment('pending', $days);

            return $req;
        });
    }

    public function approve(LeaveRequest $req, ?string $note = null): void
    {
        abort_unless($req->status === 'pending', 422, 'Not pending.');
        DB::transaction(function () use ($req, $note) {
            $bal = $this->balance($req->employee, $req->leave_type_id, (int) $req->start_date->year);
            $bal->decrement('pending', (float) $req->days);
            $bal->increment('used', (float) $req->days);
            $req->forceFill(['status' => 'approved', 'decided_by' => auth()->id(), 'decided_at' => now(), 'decision_note' => $note])->save();
        });
    }

    public function reject(LeaveRequest $req, ?string $note = null): void
    {
        abort_unless($req->status === 'pending', 422, 'Not pending.');
        DB::transaction(function () use ($req, $note) {
            $this->balance($req->employee, $req->leave_type_id, (int) $req->start_date->year)->decrement('pending', (float) $req->days);
            $req->forceFill(['status' => 'rejected', 'decided_by' => auth()->id(), 'decided_at' => now(), 'decision_note' => $note])->save();
        });
    }

    public function cancel(LeaveRequest $req): void
    {
        abort_unless(in_array($req->status, ['pending', 'approved'], true), 422, 'Cannot cancel.');
        DB::transaction(function () use ($req) {
            $bal = $this->balance($req->employee, $req->leave_type_id, (int) $req->start_date->year);
            $bal->decrement($req->status === 'approved' ? 'used' : 'pending', (float) $req->days);
            $req->forceFill(['status' => 'cancelled'])->save();
        });
    }

    public function unpaidDaysInPeriod(Employee $emp, string $period): float
    {
        $start = Carbon::parse($period . '-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $unpaidTypeIds = LeaveType::where(fn ($q) => $q->where('accrual', 'unpaid')->orWhere('paid', false))->pluck('id');

        return (float) LeaveRequest::where('employee_id', $emp->id)
            ->where('status', 'approved')
            ->whereIn('leave_type_id', $unpaidTypeIds)
            ->where('start_date', '<=', $end)
            ->where('end_date', '>=', $start)
            ->get()
            ->sum(fn ($r) => $this->workingDaysBetween(
                max($r->start_date, $start)->toDateString(),
                min($r->end_date, $end)->toDateString(),
            ));
    }

    public function accrueMonthly(): int
    {
        $year = (int) now()->year;
        $count = 0;
        foreach (LeaveType::where('accrual', 'monthly_accrual')->where('is_active', true)->get() as $type) {
            $monthly = round((float) $type->annual_quota / 12, 2);
            foreach (Employee::active()->get() as $emp) {
                $this->balance($emp, $type->id, $year)->increment('accrued', $monthly);
                $count++;
            }
        }

        return $count;
    }

    public function balanceFor(Employee $emp, ?int $year = null): \Illuminate\Support\Collection
    {
        $year ??= (int) now()->year;
        LeaveType::where('is_active', true)->get()->each(fn ($t) => $this->balance($emp, $t->id, $year));

        return LeaveBalance::with('type')->where('employee_id', $emp->id)->where('year', $year)->get();
    }

    private function balance(Employee $emp, int $typeId, int $year): LeaveBalance
    {
        $type = LeaveType::find($typeId);
        $opening = $type && $type->accrual === 'annual_fixed' ? (float) $type->annual_quota : 0.0;

        return LeaveBalance::firstOrCreate(
            ['employee_id' => $emp->id, 'leave_type_id' => $typeId, 'year' => $year],
            ['opening' => $opening, 'accrued' => 0, 'used' => 0, 'pending' => 0],
        );
    }

    private function workingDaysBetween(string $start, string $end): float
    {
        $s = Carbon::parse($start);
        $e = Carbon::parse($end);
        if ($e->lt($s)) {
            return 0;
        }
        $days = 0;
        for ($d = $s->copy(); $d->lte($e); $d->addDay()) {
            if (! $d->isWeekend()) {
                $days++;
            }
        }

        return $days;
    }
}
