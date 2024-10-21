<?php

namespace App\Services;

use App\Models\Comment;



class CommentService
{
    public function createComment($content, $user_id, $post_id){
       return Comment::create([
            'post_id' => $post_id,
            'user_id' => $user_id,
            'content' => $content
       ]);
    }

    public function getCommentWherePostIdAndCommentId($postId, $commentId){
        return Comment::where('post_id', $postId)->findOrFail($commentId);
    }

    public function findCommentById($commentId){
        return Comment::find($commentId);
    }

    public function findComment($commentId, $post_id){
        return Comment::where('post_id', $postId)->find($commentId);
    }
}