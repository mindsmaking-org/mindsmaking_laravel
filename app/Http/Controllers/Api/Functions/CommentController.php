<?php

namespace App\Http\Controllers\Api\Functions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Support\Facades\Auth;
use App\Services\PostService;
use App\Services\CommentService;
use App\Services\ActivityService;
use Illuminate\Support\Facades\Storage;

class CommentController extends Controller
{
    protected $postService;
    protected $commentService;
    protected $activityService;

    
    public function __construct(PostService $postService,commentService $commentService, ActivityService $activityService){
        $this->postService = $postService;
        $this->commentService = $commentService;
        $this->activityService = $activityService;
    }

    public function getPostComment(Request $request)
    {
        try {
            $postId = $request->route('post');
            if(!is_numeric($postId)){
                return $this->sendResponse(false, "the id needs to be a type if int", [], 400);
            }
            $post = $this->postService->findPostById($postId);
            if(!$post){
                return $this->sendResponse(false, "no post data matches the id given", [], 400);
            }
            $comments = $post->comments()->with('user')->get();

            return $this->sendResponse(true, 'successfully fetched', ['data'=>$comments], 200);
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'An error occurred while fetching comment for post', ['error' => $e->getMessage()], 500);
        }
        
    }

    public function storeComment(Request $request)
    {
        try {
            $postId = $request->route('post');
            if(!is_numeric($postId)){
                return $this->sendResponse(false, "the id needs to be a type if int", [], 400);
            }

            $request->validate([
                'content' => 'required|string|max:1000',
            ]);
            $post = $this->postService->findPostById($postId);

            if(!$post){
                return $this->sendResponse(false, "no post data matches the id given", [], 400);
            }
            $comment = $this->commentService->createComment($request->input('content'), auth()->id(), $post->id);
            
            if(!$comment){
                return $this->sendResponse(false, "failed to add comment", [], 400);
            }

            return $this->sendResponse(true, "successfully added comment", ['data'=>$comment], 200);
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'An error occurred while storing comment for a post', ['error' => $e->getMessage()], 500);
        }
    }
    

    // Get a specific comment
    public function showComment($postId, $commentId)
    {
        try {
            $commentId = $request->route('comment');
            $postId = $request->route('post');

            if(!is_numeric($postId) && !is_numeric($commentId)){
                return $this->sendResponse(false, 'both post id and comment id are of int type', [], 400);
            }

            $comment = $this->commentService->getCommentWherePostIdAndCommentId($postId, $commentId);
        
            if(!$comment){
                return $this->sendResponse(false, 'failed to fetch data', [], 400);
            }

            return $this->sendResponse(true, 'data fetched successfully', ['data'=>$comment], 200);
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'An error occurred while storing comment for a post', ['error' => $e->getMessage()], 500);
        }
        
    }

    // Update a comment
    public function updateComment(Request $request, $postId, $commentId)
    {
        try {
            $postId = $request->route('post');
            $commentId = $request->route('comment');

            if(!is_numeric($postId) && !is_numeric($commentId)){
                return $this->sendResponse(false, "Comment id, and post id should both be of type int", [], 400);
            }

            $request->validate([
                'content' => 'required|string|max:1000',
            ]);
            $postExists = $this->postService->findPostById($postId);

            if (!$postExists) {
                return $this->sendResponse(false, "Post not found", [], 400);
            }
    
            $commentExists = $this->commentService->findCommentById($commentId);

            if (!$commentExists) {
                return $this->sendResponse(false, "Comment not found", [], 400);
            }

            $comment = $this->commentService->findComment($commentId, $post_id);

            if ($comment->user_id !== auth()->id()) {
                return $this->sendResponse(false, "You are not authorized to edit this comment", [], 403);
            }

            $comment->update([
                'content' => $request->input('content'),
            ]);

            return $this->sendResponse(false, "Comment updated successfully", ['data'=>$comment], 200);
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'An error occurred while updating comment for a post', ['error' => $e->getMessage()], 500);
        }        
    }
    
    // Delete a comment
    public function destroyComment(Request $request)
    {
        try {
            $postId = $request->route('post');
            $commentId = $request->route('comment');

            if(!is_numeric($postId) && !is_numeric($commentId)){
                return $this->sendResponse(false, "Comment id, and post id should both be of type int", [], 400);
            }

            $postExists = $this->postService->findPostById($postId);

            if (!$postExists) {
                return $this->sendResponse(false, "Post not found", [], 400);
            }
    
            $commentExists = $this->commentService->findCommentById($commentId);

            if (!$commentExists) {
                return $this->sendResponse(false, "Comment not found", [], 400);
            }
            
            $comment = $this->commentService->findComment($commentId, $post_id);
            if ($comment->user_id !== auth()->id()) {
                return $this->sendResponse(false, "You are not authorized to delete this comment", [], 403);
            }
            $comment->delete();

            return $this->sendResponse(true, 'Comment deleted successfully', [], 200);
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'An error occurred while deleteing comment for a post', ['error' => $e->getMessage()], 500);
        }
    }
    public function destroyCommentByAdmin(Request $request)
    {
        try {
            $postId = $request->route('post');
            $commentId = $request->route('comment');

            if(!is_numeric($postId) && !is_numeric($commentId)){
                return $this->sendResponse(false, "Comment id, and post id should both be of type int", [], 400);
            }

            $postExists = $this->postService->findPostById($postId);

            if (!$postExists) {
                return $this->sendResponse(false, "Post not found", [], 400);
            }
    
            $commentExists = $this->commentService->findCommentById($commentId);

            if (!$commentExists) {
                return $this->sendResponse(false, "Comment not found", [], 400);
            }
            
            $comment = $this->commentService->findComment($commentId, $post_id);
            $comment->delete();

            $email = auth()->user()->email;
            $title = 'Comment Deleted';
            $action = "A Comment was deleted .";

            $this->activityService->createActivity($email, $title, $action);

            return $this->sendResponse(true, 'Comment deleted successfully', [], 200);
        } catch (\Exception $e) {
            return $this->sendResponse(false, 'An error occurred while deleteing comment for a post', ['error' => $e->getMessage()], 500);
        }
    }
}
