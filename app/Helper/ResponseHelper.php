<?php

namespace App\Helper;

class ResponseHelper
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * @param string $status
     * @param string $message
     * @param array $data
     * @param integer $statusCode
     * @return response
    */

    public static function sendResponse($status, $message, $data = [], $statusCode = 200)
    {
        return response()->json([
            'status' => $status,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }
}
