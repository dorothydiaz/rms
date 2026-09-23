<?php

namespace App\Services;

use Carbon\Carbon;

class AttendanceCalculationService
{
    /**
     * Calculate comprehensive attendance details for an attendance record or given times.
     *
     * @param string $date e.g. '2026-09-24'
     * @param string|null $scheduleStart e.g. '08:00:00' or '22:00:00'
     * @param string|null $scheduleEnd e.g. '17:00:00' or '07:00:00'
     * @param string|null $timeIn e.g. '08:05:00' or '2026-09-24 08:05:00'
     * @param string|null $timeOut e.g. '17:15:00' or '2026-09-24 17:15:00'
     * @param string|null $breakOut
     * @param string|null $breakIn
     * @param bool $isOvernight
     * @return array
     */
    public function calculate(
        string $date,
        ?string $scheduleStart,
        ?string $scheduleEnd,
        ?string $timeIn,
        ?string $timeOut,
        ?string $breakOut = null,
        ?string $breakIn = null,
        bool $isOvernight = false
    ): array {
        if (!$timeIn || !$timeOut) {
            return [
                'total_hours' => 0.0,
                'regular_hours' => 0.0,
                'late_minutes' => 0,
                'undertime_minutes' => 0,
                'overtime_hours' => 0.0,
                'night_diff_hours' => 0.0,
                'break_minutes' => 0,
                'status' => 'Absent',
            ];
        }

        // Parse actual in / out
        $inDateTime = Carbon::parse(strlen($timeIn) > 8 ? $timeIn : "$date $timeIn");
        $outDateTime = Carbon::parse(strlen($timeOut) > 8 ? $timeOut : "$date $timeOut");

        // If timeOut is earlier than timeIn, it naturally crossed midnight
        if ($outDateTime->lt($inDateTime)) {
            $outDateTime->addDay();
        }

        // Calculate break duration
        $breakMinutes = 0;
        if ($breakOut && $breakIn) {
            $bOut = Carbon::parse(strlen($breakOut) > 8 ? $breakOut : "$date $breakOut");
            $bIn = Carbon::parse(strlen($breakIn) > 8 ? $breakIn : "$date $breakIn");
            if ($bIn->lt($bOut)) {
                $bIn->addDay();
            }
            $breakMinutes = max(0, $bOut->diffInMinutes($bIn));
        } elseif ($scheduleStart && $scheduleEnd) {
            // Default 1 hour unpaid meal break if shift is 8+ hours
            $breakMinutes = 60;
        }

        // Gross duration in minutes
        $grossMinutes = max(0, $inDateTime->diffInMinutes($outDateTime));
        $workedMinutes = max(0, $grossMinutes - $breakMinutes);
        $totalHours = round($workedMinutes / 60, 2);

        // Schedule parsing
        $lateMinutes = 0;
        $undertimeMinutes = 0;
        $overtimeHours = 0.0;
        $regularHours = min(8.0, $totalHours);

        if ($scheduleStart && $scheduleEnd) {
            $schedStartDt = Carbon::parse("$date $scheduleStart");
            $schedEndDt = Carbon::parse("$date $scheduleEnd");
            if ($isOvernight || $schedEndDt->lt($schedStartDt)) {
                $schedEndDt->addDay();
            }

            // Late calculation
            if ($inDateTime->gt($schedStartDt)) {
                $lateMinutes = $schedStartDt->diffInMinutes($inDateTime);
            }

            // Undertime calculation (if left early before scheduled shift end)
            if ($outDateTime->lt($schedEndDt)) {
                $undertimeMinutes = $outDateTime->diffInMinutes($schedEndDt);
            }

            // Overtime hours (worked beyond scheduled end, with minimum 30 min OT threshold)
            if ($outDateTime->gt($schedEndDt)) {
                $otMinutes = $schedEndDt->diffInMinutes($outDateTime);
                if ($otMinutes >= 30) {
                    $overtimeHours = round($otMinutes / 60, 2);
                }
            }
        } elseif ($totalHours > 8.0) {
            $overtimeHours = round($totalHours - 8.0, 2);
            $regularHours = 8.0;
        }

        // Night Differential Calculation (10:00 PM to 6:00 AM)
        $nightDiffHours = $this->calculateNightDifferentialHours($inDateTime, $outDateTime);

        // Deduct break from night diff if break was within night hours
        if ($nightDiffHours > 0 && $breakMinutes > 0) {
            // Prorate break if overnight shift
            $nightDiffHours = max(0.0, round($nightDiffHours - ($breakMinutes / 60) * 0.5, 2));
        }

        // Determine Status
        $status = 'Present';
        if ($lateMinutes > 0 && $lateMinutes >= 15) {
            $status = 'Late';
        }
        if ($totalHours <= 4.0 && $totalHours > 0) {
            $status = 'Half Day';
        }

        return [
            'total_hours' => $totalHours,
            'regular_hours' => $regularHours,
            'late_minutes' => $lateMinutes,
            'undertime_minutes' => $undertimeMinutes,
            'overtime_hours' => $overtimeHours,
            'night_diff_hours' => $nightDiffHours,
            'break_minutes' => $breakMinutes,
            'status' => $status,
        ];
    }

    /**
     * Calculates hours worked between 10:00 PM (22:00) and 6:00 AM (06:00).
     */
    public function calculateNightDifferentialHours(Carbon $in, Carbon $out): float
    {
        $current = $in->copy();
        $totalNdMinutes = 0;

        // Iterate in 15-minute intervals to precisely capture crossing night intervals
        while ($current->lt($out)) {
            $next = $current->copy()->addMinutes(15);
            if ($next->gt($out)) {
                $intervalMinutes = $current->diffInMinutes($out);
            } else {
                $intervalMinutes = 15;
            }

            $hour = $current->hour;
            // 22:00 (10 PM) through 23:59 and 00:00 through 05:59 are within the 10PM - 6AM night window
            if ($hour >= 22 || $hour < 6) {
                $totalNdMinutes += $intervalMinutes;
            }

            $current = $next;
        }

        return round($totalNdMinutes / 60, 2);
    }
}
