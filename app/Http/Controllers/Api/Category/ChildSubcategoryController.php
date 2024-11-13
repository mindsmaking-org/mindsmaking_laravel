<?php

namespace App\Http\Controllers\Api\Category;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ChildSubcategory;
use App\Services\CategoryService;
use App\Services\ActivityService;

class ChildSubcategoryController extends Controller
{
    protected $categoryService;
    protected $activityService;
    
    public function __construct(CategoryService $categoryService, ActivityService $activityService){
        $this->categoryService = $categoryService;
        $this->activityService = $activityService;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'subcategory_id' => 'required|exists:subcategories,id',
            ]);
    
            $ckeck_if_name_exist = $this->categoryService->checkIfChildSubCategoryNameExist($validatedData["name"], $validatedData["subcategory_id"]);
    
            if($ckeck_if_name_exist){
                return $this->sendResponse(false, 'ChildSubcategory already exist.', [], 400);
            }

            
            $imagePath = $request->file('image')->store('images/childsubcategories', 'public');
    
            $childsubcategory = $this->categoryService->createChildSubcategory($validatedData, $imagePath);
    
            if(!$childsubcategory){
                return $this->sendResponse(false, 'Failed to create Childsubcategory data', [], 400);
            }
            $subcategory = $this->categoryService->getSubcategoryById($validatedData["subcategory_id"]);
            $subcategory_name = $subcategory->name;

            $category_id = $subcategory->category_id;
            $category = $this->categoryService->getCategoryById($category_id);
            $category_name = $category->name;

            $email = auth()->user()->email;
            $title = 'ChildSubCategory Created';
            $action = "The SubCategory of name $request->name was created under the subcategory of $subcategory_name and under the category of $category_name";

            $this->activityService->createActivity($email, $title, $action);

            return $this->sendResponse(true, 'successfully created', ['data'=>$childsubcategory], 201);
        } catch (Exception $e) {
            return $this->sendResponse(false, 'An error occurred while creating a ChildSubcategory', ['error' => $e->getMessage()], 500);
        }
        
    }

    public function getAllSubcategories()
    {
        try {
            $childsubcategories = $this->categoryService->getAllChildSubcategories();

            if(!$childsubcategories){
                return $this->sendResponse(false, 'failed to fetch all the childsubcategories data.', [], 400);
            }
            return $this->sendResponse(true, 'Successfully fetched all the childsubcaetgories data', ['data'=>$childsubcategories], 400);
        } catch (Exception $e) {
            return $this->sendResponse(false, 'An error occurred while fetching all the childSubcategories', ['error' => $e->getMessage()], 500);
        }
       
    }

    public function getChildSubcategoryInfo(Request $request)
    {
        try {
            $id = $request->query('id');
            $name = $request->query('name');

            if ($id) {
                $childsubcategory = $this->categoryService->getChildSubcategoryById($id);
            } elseif ($name) {
                $childsubcategory = $this->categoryService->getChildSubcategoryByName($name);
            } else {
                return $this->sendResponse(false, 'please provide either an ID or a name', [], 400);
            }

            if (!$childsubcategory) {
                return $this->sendResponse(false, '$ChildSubcategory does not exist', [], 400);
            }


            if(!$childsubcategory){
                return $this->sendResponse(false, 'failed to fetch data', [], 400);
            }

            return $this->sendResponse(true, 'succefully fetched the data', ['data'=>$childsubcategory], 200); 
        } catch (Exception $e) {
            return $this->sendResponse(false, 'An error occurred while fetching a ChildSubcategory data', ['error' => $e->getMessage()], 500);
        }
       
    }
    
    public function editChildSubcategory(Request $request)
    {
        try {
            $id = $request->query('child_subcategory_id');

            if (!$id) {
                return $this->sendResponse(false, 'child_subcategory_id is required in the query string.', [], 400);
            }

            $validatedData = $request->validate([
                'name' => 'required|string',
                'description' => 'required|string',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            $childsubcategory = $this->categoryService->getChildSubcategoryById($id);

            if (!$childsubcategory) {
                return $this->sendResponse(false, 'ChildSubCategory not found.', [], 400);
            }

            $childsubcategory->name = $validatedData['name'];
            if (isset($validatedData['description'])) {
                $category->description = $validatedData['description'];
            }

            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('images/childsubcategories', 'public');
                $category->image = $imagePath;
            }


            $childsubcategory->save();

            $email = auth()->user()->email;
            $title = 'ChildSubCategory Edited';
            $action = "The childsubcategory with id {$childsubcategory->id} was renamed to {$childsubcategory->name}.";

            $this->activityService->createActivity($email, $title, $action);

            return $this->sendResponse(true, 'Child SubCategory successfully updated', ['data' =>$childsubcategory], 200);
        } catch (Exception $e) {
            return $this->sendResponse(false, 'An error occurred while editing the child subcategory info', ['error' => $e->getMessage()], 500);
        }
    }
  
    public function deleteChildSubcategory(Request $request)
    {
        try {
            $id = $request->query('child_subcategory_id');

            if ($id) {
                $childsubcategory = $this->categoryService->getChildSubcategoryById($id);
            } else {
                return $this->sendResponse(false, 'please provide either an child_subcategory_id', [], 400);
            }

            if (!$childsubcategory) {
                return $this->sendResponse(false, 'child_subcategory_id not found', [], 400);
            }

            if ($subcategory->image && Storage::disk('public')->exists($subcategory->image)) {
                Storage::disk('public')->delete($subcategory->image);
            }
        
            $childsubcategory->delete();

            $email = auth()->user()->email;
            $title = 'ChildSubcategory Deleted';
            $action = "The childsubcategory with id {$id} and name '{$childsubcategory->name}' was deleted along with its child subcategories.";
    
            $this->activityService->createActivity($email, $title, $action);

            return $this->sendResponse(true, 'Child Subcategory deleted successfully.', [], 200);
        } catch (Exception $e) {
            return $this->sendResponse(false, 'An error occurred while deleting ChildSubcategory data', ['error' => $e->getMessage()], 500);
        }
    }
    
}
