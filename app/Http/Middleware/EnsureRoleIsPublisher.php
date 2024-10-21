<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoleIsPublisher
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        
        $roles = explode(',', auth()->user()->roles);

        if (!in_array('publisher', $roles)) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized: You do not have the publisher role'
            ], 403); 
        }

       
        return $next($request);
    }
}
