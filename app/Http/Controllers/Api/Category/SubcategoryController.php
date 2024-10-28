<?php

namespace App\Http\Controllers\Api\Category;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subcategory;
use App\Services\CategoryService;
use App\Services\ActivityService;
use Illuminate\Support\Facades\Storage;

class SubcategoryController extends Controller
{
    protected $categoryService;
    protected $activityService;
    
    public function __construct(CategoryService $categoryService, ActivityService $activityService){
        $this->categoryService = $categoryService;
        $this->activityService = $activityService;
    }
   
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'category_id' => 'required|exists:categories,id',
            ]);
    
            $ckeck_if_name_exist = $this->categoryService->checkIfSubCategoryNameExist($validatedData["name"], $validatedData["category_id"]);
    
            if($ckeck_if_name_exist){
                return $this->sendResponse(false, 'Subcategory already exist.', [], 400);
            }

            $imagePath = $request->file('image')->store('images/subcategories', 'public');
    
            $subcategory = $this->categoryService->createSubcategory($validatedData, $imagePath);
    
            if(!$subcategory){
                return $this->sendResponse(false, 'Failed to create subcategory data', [], 400);
            }
            $category = $this->categoryService->getCategoryById($validatedData["category_id"]);
            $category_name = $category->name;
            $email = auth()->user()->email;
            $title = 'SubCategory Created';
            $action = "The SubCategory of name $request->name was created under the category of $category_name";

            $this->activityService->createActivity($email, $title, $action);

            return $this->sendResponse(true, 'successfully created', ['data'=>$subcategory], 201);
        } catch (Exception $e) {
            return $this->sendResponse(false, 'An error occurred while creating a Subcategory', ['error' => $e->getMessage()], 500);
        }
        
    }

    public function getAllSubcategories()
    {
        try {
            $subcategories = $this->categoryService->getAllSubcategories();

            if(!$subcategories){
                return $this->sendResponse(false, 'failed to fetch all the subcategories data.', [], 400);
            }
            return $this->sendResponse(true, 'Successfully fetched all the subcaetgories data', ['data'=>$subcategories], 200);
        } catch (Exception $e) {
            return $this->sendResponse(false, 'An error occurred while fetching all the Subcategories', ['error' => $e->getMessage()], 500);
        }
       
    }

    public function getASubcategoryInfo(Request $request)
    {
        try {
            $id = $request->query('subcategory_id');
            $name = $request->query('name');

            if ($id) {
                $subcategory = $this->categoryService->getSubcategoryById($id);
            }elseif($name){
                $subcategory = $this->categoryService->getSubcategoryByName($name);
            }else {
                return $this->sendResponse(false, 'please provide either a subcategory_id or a name', [], 400);
            }

            if (!$subcategory) {
                return $this->sendResponse(false, '$Subcategory does not exist', [], 400);
            }

            
            $subcategory->load('childSubcategories');

            if(!$subcategory){
                return $this->sendResponse(false, 'failed to fetch data', [], 400);
            }

            return $this->sendResponse(true, 'succefully fetched the data', ['data'=>$subcategory], 200); 
        } catch (Exception $e) {
            return $this->sendResponse(false, 'An error occurred while fetching a Subcategory data', ['error' => $e->getMessage()], 500);
        }
       
    }

    public function editSubcategory(Request $request){
        try {
            $id = $request->query('subcategory_id');

            if (!$id) {
                return $this->sendResponse(false, 'subcategory_id is required in the query string.', [], 400);
            }

            $validatedData = $request->validate([
                'name' => 'required|string',
                'description' => 'nullable|string',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            $subcategory = $this->categoryService->getSubcategoryById($id);

            if (!$subcategory) {
                return $this->sendResponse(false, 'SubCategory not found.', [], 400);
            }

            $subcategory->name = $validatedData['name'];
            if (isset($validatedData['description'])) {
                $category->description = $validatedData['description'];
            }

            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('images/subcategories', 'public');
                $category->image = $imagePath;
            }

            $subcategory->save();

            $email = auth()->user()->email;
            $title = 'SubCategory Edited';
            $action = "The subcategory with id {$subcategory->id} was renamed to {$subcategory->name}.";

            $this->activityService->createActivity($email, $title, $action);

            return $this->sendResponse(true, 'SubCategory successfully updated', ['data' => $subcategory], 200);
        } catch (Exception $e) {
            return $this->sendResponse(false, 'An error occurred while editing the sub category info', ['error' => $e->getMessage()], 500);
        }
    }

    public function deleteSubcategory(Request $request)
    {
        try {
            $id = $request->query('subcategory_id');
            
            if (!$id) {
                return $this->sendResponse(false, 'subcategory_id is required in the query string.', [], 400);
            }

            $subcategory = $this->categoryService->getSubcategoryById($id);
            if (!$subcategory) {
                return $this->sendResponse(false, 'SubCategory not found.', [], 400);
            }
            $subcategory->load('childSubcategories');

            if ($subcategory->image && Storage::disk('public')->exists($subcategory->image)) {
                Storage::disk('public')->delete($subcategory->image);
            }
            $subcategory->childSubcategories()->delete();
            $subcategory->delete();

            $email = auth()->user()->email;
            $title = 'Subcategory Deleted';
            $action = "The subcategory with id {$id} and name '{$subcategory->name}' was deleted along with its child subcategories.";
            
            $this->activityService->createActivity($email, $title, $action);

            return $this->sendResponse(true, 'Subcategory and related child subcategories successfully deleted', [], 200);
        } catch (Exception $e) {
            return $this->sendResponse(false, 'An error occurred while deleting the subcategory', ['error' => $e->getMessage()], 500);
        }
    }
}
