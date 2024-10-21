<?php

namespace App\Http\Controllers\Api\Tools;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PregnancyWeekController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }


    public function calculate(Request $request)
    {
        // Validate input
        $request->validate([
            'first_day_of_menstrual_period' => 'required|date',
            'cycle_length' => 'required|integer|min:21|max:35',
        ]);

        $firstDayOfMenstrualPeriod = Carbon::parse($request->first_day_of_menstrual_period);
        $cycleLength = $request->cycle_length;

        // Calculate Estimated Due Date (EDD)
        $edd = $firstDayOfMenstrualPeriod->copy()->addDays(280 - (28 - $cycleLength));

        // Calculate Gestational Age
        $today = Carbon::now();
        $gestationalAge = $firstDayOfMenstrualPeriod->diffInWeeks($today) . ' weeks ' . $firstDayOfMenstrualPeriod->diffInDays($today) % 7 . ' days';
        $gestational_week = $firstDayOfMenstrualPeriod->diffInWeeks($today);
        $gestational_day = $firstDayOfMenstrualPeriod->diffInDays($today) % 7;

        $gestational = [
            'gestationalAge' => $gestationalAge,
            'gestational_week' => $gestational_week,
            'gestational_day' => $gestational_day,
        ];

        // Calculate Until Due Date
        $untilDueDate = $today->diffInWeeks($edd) . ' weeks ' . $today->diffInDays($edd) % 7 . ' days';
        $untilDueDate_week = $today->diffInWeeks($edd);
        $untilDueDate_days = $today->diffInDays($edd) % 7;

        $untilDue = [
            'untilDueDate' => $untilDueDate,
            'untilDueDate_week' => $untilDueDate_week,
            'untilDueDate_days' => $untilDueDate_days,
        ];

        // Trimester Information
        $trimesters = [
            'First Trimester' => [
                'start_week' => 1,
                'end_week' => 12,
                'start_date' => $firstDayOfMenstrualPeriod->toDateString(),
                'end_date' => $firstDayOfMenstrualPeriod->copy()->addWeeks(12)->toDateString(),
                'milestone' => 'Organ formation begins'
            ],
            'Second Trimester' => [
                'start_week' => 13,
                'end_week' => 26,
                'start_date' => $firstDayOfMenstrualPeriod->copy()->addWeeks(12)->toDateString(),
                'end_date' => $firstDayOfMenstrualPeriod->copy()->addWeeks(26)->toDateString(),
                'milestone' => 'Fetal movement felt'
            ],
            'Third Trimester' => [
                'start_week' => 27,
                'end_week' => 40,
                'start_date' => $firstDayOfMenstrualPeriod->copy()->addWeeks(26)->toDateString(),
                'end_date' => $edd->toDateString(),
                'milestone' => 'Fetus gains weight rapidly'
            ]
        ];

        // Return response
        return response()->json([
            'status' => 'success',
            'gestational_age' => $gestational,
            'estimated_due_date' => $edd->toDateString(),
            'until_due_date' =>$untilDue,
            'trimesters' => $trimesters,
        ]);
    }

}
