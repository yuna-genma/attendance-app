<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function index(): View
    {

        $userId = Auth::id();

        $sixMonthsAgo = Carbon::now()->subMonths(5)->startOfMonth();
        $endOfThisMonth = Carbon::now()->endOfMonth();
        $thisMonthStr = Carbon::now()->format('Y-m');

        $attendances = Attendance::with('rests')
            ->where('user_id', $userId)
            ->whereBetween('date', [$sixMonthsAgo->format('Y-m-d'), $endOfThisMonth->format('Y-m-d')])
            ->get();

        $processedRecords = $attendances->map(function ($attendance) {
            if (! $attendance->getRawOriginal('clock_in') || ! $attendance->getRawOriginal('clock_out')) {
                return [
                    'month_key' => null,
                    'work' => 0,
                    'overtime' => 0,
                    'is_late' => false,
                    'is_early' => false,
                    'is_long' => false,
                ];
            }

            $in = Carbon::parse($attendance->getRawOriginal('clock_in'));
            $out = Carbon::parse($attendance->getRawOriginal('clock_out'));

            $totalStay = $in->diffInMinutes($out);

            $totalBreak = $attendance->rests->sum(function ($rest) {
                if ($rest->break_in && $rest->break_out) {
                    return Carbon::parse($rest->break_in)->diffInMinutes(Carbon::parse($rest->break_out));
                }

                return 0;
            });

            $workMinutes = max(0, $totalStay - $totalBreak);

            $overtimeMinutes = max(0, $workMinutes - 480);

            $isLate = $in->format('H:i:s') > '09:00:00';
            $isEarly = $out->format('H:i:s') < '18:00:00';
            $isLong = $workMinutes > 600;

            return [
                'month_key' => Carbon::parse($attendance->getRawOriginal('date'))->format('Y-m'),
                'work' => $workMinutes,
                'overtime' => $overtimeMinutes,
                'is_late' => $isLate,
                'is_early' => $isEarly,
                'is_long' => $isLong,
            ];
        })->filter(fn ($item) => $item['month_key'] !== null);

        $totalDays = $processedRecords->count();
        $summary = [
            'total_work_minutes' => $processedRecords->sum('work'),
            'total_overtime_minutes' => $processedRecords->sum('overtime'),
            'avg_work_minutes' => $totalDays > 0 ? (int) round($processedRecords->sum('work') / $totalDays) : 0,
        ];

        $monthlyTrend = collect(range(5, 0))
            ->map(function ($i) use ($processedRecords) {
                $carbonMonth = Carbon::now()->subMonths($i);
                $monthKey = $carbonMonth->format('Y-m');

                $monthData = $processedRecords->where('month_key', $monthKey);

                return [
                    'month' => $carbonMonth->format('Y年m月'),
                    'work_minutes' => (int) $monthData->sum('work'),
                    'overtime_minutes' => (int) $monthData->sum('overtime'),
                ];
            });

        $thisMonthRecords = $processedRecords->where('month_key', $thisMonthStr);

        $anomalies = [
            'late_count' => $thisMonthRecords->where('is_late', true)->count(),
            'early_leave_count' => $thisMonthRecords->where('is_early', true)->count(),
            'long_work_count' => $thisMonthRecords->where('is_long', true)->count(),
        ];

        return view('reports.index', compact('summary', 'monthlyTrend', 'anomalies'));
    }
}
