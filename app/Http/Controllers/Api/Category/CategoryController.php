<?php

namespace App\Http\Controllers\Api\Category;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Services\CategoryService;
use App\Services\ActivityService;

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
                'name' => 'required|string'
            ]);
             
            $ckeck_if_name_exist = $this->categoryService->ckeckIfCategoryNameExist($request);
    
            if($ckeck_if_name_exist){
                return $this->sendResponse(false, 'category already exist',[], 400);
            }
    
            $category = $this->categoryService->createCategory($validatedData);

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

            if ($id) {
                $category = $this->categoryService->getCategoryById($id);
            }else {
                return $this->sendResponse(false, 'please provide either an category_id', [], 400);
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
            ]);

            $category = $this->categoryService->getCategoryById($id);

            if (!$category) {
                return $this->sendResponse(false, 'Category not found.', [], 400);
            }

            $category->name = $validatedData['name'];
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

    public function deleteCategory(Request $request){
        try {
            $id = $request->query('category_id');
            if (!$id) {
                return $this->sendResponse(false, 'category_id is required in the query string.', [], 400);
            }

            $category = $this->categoryService->getCategoryById($id);
            if (!$category) {
                return $this->sendResponse(false, 'Category not found.', [], 400);
            }
            $category->load('subcategories.childSubcategories');

            foreach ($category->subcategories as $subcategory) {
                $subcategory->childSubcategories()->delete();
                $subcategory->delete();
            }
            $category->delete();

            $email = auth()->user()->email;
            $title = 'Category Deleted';
            $action = "The category with ID {$id} and name '{$category->name}' was deleted along with its subcategories and child subcategories.";

            $this->activityService->createActivity($email, $title, $action);

            return $this->sendResponse(true, 'Category and related subcategories successfully deleted', [], 200);
        }catch (Exception $e) {
            return $this->sendResponse(false, 'An error occurred while deleting the category data', ['error' => $e->getMessage()], 500);
        }
    }

}
