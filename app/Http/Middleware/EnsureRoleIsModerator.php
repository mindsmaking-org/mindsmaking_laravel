<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoleIsModerator
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        
        $roles = explode(',', auth()->user()->roles);

        if (!in_array('moderator', $roles)) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized: You do not have the moderator role'
            ], 403); 
        }

       
        return $next($request);
    }
}
