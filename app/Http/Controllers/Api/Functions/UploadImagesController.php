<?php

namespace App\Http\Controllers\Api\Functions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\ActivityService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UploadImagesController extends Controller
{
    protected $activityService;

    public function __construct(ActivityService $activityService){
        $this->activityService = $activityService;
    }

    public function uploadImages(Request $request)
    {
        try {
            $user = Auth::user();
    
            $validator = Validator::make($request->all(), [
                'image_1' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', 
                'image_2' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', 
                'image_3' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', 
                'image_4' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', 
                'image_5' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120', 
            ]);
    
            if ($validator->fails()) {
                return $this->sendResponse(false, 'Validation failed', $validator->errors(), 422);
            }
    
            $imageUrls = [];
    
            
            foreach (['image_1', 'image_2', 'image_3', 'image_4', 'image_5'] as $imageField) {
                if ($request->hasFile($imageField)) {
                    $imagePath = $request->file($imageField)->store("images/post", 'public');
                    $imageUrls[$imageField] = $imagePath; 
                }
            }
    
            return $this->sendResponse(true, 'Images uploaded successfully', ['image_urls' => $imageUrls], 201);
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'An error occurred while uploading images', ['error' => $e->getMessage()], 500);
        }
    }
    
    public function deleteImage(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'image_url' => 'required|url',
            ]);

            $imageUrl = $validatedData['image_url'];
            $path = str_replace(asset('storage') . '/', '', $imageUrl);

            if (!Storage::disk('public')->exists($path)) {
                return $this->sendResponse(false, 'Image not found', [], 404);
            }

            Storage::disk('public')->delete($path);

            $user = Auth::user();
            $title = 'Image Deleted';
            $action = "An image was deleted by {$user->email}. Image URL: $imageUrl";
            $this->activityService->createActivity($user->email, $title, $action);

            return $this->sendResponse(true, 'Image deleted successfully', [], 200);
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'An error occurred while deleting the image', ['error' => $e->getMessage()], 500);
        }
    }

    public function getAllImages(Request $request)
    {
        try {
            $folder = $request->query('folder', 'images/post'); 

            if (!Storage::disk('public')->exists($folder)) {
                return $this->sendResponse(false, "Folder '$folder' does not exist", [], 404);
            }

            $files = Storage::disk('public')->files($folder);

            $images = array_filter($files, function ($file) {
                return preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $file);
            });

            $imageUrls = array_map(function ($image) {
                return asset("storage/$image");
            }, $images);

            return $this->sendResponse(true, 'Images retrieved successfully', ['images' => $imageUrls], 200);
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'An error occurred while fetching images', ['error' => $e->getMessage()], 500);
        }
    }
    
}
