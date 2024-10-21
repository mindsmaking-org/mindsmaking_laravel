<?php

namespace App\Http\Controllers\Api\Tools;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class GestationalController extends Controller
{
    public function calculateDiet(Request $request)
    {
        // Validate input
        $request->validate([
            'height' => 'required|numeric',
            'weight' => 'required|numeric',
            'weight_unit' => 'required|in:kg,g',
            'age' => 'required|integer|min:1',
            'exercise_level' => 'required|in:sedentary,lightly_active,moderately_active,very_active',
        ]);

        $height = $request->height; // in cm
        $weight = $request->weight; // weight can be kg or g
        $weightUnit = $request->weight_unit; // kg or g
        $age = $request->age;
        $exerciseLevel = $request->exercise_level;

        // Convert weight to kg if it's in grams
        if ($weightUnit === 'g') {
            $weight /= 1000; // Convert grams to kg
        }

        // Calculate BMI
        $bmi = $weight / (($height / 100) ** 2); // height in meters

        // Calculate IBW (Assuming female for this example)
        $ibw = 45.5 + 0.9 * ($height - 152);

        // Calculate BMR (Assuming female for this example)
        $bmr = 655 + 9.6 * $weight + 1.8 * $height - 4.7 * $age;

        // Adjust Weight (ABW) - Not typically used for normal weight calculations, more for obesity adjustments
        $abw = $ibw + 0.25 * ($weight - $ibw);

        // Calculate Weight Gain (if you have a target weight range, otherwise this can be omitted)
        // Example: Weight gain calculation can be adjusted based on specific needs

        // Calculate Calories
        $activityFactors = [
            'sedentary' => 1.2,
            'lightly_active' => 1.375,
            'moderately_active' => 1.55,
            'very_active' => 1.725,
        ];
        $activityFactor = $activityFactors[$exerciseLevel];
        $calories = $bmr * $activityFactor;

        // Return response
        return response()->json([
            'status' => 'success',
            'bmi' => round($bmi, 2),
            'ibw' => round($ibw, 2),
            'abw' => round($abw, 2),
            'bmr' => round($bmr, 2),
            'calories' => round($calories, 2),
        ]);
    }

}
