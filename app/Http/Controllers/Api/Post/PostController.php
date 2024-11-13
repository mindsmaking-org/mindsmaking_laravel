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
                'title' => 'required|string|max:255',
                'table_of_content' => 'required|array',
                'content' => 'required|array',
                'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
                'excerpt' => 'required|string|max:255',
                'key_facts' => 'required|array',
                'faq' => 'required|array',
                'sources' => 'required|array',
                'category_id' => 'nullable|exists:categories,id',
                'subcategory_id' => 'nullable|exists:subcategories,id',
                'child_subcategory_id' => 'required|exists:child_subcategories,id',
                'writer' => 'required|email'
            ]);

            $writerAdmin = $this->postService->check_if_email_is_AWriter($validatedData);

            if (!$writerAdmin) {
                return $this->sendResponse(false, 'The provided writer email does not belong to a valid admin with the writer role.', [], 400);
            }


            $imagePaths = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $imagePath = $image->store("images/post", 'public'); 
                    $imagePaths[] = $imagePath; 
                }
            }

            $postData = [
                'title' => $validatedData['title'],
                'table_of_content' => json_encode($validatedData['table_of_content']),
                'content' => $validatedData['content'],
                'images' => json_encode($imagePaths), // Save images as JSON
                'excerpt' => $validatedData['excerpt'],
                'key_facts' => json_encode($validatedData['key_facts']), // Convert array to JSON
                'faq' => json_encode($validatedData['faq']),
                'sources' => json_encode($validatedData['sources']),
                'category_id' => $validatedData['category_id'] ?? null,
                'subcategory_id' => $validatedData['subcategory_id'] ?? null,
                'child_subcategory_id' => $validatedData['child_subcategory_id'],
                'posted_by' => $user->email,
                'writer' => $validatedData['writer'],
            ];

            $existingPost = $this->postService->checkForExistingPost($postData);

            if($existingPost) {
                return $this->sendResponse(false, 'A post with the same content and child subcategory already exists.', [], 400);
            }
            $postData["posted_by"] = $user->email;
        
            $post = $this->postService->createPost($postData);
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
                'title' => 'required|string|max:255',
                'table_of_content' => 'required',
                'content' => 'required|string',
                'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
                'excerpt' => 'required|string|max:255',
                'key_facts' => 'required|array',
                'faq' => 'required|string',
                'sources' => 'required|string',
                'category_id' => 'nullable|exists:categories,id',
                'subcategory_id' => 'nullable|exists:subcategories,id',
                'child_subcategory_id' => 'required|exists:child_subcategories,id',
                'writer' => 'required|email'
            ]);

            // Check if the writer email belongs to an admin with the writer role
            $writerAdmin = $this->postService->check_if_email_is_AWriter($validatedData);
            if (!$writerAdmin) {
                return $this->sendResponse(false, 'The provided writer email does not belong to a valid admin with the writer role.', [], 400);
            }

            // Find the post by ID
            if (!is_numeric($id)) {
                return $this->sendResponse(false, 'Post ID must be a number', [], 400);
            }

            $post = $this->postService->findPostById($id);
            if (!$post) {
                return $this->sendResponse(false, 'No post was found with the given ID', [], 400);
            }

            // Check if the authenticated user is authorized to update the post
            if ($post->posted_by != $user->email) {
                return $this->sendResponse(false, 'You are not authorized to edit this post, you are not the publisher of this post', [], 403);
            }

            if ($request->has('content')) {
                $post->content = $validatedData['content'];
            }


            if ($request->hasFile('images')) {
                // Delete old images from storage
                foreach (json_decode($post->images, true) as $oldImage) {
                    Storage::disk('public')->delete($oldImage);
                }

                // Store new images and update the images column
                $newImages = [];
                foreach ($request->file('images') as $image) {
                    $newImages[] = $image->store('images/post', 'public');
                }
                $post->images = json_encode($newImages);
            }

            // Prepare data to update
            $postData = [
                'title' => $validatedData['title'],
                'table_of_content' => $validatedData['table_of_content'],
                'content' => json_encode($validatedData['content']),
                'images' => $post->images,
                'excerpt' => $validatedData['excerpt'],
                'key_facts' => json_encode($validatedData['key_facts']),
                'faq' => json_encode($validatedData['faq']),
                'sources' => json_encode($validatedData['sources']),
                'category_id' => $validatedData['category_id'] ?? null,
                'subcategory_id' => $validatedData['subcategory_id'] ?? null,
                'child_subcategory_id' => $validatedData['child_subcategory_id'],
            ];

            // Update the post with validated and processed data
            $post->update($postData);

            // Log the activity
            $email = $user->email;
            $title = 'Post Updated';
            $action = "The post with the ID of $post->id was updated";
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
            
            // Validate post ID
            if ($id && is_numeric($id)) {
                $post = $this->postService->findPostById($id);
            } else {
                return $this->sendResponse(false, 'Post ID of type number is required', [], 400);
            }
    
            if (!$post) {
                return $this->sendResponse(false, 'No post with the given ID exists.', [], 400);
            }
            
            // Check if the authenticated user is authorized to delete the post
            if ($post->posted_by !== $user->email) {
                return $this->sendResponse(false, 'Unauthorized: You do not have permission to delete this post.', [], 403);
            }
    
            // Decode images JSON and delete associated files
            $images = json_decode($post->images, true) ?? [];
            foreach ($images as $imagePath) {
                if (Storage::disk('public')->exists($imagePath)) {
                    Storage::disk('public')->delete($imagePath);
                }
            }
    
            // Delete the post
            $post->delete();
    
            // Log the deletion activity
            $email = $user->email;
            $title = 'Post Deleted';
            $action = "The post with ID $post->id was deleted";
            $this->activityService->createActivity($email, $title, $action);
    
            return $this->sendResponse(true, 'Post and associated images deleted successfully', [], 200);
    
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'An error occurred while deleting the post', ['error' => $e->getMessage()], 500);
        }
    }
    
}
