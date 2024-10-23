<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Category\CategoryController;
use App\Http\Controllers\Api\Category\SubcategoryController;
use App\Http\Controllers\Api\Category\ChildSubcategoryController;
use App\Http\Controllers\Api\Post\PostController;
use App\Http\Controllers\Api\Functions\CommentController;
use App\Http\Controllers\Api\functions\GroupController;
use App\Http\Controllers\Api\Tools\BabyNameController;
use App\Http\Controllers\Api\Tools\PregnancyWeekController;
use App\Http\Controllers\Api\Tools\GestationalController;
use App\Http\Controllers\Api\Tools\WeeklyWeightGainController;
use App\Http\Controllers\Api\Tools\DueDateCalculatorController;
use App\Http\Controllers\Api\Tools\OvulationController;
use App\Http\Controllers\Api\Tools\QuizController;
use App\Http\Controllers\Api\SuperAdmin\SUperAdminController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::controller(AuthController::class)->group(function(){
    Route::post('user/register', 'register');
    Route::post('user/login', 'login');


    Route::get('logout', 'logout')->middleware('auth:sanctum');


    Route::post('admin/register', 'adminRegister');
    Route::post('admin/login', 'adminLogin');

});


Route::prefix('editor')->controller(CategoryController::class)->group(function(){
    Route::middleware(['auth:sanctum', 'admin', 'editor'])->group(function(){
        Route::post('add-categories', 'store');
        Route::put('edit-category', 'editCategory');
        Route::delete('delete-category', 'deleteCategory');
    });
    Route::get('categories', 'getAllCategories');
    Route::get('category-info', 'getACategoryInfo');
});

Route::prefix('editor')->controller(SubcategoryController::class)->group(function(){
    Route::middleware(['auth:sanctum', 'admin', 'editor'])->group(function(){
        Route::post('add-subcategories', 'store');
        Route::put('edit-subcategory', 'editSubcategory');
        Route::delete('delete-subcategory', 'deleteSubcategory');
    });
    Route::get('subcategories', 'getAllSubcategories');
    Route::get('subcategory-info', 'getASubcategoryInfo');
});

Route::prefix('editor')->controller(ChildSubcategoryController::class)->group(function(){
    Route::middleware(['auth:sanctum', 'admin', 'editor'])->group(function(){
        Route::post('add-child-subcategories', 'store');
        Route::put('edit-child-subcategory', 'editChildSubcategory');
        Route::delete('delete-child-subcategories', 'deleteChildSubcategory');
    });
    // Route::get('admin/child-subcategories', 'getAllSubcategories');
    // Route::get('admin/child-subcategories/{childSubcategory}', 'show');
});

Route::prefix('publisher')->controller(PostController::class)->group(function(){
    Route::middleware(['auth:sanctum', 'admin', 'publisher'])->group(function(){
        Route::post('add-posts', 'store');
        Route::put('edit-post/{id}', 'updatePost');
        Route::delete('delete-post/{id}', 'deletePost');
        Route::get('/posts/{post}/views', 'getPostViews');
        Route::get('posts/{post}/comments-count', 'getPostCommentsCount');
    });
});

Route::controller(PostController::class)->group(function(){
    Route::get('/posts', 'getAllPost');
    Route::get('posts/{post}/increment-views', 'incrementViews');
});

Route::get('images/{filename}', function ($filename) {
    $path = storage_path('app/public/images/' . $filename);

    if (!File::exists($path)) {
        abort(404);
    }

    $file = File::get($path);
    $type = File::mimeType($path);

    $response = response($file, 200);
    $response->header("Content-Type", $type);

    return $response;
});

Route::prefix('posts')->controller(CommentController::class)->group(function(){
    Route::get('{post}/comments', 'getPostComment')->middleware('auth:sanctum'); 
    Route::post('{post}/comments', 'storeComment')->middleware(['auth:sanctum', 'user']); 
    Route::get('{post}/comments/{comment}', 'showComment')->middleware('auth:sanctum'); 
    Route::put('{post}/comments/{comment}', 'updateComment')->middleware(['auth:sanctum', 'user']); 
    Route::delete('{post}/comments/{comment}', 'destroyComment')->middleware('auth:sanctum'); 
});

Route::controller(CommentController::class)->group(function(){
    Route::delete('moderator/posts/{post}/comments/{comment}', 'destroyCommentByAdmin')->middleware(['auth:sanctum', 'admin', 'moderator']); 
});


Route::controller(GroupController::class)->group(function(){
    Route::prefix('moderator')->middleware(['auth:sanctum', 'admin', 'moderator'])->group(function(){
        Route::post('groups', 'createGroup');
        Route::delete('groups/{groupId}', 'deleteGroup');
        Route::delete('groups/{messageId}/messages', 'adminDeleteMessage');
    });
    
    Route::get('/groups', 'viewAllGroups')->middleware(['auth:sanctum', 'user']);
    Route::get('/groups/{groupId}/join', 'joinGroup')->middleware(['auth:sanctum', 'user']);
    Route::post('/groups/{groupId}/messages', 'sendMessage')->middleware(['auth:sanctum', 'user']);
    Route::get('/groups/{groupId}/messages', 'viewMessages')->middleware('auth:sanctum');
    Route::put('/groups/{messageId}/messages', 'editMessage')->middleware('auth:sanctum', 'user');
    Route::delete('/groups/{messageId}/messages', 'deleteMessage')->middleware(['auth:sanctum', 'user']);
});


Route::controller(BabyNameController::class)->group(function(){
    Route::post('admin/baby-names', 'store')->middleware(['auth:sanctum', 'admin']);
    Route::put('admin/baby-names/{babyName}', 'update')->middleware(['auth:sanctum', 'admin']);
    Route::delete('admin/baby-names/{babyName}', 'destroy')->middleware(['auth:sanctum', 'admin']);



    Route::get('users/baby-names/categories', 'categories');
    Route::post('users/baby-names', 'index');
    Route::get('users/baby-names/{identifier}', 'show');
});


Route::post('users/pregnancy-week', [PregnancyWeekController::class, 'calculate']);


Route::post('users/gestational-diabetes-diet', [GestationalController::class, 'calculateDiet']);


Route::post('users/weekly-pregnancy-weight-gain', [WeeklyWeightGainController::class, 'calculateWeightGain']);


Route::post('users/due-date-calculator', [DueDateCalculatorController::class, 'calculateDueDate']); 

Route::post('users/ovulation-calculator', [OvulationController::class, 'calculate']);



Route::controller(QuizController::class)->group(function(){
    Route::post('/admin/quizzes', 'store')->middleware('auth:sanctum');
    Route::post('/admin/quizzes/{quiz}/questions', 'addQuestion')->middleware('auth:sanctum');
    Route::put('/admin/quizzes/{quiz}/questions/{questionId}', 'editQuestion')->middleware('auth:sanctum');
    Route::delete('/admin/quizzes/{quiz}/questions/{questionId}', 'deleteQuestion')->middleware('auth:sanctum');
    
});

Route::get('/admin/quizzes/{quiz}/questions', [QuizController::class, 'getQuestions']);


Route::prefix('superAdmin')->controller(SUperAdminController::class)->group(function(){
    Route::middleware(['auth:sanctum', 'superAdmin'])->group(function(){
        Route::get('get-admins', 'getAllAdmins');
        Route::put('update-roles/{adminId}', 'updateAdminRoles');
    });
});


