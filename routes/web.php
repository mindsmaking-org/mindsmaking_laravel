<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;


Route::get('/', function () {
    return view('welcome');
});

Route::get('images/{path}/{filename}', function ($path, $filename) {
    $fullPath = storage_path("app/public/images/$path/$filename");

    if (!File::exists($fullPath)) {
        abort(404);
    }

    $file = File::get($fullPath);
    $type = File::mimeType($fullPath);

    return response($file, 200)->header("Content-Type", $type);
}); 

