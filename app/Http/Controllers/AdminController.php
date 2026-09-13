<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function attendanceIndex(Request $request)
    {
        $users = auth()->user();
        $baseDate = $attendance->date_object->format('Y-m-d')
        $dateString = $request->query('date');

        if ($dateString) {
            $date = Carbon::parse($dateString)->startOfDay();
        } else {
            $date = Carbon::today()->startOfDay();
        }

        $previousDay = $date->copy()->subDay();
        $nextDay = $date->copy()->addMonth();




    }

}
