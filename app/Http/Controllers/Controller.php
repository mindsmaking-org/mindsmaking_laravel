<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Helper\ResponseHelper;

abstract class Controller
{
    use AuthorizesRequests, ValidatesRequests;

    protected function sendResponse($status, $message, $data = [], $statusCode = 200)
    {
        return ResponseHelper::sendResponse($status, $message, $data, $statusCode);
    }
}
