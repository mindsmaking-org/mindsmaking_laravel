<?php

namespace App\Http\Controllers\Api\Category;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Services\CategoryService;
use App\Services\ActivityService;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    protected $categoryService;
    protected $activityService;
    
    public function __construct(CategoryService $categoryService, ActivityService $activityService){
        $this->categoryService = $categoryService;
        $this->activityService = $activityService;
    }

    public function store(Request $request){

        try {
            
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                'image1' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'image2' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            ]);
             
            $ckeck_if_name_exist = $this->categoryService->ckeckIfCategoryNameExist($request);
    
            if($ckeck_if_name_exist){
                return $this->sendResponse(false, 'category already exist',[], 400);
            }
    
            $image1Path = $request->file('image1')->store('images/categories', 'public');

            $image2Path = $request->hasFile('image2') ? $request->file('image2')->store('images/categories', 'public') : null;
            
            $category = $this->categoryService->createCategory($validatedData, $image1Path, $image2Path);

            if($category){
                $email = auth()->user()->email;
                $title = 'Category Created';
                $action = "The category of name $request->name was created";

                $this->activityService->createActivity($email, $title, $action);

                return $this->sendResponse(true, 'succefully created', ['data'=>$category], 201);
            }

            return $this->sendResponse(false, 'Failed to create Category data', [], 400);
    
           
        } catch (Exception $e) {
            return $this->sendResponse(false, 'An error occurred while Creating category', ['error' => $e->getMessage()], 500);
        }
        
    }

    public function getAllCategories()
    {
        try {
            $categories = $this->categoryService->getAllCategories();

            if(!$categories){
                return $this->sendResponse(false, 'this action failed', [], 400);
            }
            return $this->sendResponse(true, 'this action was successful', ['data'=>$categories], 200);
        } catch (Exception $e) {
            return $this->sendResponse(false, 'An error occurred while fetching all categories data', ['error' => $e->getMessage()], 500);
        }
        
    }

    public function getACategoryInfo(Request $request)
    {
        try {
            $id = $request->query('category_id');
            $name = $request->query('name');

            if ($id) {
                $category = $this->categoryService->getCategoryById($id);
            } elseif ($name) {
                $category = $this->categoryService->getCategoryByName($name);
            }else {
                return $this->sendResponse(false, 'please provide either an category_id or a category name ', [], 400);
            }

            if (!$category) {
                return $this->sendResponse(false, 'Category does not exist', [], 400);
            }

            
            $category->load('subcategories.childSubcategories');

            if(!$category){
                return $this->sendResponse(false, 'failed to fetch data', [], 400);
            }

            return $this->sendResponse(true, 'succefully fetched the data', ['data'=>$category], 200); 
        } catch (Exception $e) {
            return $this->sendResponse(false, 'An error occurred while fetching a category data', ['error' => $e->getMessage()], 500);
        }
       
    }

    public function editCategory(Request $request){
        try {
            $id = $request->query('category_id');

            if (!$id) {
                return $this->sendResponse(false, 'category_id is required in the query string.', [], 400);
            }

            $validatedData = $request->validate([
                'name' => 'required|string',
                'description' => 'nullable|string',
                'image1' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'image2' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            $category = $this->categoryService->getCategoryById($id);

            if (!$category) {
                return $this->sendResponse(false, 'Category not found.', [], 400);
            }

            $category->name = $validatedData['name'];
            if (isset($validatedData['description'])) {
                $category->description = $validatedData['description'];
            }

            if ($request->hasFile('image1')) {
                $image1Path = $request->file('image1')->store('images/categories', 'public');
                $category->image1 = $image1Path;
            }

            if ($request->hasFile('image2')) {
                $image2Path = $request->file('image2')->store('images/categories', 'public');
                $category->image2 = $image2Path;
            }
            $category->save();

            $email = auth()->user()->email;
            $title = 'Category Edited';
            $action = "The category with id {$category->id} was renamed to {$category->name}.";

            $this->activityService->createActivity($email, $title, $action);

            return $this->sendResponse(true, 'Category successfully updated', ['data' => $category], 200);
        } catch (Exception $e) {
            return $this->sendResponse(false, 'An error occurred while editing the category info', ['error' => $e->getMessage()], 500);
        }
    }

    public function deleteCategory(Request $request)
    {
        try {
            $id = $request->query('category_id');
            if (!$id) {
                return $this->sendResponse(false, 'category_id is required in the query string.', [], 400);
            }

            // Retrieve the category
            $category = $this->categoryService->getCategoryById($id);
            if (!$category) {
                return $this->sendResponse(false, 'Category not found.', [], 400);
            }

            // Load related subcategories and child subcategories
            $category->load('subcategories.childSubcategories');

            // Delete the category images if they exist
            if ($category->image1 && Storage::disk('public')->exists($category->image1)) {
                Storage::disk('public')->delete($category->image1);
            }
            if ($category->image2 && Storage::disk('public')->exists($category->image2)) {
                Storage::disk('public')->delete($category->image2);
            }

            // Delete subcategories and their images
            foreach ($category->subcategories as $subcategory) {
                // Delete the subcategory image if it exists
                if ($subcategory->image && Storage::disk('public')->exists($subcategory->image)) {
                    Storage::disk('public')->delete($subcategory->image);
                }

                // Delete child subcategories
                $subcategory->childSubcategories()->delete();
                
                // Delete the subcategory
                $subcategory->delete();
            }

            // Delete the main category
            $category->delete();

            // Log the activity
            $email = auth()->user()->email;
            $title = 'Category Deleted';
            $action = "The category with ID {$id} and name '{$category->name}' was deleted along with its subcategories and child subcategories.";

            $this->activityService->createActivity($email, $title, $action);

            return $this->sendResponse(true, 'Category and related subcategories successfully deleted', [], 200);

        } catch (Exception $e) {
            return $this->sendResponse(false, 'An error occurred while deleting the category data', ['error' => $e->getMessage()], 500);
        }
    }

}
