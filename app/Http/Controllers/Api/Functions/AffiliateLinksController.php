<?php

namespace App\Http\Controllers\Api\Functions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AffiliateLink;
use Illuminate\Support\Facades\Auth;
use App\Services\AffiliateLinkService;
use App\Services\ActivityService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AffiliateLinksController extends Controller
{
    
    protected $affiliateService;
    protected $activityService;

    
    public function __construct(AffiliateLinkService $affiliateLinkService, ActivityService $activityService){
        $this->affiliateService = $affiliateLinkService;
        $this->activityService = $activityService;
    }


    public function createAffiliate(Request $request){
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', 
            ]);
            
            if ($validator->fails()) {
                return $this->sendResponse(false, 'Validation failed', $validator->errors(), 422);
            }
            $user = auth()->user();

            $imagePath = null;

            if ($request->hasFile('image')) { 
                $imagePath = $request->file('image')->store("images/affiliate", 'public');
            }

            $data = [
                'name' => $request->input('name'),
                'image' => $imagePath, 
            ];

            $existingAffiliate = $this->affiliateLinkService->checkForExistingAffiliate($data);

            if($existingAffiliate) {
                return $this->sendResponse(false, 'An Affiliate link with the same content already exists.', [], 400);
            }

            $affiliate = $this->affiliateLinkService->createAffiliate($data);
            $email = $user->email;
            $title = 'Affiliate Link Created';
            $action = "A new Affiliate Link with the id of $affiliate->id was created";

            $this->activityService->createActivity($email, $title, $action);

            return $this->sendResponse(true, 'Affiliate Link Created Successfully', ["data" => $affiliate], 201);

        } catch (\Exception $e) {
            return $this->sendResponse(false, 'An error occurred while creating an affiliate link', ['error' => $e->getMessage()], 500);
        }
    }

    public function editAffiliate(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'string',
                'image' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            ]);

            if ($validator->fails()) {
                return $this->sendResponse(false, 'Validation failed', $validator->errors(), 422);
            }


            $affiliate = $this->affiliateLinkService->findAffiliateById($id);

            if (!$affiliate) {
                return $this->sendResponse(false, 'Affiliate link not found', [], 400);
            }

            if ($request->has('name')) {
                $affiliate->name = $request->input('name');
            }

        
            if ($request->hasFile('image')) {
                if ($affiliate->image && \Storage::disk('public')->exists($affiliate->image)) {
                    \Storage::disk('public')->delete($affiliate->image);
                }

                $newImagePath = $request->file('image')->store("images/affiliate", 'public');
                $affiliate->image = $newImagePath;
            }

            $affiliate->save();

            $user = auth()->user();
            $email = $user->email;
            $title = 'Affiliate Link Updated';
            $action = "Affiliate Link with ID $affiliate->id has been updated";

            $this->activityService->createActivity($email, $title, $action);

            return $this->sendResponse(true, 'Affiliate Link Updated Successfully', ['data' => $affiliate], 200);
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'An error occurred while editing the affiliate link', ['error' => $e->getMessage()], 500);
        }
    }

    public function getAffiliate(Request $request){
        try {
            if ($request->has('id')) {
                $id = $request->query('id');

                $affiliate = $this->affiliateLinkService->findAffiliateById($id); 
                if (!$affiliate) {
                    return $this->sendResponse(false, 'Affiliate not found', [], 400);
                }
            }
    
           
            if ($request->has('name')) {
                $name = $request->query('name');
                $affiliates = $this->affiliateLinkService->findAffiliateByName($name)->get();
            }
            
            $affiliates = $this->affiliateLinkService->allAffiliate();
            return $this->sendResponse(true, 'All affiliates fetched successfully', ['data' => $affiliates], 200);
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'An error occurred while getting the affiliates', ['error' => $e->getMessage()], 500);
        }
    }

    public function deleteAffiliate($id)
    {
        try {
            $affiliate = $this->affiliateService->findAffiliateById($id);
            if (!$affiliate) {
                return $this->sendResponse(false, 'Affiliate not found', [], 400);
            }

            $affiliate->delete();

            return $this->sendResponse(true, 'Affiliate deleted successfully', [], 200);
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'An error occurred while deleting the product', ['error' => $e->getMessage()], 500);
        }
    }

}
