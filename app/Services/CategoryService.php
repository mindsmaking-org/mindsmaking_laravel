<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Subcategory;
use App\Models\ChildSubcategory;

class CategoryService
{
    public function ckeckIfCategoryNameExist($request){
        return Category::where('name', $request->name)->first();
    }

    public function createCategory(array $validatedData, $image1Path, $image2Path = null)
    {
        $validatedData['image1'] = $image1Path;
        if ($image2Path) {
            $validatedData['image2'] = $image2Path;
        }
        return Category::create($validatedData);
    }

    public function getAllCategories(){
        return Category::with('subcategories.childSubcategories')->get();
    }

    public function getCategoryById($id){
        return Category::find($id);
    }

    public function getCategoryByName($name){
        return Category::where('name', $name);
    }

    // for  Subcategories
    public function checkIfSubCategoryNameExist($name, $id){
        return Subcategory::where('name', $name)
        ->where('category_id', $id)
        ->first();
    }

    public function createSubcategory(array $validatedData, $imagePath)
    {
        $validatedData['image'] = $imagePath;
        return Subcategory::create($validatedData);
    }

    public function getAllSubcategories(){
        return Subcategory::with('childSubcategories')->get();
    }

    public function getSubcategoryById($id){
        return Subcategory::find($id);
    }

    public function getSubcategoryByName($name){
        return Subcategory::where('name', $name);
    }

    // for  ChildSubcategories
    public function checkIfChildSubCategoryNameExist($name, $id){
        return ChildSubcategory::where('name', $name)
        ->where('subcategory_id', $id)
        ->first();
    }

    public function createChildSubcategory(array $validatedData)
    {
        return ChildSubcategory::create($validatedData);
    }

    public function getAllChildSubcategories(){
        return ChildSubcategory::with('childSubcategories')->get();
    }

    public function getChildSubcategoryById($id){
        return ChildSubcategory::find($id);
    }

    public function getChildSubcategoryByName($name){
        return ChildSubcategory::where('name', $name);
    }
}