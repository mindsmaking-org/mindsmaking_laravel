<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Admin;


class PostService
{
    public function checkForExistingPost(array $validatedData){
       return Post::where('content', $validatedData['content'])
        ->where('child_subcategory_id', $validatedData['child_subcategory_id'])
        ->first();
    }

    public function createPost(array $validatedData){
        return Post::create($validatedData);
    }

    public function findPostById($id){
        return Post::find($id);
    }

    public function queryPost(){
        return Post::with(['category', 'subcategory', 'childSubcategory']);
    }

    public function check_if_email_is_AWriter($validatedData){
        return Admin::where('email', $validatedData['writer'])
        ->whereRaw("FIND_IN_SET('writer', roles)")
        ->first();
    }
}