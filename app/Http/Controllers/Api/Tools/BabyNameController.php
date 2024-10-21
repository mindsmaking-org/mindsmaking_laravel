<?php

namespace App\Http\Controllers\Api\Tools;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BabyName;
use Illuminate\Support\Facades\Auth;

class BabyNameController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {

        $request->validate([
            'gender' => 'nullable|in:male,female,unisex',
            'type' => 'nullable|in:firstname,lastname,others',
        ]);


        $query = BabyName::query();

        // Apply filters if provided
        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }
        
        if ($request->has('origin')) {
            $query->where('origin', $request->input('origin'));
        }

        if ($request->has('theme')) {
            $query->where('theme', $request->input('theme'));
        }

        if ($request->has('gender')) {
            $query->where('gender', $request->input('gender'));
        }

        if ($request->has('culture')) {
            $query->where('culture', $request->input('culture'));
        }

        if ($request->has('country')) {
            $query->where('culture', $request->input('culture'));
        }

        if ($request->has('popular')) {
            $query->where('popular', $request->input('popular'));
        }

        // Fetch names
        $names = $query->get();

        return response()->json(['status' => 'success', 'data' => $names]);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'gender' => 'required|in:male,female,unisex',
            'type' => 'required|in:firstname,lastname,others',
            'origin' => 'required|string|max:100',
            'theme' => 'required|string|max:100',
            'culture' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'name' => 'required|string|max:255|unique:baby_names',
            'meaning' => 'required|string',
            'description' => 'nullable|string',
            'popular' => 'nullable|string',
        ]);

        $babyName = BabyName::create([
            'gender' => $request->input('gender'),
            'type' => $request->input('type'),
            'origin' => $request->input('origin'),
            'theme' => $request->input('theme'),
            'culture' => $request->input('culture'),
            'country' => $request->input('country'),
            'name' => $request->input('name'),
            'meaning' => $request->input('meaning'),
            'description' => $request->input('description'),
            'popular' => $request->input('popular'),
            'admin_id' => Auth::id(),
        ]);

        return response()->json(['status' => 'success', 'data' => $babyName]);
    }

    /**
     * Display the specified resource.
     */
    public function update(Request $request, $identifier)
    {
        $babyName = BabyName::where('id', $identifier)->orWhere('name', $identifier)->first();

        if (!$babyName) {
            return response()->json(['status' => 'error', 'message' => 'Baby name not found.'], 404);
        }

        $request->validate([
            'gender' => 'required|in:male,female,unisex',
            'type' => 'required|in:firstname,lastname',
            'origin' => 'required|string|max:100',
            'theme' => 'required|string|max:100',
            'culture' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'name' => 'required|string|max:255|unique:baby_names,name,' . $babyName->id,
            'meaning' => 'nullable|string',
            'description' => 'nullable|string',
            'popular' => 'nullable|string',
        ]);

        $babyName->update($request->all());

        return response()->json(['status' => 'success', 'data' => $babyName]);
    }

    // Remove the specified name from storage
    public function destroy(BabyName $babyName)
    {
        $babyName->delete();

        return response()->json(['status' => 'success', 'message' => 'Name deleted successfully.']);
    }

    // Show the specified name
    public function show($identifier)
    {
        // Attempt to find the BabyName by ID
        $babyName = BabyName::find($identifier);
    
        // If not found by ID, attempt to find by name
        if (!$babyName) {
            $babyName = BabyName::where('name', $identifier)->first();
        }
    
        // If still not found, return a "not found" response
        if (!$babyName) {
            return response()->json(['status' => 'error', 'message' => 'Baby name not found.'], 404);
        }
    
        // Return the found BabyName
        return response()->json(['status' => 'success', 'data' => $babyName]);
    }
    
 
    public function categories()
    {
        $origins = BabyName::select('origin')->distinct()->pluck('origin');
        $themes = BabyName::select('theme')->distinct()->pluck('theme');
        $cultures = Babyname::select('culture')->distinct()->pluck('culture');
        $countries = BabyName::select('country')->distinct()->pluck('country');

        $categories = [
            'origins' => $origins,
            'themes' => $themes,
            'cultures' => $cultures,
            'countries' => $countries,
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Categories fetched successfully.',
            'data' => $categories,
        ]);
    }



}
