<?php

namespace App\Http\Controllers\Api\Tools;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DueDateCalculatorController extends Controller
{
    public function calculateDueDate(Request $request)
    {
        
        // Validate the input
        $request->validate([
            'last_menstrual_period' => 'required|date',
        ]);

        // Get the first day of the last menstrual period
        $lmp = Carbon::parse($request->input('last_menstrual_period'));

        // Calculate the dates
        $estimatedFertilityStart = $lmp->copy()->addDays(10);
        $estimatedFertilityEnd = $lmp->copy()->addDays(17);
        $estimatedConceptionDate = $lmp->copy()->addDays(14);
        $firstTrimesterEnd = $lmp->copy()->addWeeks(12);
        $secondTrimesterEnd = $lmp->copy()->addWeeks(27);
        $estimatedDueDate = $lmp->copy()->addWeeks(40);

        // Return the response as JSON
        return response()->json([
            'status' => 'success',
            'message' => 'Due date and milestones calculated successfully.',
            'data' => [
                'estimated_fertility_dates' => [
                    'start' => $estimatedFertilityStart->toDateString(),
                    'end' => $estimatedFertilityEnd->toDateString(),
                ],
                'estimated_conception_date' => $estimatedConceptionDate->toDateString(),
                'first_trimester_ends' => $firstTrimesterEnd->toDateString(),
                'second_trimester_ends' => $secondTrimesterEnd->toDateString(),
                'estimated_due_date' => $estimatedDueDate->toDateString(),
            ],
        ]);
    }
}
