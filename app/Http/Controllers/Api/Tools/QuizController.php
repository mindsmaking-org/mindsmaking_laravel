<?php

namespace App\Http\Controllers\Api\Tools;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\Answer;
use Illuminate\Support\Facades\Auth;

class QuizController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $quiz = Quiz::create([
            'admin_id' => Auth::id(),  // Capture admin_id
            'title' => $request->input('title'),
            'description' => $request->input('description'),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Quiz created successfully',
            'data' => $quiz,
        ]);
    }

    public function addQuestion(Request $request, $quizId)
    {
        // Validate the incoming request data
        $request->validate([
            'quizId' => 'required',
            'question' => 'required|string',
            'options' => 'required|array',
            'options.*.answer' => 'required|string',
            'options.*.is_correct' => 'required|boolean',
        ]);
    
        // Validate quizId to ensure it's a number
        if (!is_numeric($quizId)) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Quiz ID must be a number'
            ], 400);
        }
    
        // Check if the quizId from the request matches the route parameter
        if ($request->input('quizId') != $quizId) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Quiz ID does not match'
            ], 400);
        }
    
        // Find the quiz by ID
        $quiz = Quiz::findOrFail($quizId);
    
        
        $question = $quiz->questions()->create([
            'admin_id' => Auth::id(), 
            'question' => $request->input('question'),
            'quiz_id' => $quizId, 
        ]);
    
        
        foreach ($request->input('options') as $option) {
            $question->answers()->create([
                'admin_id' => Auth::id(),  
                'answer' => $option['answer'],
                'is_correct' => $option['is_correct'],
            ]);
        }
    
        // Return a success response
        return response()->json([
            'status' => 'success',
            'message' => 'Question and options added successfully',
            'data' => $question, // Optional: return the created question
        ]);
    }

    public function getQuestions($quizId)
    {
        // Validate quizId to ensure it's a number
        if (!is_numeric($quizId)) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Quiz ID must be a number'
            ], 400);
        }
    
        // Find the quiz
        $quiz = Quiz::with('questions.answers')->find($quizId);
        if (!$quiz) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Quiz not found'
            ], 404);
        }
    
        // Prepare the response data
        $data = [];
        foreach ($quiz->questions as $question) {
            $data[] = [
                'question' => $question->question,
                'options' => $question->answers->map(function ($answer) {
                    return [
                        'id' => $answer->id,  // Include the answer ID
                        'answer' => $answer->answer,
                        'is_correct' => $answer->is_correct,
                    ];
                }),
            ];
        }
    
        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }
    

    public function editQuestion(Request $request, $quizId, $questionId)
    {
        // Validate quizId and questionId to ensure they are numbers
        if (!is_numeric($quizId) || !is_numeric($questionId)) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Quiz ID and Question ID must be numbers'
            ], 400);
        }
    
        // Find the quiz
        $quiz = Quiz::find($quizId);
        if (!$quiz) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Quiz not found'
            ], 404);
        }
    
        // Find the question
        $question = $quiz->questions()->find($questionId);
        if (!$question) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Question not found'
            ], 404);
        }
    
        // Check if the authenticated admin is the one who created the question
        if ($question->admin_id !== Auth::id()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Unauthorized action'
            ], 403);
        }
    
        // Validate the incoming request data
        $request->validate([
            'question' => 'required|string',
            'options' => 'required|array',
            'options.*.answer' => 'required|string',
            'options.*.is_correct' => 'required|boolean',
            'options.*.id' => 'sometimes|nullable|integer',  // ID is optional for new options
        ]);
    
        // Update the question text
        $question->update([
            'question' => $request->input('question'),
        ]);
    
        // Loop through each option
        foreach ($request->input('options') as $option) {
            if (isset($option['id'])) {
                // Check if the answer exists and belongs to the same admin
                $answer = $question->answers()->where('id', $option['id'])->first();
                if (!$answer) {
                    return response()->json([
                        'status' => 'failed',
                        'message' => 'Answer ID not found or does not belong to the admin'
                    ], 404);
                }
                if ($answer->admin_id !== Auth::id()) {
                    return response()->json([
                        'status' => 'failed',
                        'message' => 'Unauthorized action on answer ID: ' . $option['id']
                    ], 403);
                }
    
                // Update existing option
                $answer->update([
                    'answer' => $option['answer'],
                    'is_correct' => $option['is_correct'],
                ]);
            } else {
                // Create new option
                $question->answers()->create([
                    'admin_id' => Auth::id(),  // Capture admin_id
                    'answer' => $option['answer'],
                    'is_correct' => $option['is_correct'],
                ]);
            }
        }
    
        return response()->json([
            'status' => 'success',
            'message' => 'Question and options updated successfully',
        ]);
    }
    
    public function deleteQuestion($quizId, $questionId)
    {
        // Validate quizId and questionId to ensure they are numbers
        if (!is_numeric($quizId) || !is_numeric($questionId)) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Quiz ID and Question ID must be numbers'
            ], 400);
        }

        // Find the quiz
        $quiz = Quiz::find($quizId);
        if (!$quiz) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Quiz not found'
            ], 404);
        }

        // Find the question
        $question = $quiz->questions()->find($questionId);
        if (!$question) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Question not found'
            ], 404);
        }

        // Check if the authenticated admin is the one who created the question
        if ($question->admin_id !== Auth::id()) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Unauthorized action'
            ], 403);
        }

        // Delete the question
        $question->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Question deleted successfully',
        ]);
    }


    
}
