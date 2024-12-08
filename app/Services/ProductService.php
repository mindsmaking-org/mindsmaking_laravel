<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Product;

class ProductService
{
    public function checkForExistingProduct(array $validatedData){
        return Product::where('name', $validatedData['name'])
         ->where('title', $validatedData['title'])
         ->first();
    }

    public function createProduct(array $data){
        return Product::create($data);
    }

    public function findProductById($id){
        return Product::find($id);
    }

    public function findProductByName($name){
        return Product::where('name', 'like', "%{$name}%")->get();
    }

    public function findProductByTitle($title){
        return Product::where('title', 'like', "%{$title}%")->get();
    }

    public function getProductsByParentPostId($postId)
    {
        return Product::where('parent_post_id', $postId)->get();
    }

    public function getProductsByAffiliatePostIds(array $affiliatePostIds)
    {
        return Product::whereIn('parent_post_id', $affiliatePostIds)->get();
    }

    public function allProduct(){
        return Product::all();
    }

}