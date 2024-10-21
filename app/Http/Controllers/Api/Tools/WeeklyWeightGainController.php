<?php

namespace App\Http\Controllers\Api\Tools;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WeeklyWeightGainController extends Controller
{
    public function calculateWeightGain(Request $request)
    {
        // Validate input
        $request->validate([
            'height' => 'required|numeric',
            'height_unit' => 'required|in:cm,m',
            'weight_before_pregnancy' => 'required|numeric',
            'weight_before_pregnancy_unit' => 'required|in:kg,g',
            'current_weight' => 'required|numeric',
            'current_weight_unit' => 'required|in:kg,g',
            'current_week_of_pregnancy' => 'required|integer|min:1|max:40',
        ]);

        $height = $request->height; // Height in cm or meters
        $height_unit = $request->height_unit; // Height in cm or meters
        $weightBeforePregnancy = $request->weight_before_pregnancy; // Weight before pregnancy
        $weightBeforePregnancyUnit = $request->weight_before_pregnancy_unit; // kg or g
        $currentWeight = $request->current_weight; // Current weight
        $currentWeightUnit = $request->current_weight_unit; // kg or g
        $currentWeekOfPregnancy = $request->current_week_of_pregnancy;

        // Convert weights to kg if in grams
        if ($weightBeforePregnancyUnit === 'g') {
            $weightBeforePregnancy /= 1000;
        }
        if ($currentWeightUnit === 'g') {
            $currentWeight /= 1000;
        }
        if ($height_unit === 'cm') {
            $height /= 100;
        }

        // Calculate BMI
        $bmi = $currentWeight / (($height / 100) ** 2); // height in meters

        // Calculate weight gain up to current week
        $weightGain = $currentWeight - $weightBeforePregnancy;

        // Estimate weight gain at 12th week and 40th week
        // Assuming typical weight gain ranges:
        // - At 12 weeks: 1-2 kg (2.2-4.4 lbs)
        // - At 40 weeks: 9-13 kg (19.8-28.7 lbs)
        $weightGainAt12Weeks = 1 + (2 * $currentWeekOfPregnancy / 12); // kg
        $weightGainAt40Weeks = 9 + (4 * $currentWeekOfPregnancy / 40); // kg

        // Weight gain at 40 weeks in kg and pounds
        $weightAt40Weeks = $weightBeforePregnancy + $weightGainAt40Weeks;
        $weightAt40WeeksPounds = $weightAt40Weeks * 2.20462; // Convert kg to pounds

        // Weight gain at 12 weeks in kg and pounds
        $weightAt12Weeks = $weightBeforePregnancy + $weightGainAt12Weeks;
        $weightAt12WeeksPounds = $weightAt12Weeks * 2.20462; // Convert kg to pounds

        // Generate weekly weight gain table
        $weightGainTable = [];
        for ($week = 1; $week <= 40; $week++) {
            $weightGainAtWeek = $weightBeforePregnancy + (0.225 * $week); // Approximate weekly weight gain
            $weightGainRange = [
                'min' => $weightGainAtWeek,
                'max' => $weightGainAtWeek + 0.5 // Approximate range
            ];
            $weightGainTable[] = [
                'week' => $week,
                'weight_range' => $weightGainRange,
                'weight_gain' => $weightGainAtWeek - $weightBeforePregnancy
            ];
        }

        // Return response
        return response()->json([
            'status' => 'success',
            'bmi' => round($bmi, 2),
            'weight_gain_comparison' => round($weightGain, 2) . ' kg (' . round($weightGain * 2.20462, 2) . ' lbs)',
            'weight_gain_at_40_weeks' => [
                'kg' => round($weightGainAt40Weeks, 2),
                'lbs' => round($weightGainAt40Weeks * 2.20462, 2)
            ],
            'weight_gain_at_12_weeks' => [
                'kg' => round($weightGainAt12Weeks, 2),
                'lbs' => round($weightGainAt12Weeks * 2.20462, 2)
            ],
            'weight_at_40_weeks' => [
                'kg' => round($weightAt40Weeks, 2),
                'lbs' => round($weightAt40WeeksPounds, 2)
            ],
            'weight_at_12_weeks' => [
                'kg' => round($weightAt12Weeks, 2),
                'lbs' => round($weightAt12WeeksPounds, 2)
            ],
            'weekly_weight_gain_table' => $weightGainTable,
        ]);
    }

}
