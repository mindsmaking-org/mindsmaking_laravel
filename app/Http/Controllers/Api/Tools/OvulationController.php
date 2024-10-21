<?php

namespace App\Http\Controllers\Api\Tools;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

class OvulationController extends Controller
{
    public function calculate(Request $request)
    {
        // Validate input
        $request->validate([
            'last_period_date' => 'required|date',
            'cycle_length' => 'required|integer|min:21|max:35',
        ]);

        // Get inputs
        $lastPeriodDate = Carbon::parse($request->input('last_period_date'));
        $cycleLength = (int) $request->input('cycle_length');

        // Calculate next period and ovulation date
        $nextPeriodDate = $lastPeriodDate->copy()->addDays($cycleLength);
        $ovulationDate = $nextPeriodDate->copy()->subDays(14); // Ovulation typically 14 days before next period

        // Calculate fertile window (5 days before ovulation, 1 day after)
        $fertileStartDate = $ovulationDate->copy()->subDays(5);
        $fertileEndDate = $ovulationDate->copy()->addDay();

        // Prepare the response data
        $data = [
            'last_period_date' => $lastPeriodDate->toDateString(),
            'cycle_length' => $cycleLength,
            'next_period_date' => $nextPeriodDate->toDateString(),
            'ovulation_date' => $ovulationDate->toDateString(),
            'fertile_window_start' => $fertileStartDate->toDateString(),
            'fertile_window_end' => $fertileEndDate->toDateString(),
        ];

        // Return the data in JSON format
        return response()->json([
            'status' => 'success',
            'message' => 'Ovulation details calculated successfully',
            'data' => $data,
        ], 200);
    }
}
