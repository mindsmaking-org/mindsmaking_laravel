<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnsureTokenIsPublisher
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if the authenticated user is a publisher using the appropriate guard
        $publisher = Auth::guard('sanctum')->user();

        if ($publisher && $publisher instanceof \App\Models\Publisher) {
            // Allow the request to proceed if the authenticated user is a publisher
            return $next($request);
        }

        // If the user is not a publisher, return an unauthorized response
        return response()->json([
            'status' => 'failed',
            'message' => 'Unauthorized, only publishers can access this resource',
        ], 403); // 403 Forbidden
    }
}
