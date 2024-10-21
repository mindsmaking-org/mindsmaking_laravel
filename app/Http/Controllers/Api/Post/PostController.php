<?php

namespace App\Http\Controllers\Api\Post;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use Illuminate\Support\Facades\Auth;
use App\Services\PostService;
use App\Services\ActivityService;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    protected $postService;
    protected $activityService;

    
    public function __construct(PostService $postService, ActivityService $activityService){
        $this->postService = $postService;
        $this->activityService = $activityService;
    }
    /**
     * Store a newly created post in storage.
     */
    public function store(Request $request)
    {
        try {
            $user = Auth::user();

            $validatedData = $request->validate([
                'content' => 'required',
                'image_*' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
                'category_id' => 'nullable|exists:categories,id',
                'subcategory_id' => 'nullable|exists:subcategories,id',
                'child_subcategory_id' => 'required|exists:child_subcategories,id',
                'writer' => 'required|email' 
            ]);

            $writerAdmin = $this->postService->check_if_email_is_AWriter($validatedData);

            if (!$writerAdmin) {
                return $this->sendResponse(false, 'The provided writer email does not belong to a valid admin with the writer role.', [], 400);
            }

            $contentData = [
                'content_data' => $validatedData['content'],
                'images' => [] 
            ];

            foreach ($request->allFiles() as $fileKey => $file) {
                if (str_contains($fileKey, 'image')) {
                    $contentData['images'][] = $file->store('images', 'public');
                }
            }

            $validatedData['content'] = json_encode($contentData);

            $existingPost = $this->postService->checkForExistingPost($validatedData);

            if($existingPost) {
                return $this->sendResponse(false, 'A post with the same content and child subcategory already exists.', [], 400);
            }
            $validatedData["posted_by"] = $user->email;
        
            $post = $this->postService->createPost($validatedData);
            $email = $user->email;
            $title = 'Post Created';
            $action = "A new post with the id of $post->id was created";

            $this->activityService->createActivity($email, $title, $action);

            return $this->sendResponse(true, 'Post Created Successfully', ["data" => $post], 201);
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'An error occurred while creating a new post', ['error' => $e->getMessage()], 500);
        }
    }    

    public function updatePost(Request $request, $id)
    {
        try {
            $user = Auth::user();
    
            $validatedData = $request->validate([
                'content' => 'required',
                'image_*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120', 
                'category_id' => 'nullable|exists:categories,id',
                'subcategory_id' => 'nullable|exists:subcategories,id',
                'child_subcategory_id' => 'required|exists:child_subcategories,id',
                'writer' => 'required|email'
            ]);

            $writerAdmin = $this->postService->check_if_email_is_AWriter($validatedData);

            if (!$writerAdmin) {
                return $this->sendResponse(false, 'The provided writer email does not belong to a valid admin with the writer role.', [], 400);
            }

    
            if ($id && is_numeric($id)) {
                $post = $this->postService->findPostById($id);
            } else {
                return $this->sendResponse(false, 'Post id of type number is required', [], 400);
            }
    
            if (!$post) {
                return $this->sendResponse(false, 'No post was found with the given ID', [], 400); 
            }
           
            if ($post->posted_by != $user->email) {
                return $this->sendResponse(false, 'You are not authorized to edit this post', [], 403);
            }
    
            $contentData = json_decode($post->content, true) ?? ['content_data' => '', 'images' => []];
    
            
            if ($request->has('content')) {
                $contentData['content_data'] = $validatedData['content'];
            }
    
            $newImagesUploaded = false;
    
            foreach ($request->allFiles() as $fileKey => $file) {
                if (str_contains($fileKey, 'image')) {
                    $newImagesUploaded = true;
    
                    foreach ($contentData['images'] as $oldImage) {
                        Storage::disk('public')->delete($oldImage);
                    }
                    
                    $contentData['images'] = [];
                    break;
                }
            }
    
            if ($newImagesUploaded) {
                foreach ($request->allFiles() as $fileKey => $file) {
                    if (str_contains($fileKey, 'image')) {
                        $contentData['images'][] = $file->store('images', 'public');
                    }
                }
            }
    
            $validatedData['content'] = json_encode($contentData);
    
            $post->update($validatedData);
    
            $email = $user->email;
            $title = 'Post Updated';
            $action = "The post with the id of $post->id was updated";

            $this->activityService->createActivity($email, $title, $action);
            return $this->sendResponse(true, 'Post updated successfully', ['data' => $post], 200);
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'An error occurred while updating the post', ['error' => $e->getMessage()], 500);
        }
    }

    public function getAllPost(Request $request)
    {
        try {       
            $query = $this->postService->queryPost();

            // $categoryId = $request->query('category_id');
            // $subcategoryId = $request->query('subcategory_id');
            $childSubcategoryId = $request->query('child_subcategory_id');
            $postId = $request->query('post_id');

            if ($postId) {
                $query->where('id', $postId);
            }elseif ($childSubcategoryId) {
                $query->where('child_subcategory_id', $childSubcategoryId);
            }
            
            $posts = $query->get();

            $response = [
                'status' => true,
                'message' => 'Data fetched successfully',
                'data' => $posts
            ];

            return $this->sendResponse(true, 'Data fetched successfully', ['data'=>$posts], 200);
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'An error occurred while fetching posts', ['error' => $e->getMessage()], 500);
        }
       
    }
    
    public function getPostViews($postId)
    {
        try {
            $post = $this->postService->findPostById($postId);

            if(!$post){
                return $this->sendResponse(flase, 'No result found for this.', [], 400);
            }

            return $this->sendResponse(true, 'successully fetched', ['views'=>$post->views], 200);
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'An error occurred while fetching posts', ['error' => $e->getMessage()], 500);
        }
        
    }

    public function incrementViews($postId)
    {
        try {
            $post = $this->postService->findPostById($postId);

            if(!$post){
                return $this->sendResponse(flase, 'No result found for this.', [], 400);
            }
    
            if (is_null($post->views)) {
                $post->views = 0;
                $post->save();
            }
    
            $post->increment('views');

            return $this->sendResponse(true, 'Views incremented successfully', [], 200);
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'An error occurred while fetching posts', ['error' => $e->getMessage()], 500);
        }
        
    }

    public function getPostCommentsCount($postId)
    {
        $post = Post::withCount('comments')->findOrFail($postId);
        return $this->SendResponse(true, "Successful", ["data"=> $post->comments_count], 200);
        
    }
    
    public function deletePost($id)
    {
        try {
            $user = Auth::user();
        
            if ($id && is_numeric($id)) {
                $post = $this->postService->findPostById($id);
            } else {
                return $this->sendResponse(false, 'Post id of type number is required', [], 400);
            }

            if(!$post){
                return $this->sendResponse(false, 'No post with the id given exist.', [], 400); 
            }
            
            if ($post->posted_by !== $user->email) {
                return $this->sendResponse(false, 'Unauthorized: You do not have permission to delete this post.', [], 403);
            }
        
            
            $contentData = json_decode($post->content, true);
        
        
            foreach ($contentData as $key => $value) {

                // Handle case where $value is a string
                if (is_string($value) && str_contains($key, 'image') && Storage::disk('public')->exists($value)) {
                    Storage::disk('public')->delete($value);
                
                // Handle case where $value is an array
                } elseif (is_array($value)) {
                    foreach ($value as $file) {
                        // Ensure each item in the array is a string before proceeding
                        if (is_string($file) && Storage::disk('public')->exists($file)) {
                            Storage::disk('public')->delete($file);
                        }
                    }
                }
            }
            

            $post->delete();
        
            $email = $user->email;
            $title = 'Post Updated';
            $action = "The post with the id of $post->id was updated";

            $this->activityService->createActivity($email, $title, $action);
            return $this->sendResponse(true, 'Post and associated images deleted successfully', [], 200);
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'An error occurred while updating the post', ['error' => $e->getMessage()], 500);
        }
        
    }
}
