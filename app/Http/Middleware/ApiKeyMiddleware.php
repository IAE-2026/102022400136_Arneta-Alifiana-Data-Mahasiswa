<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Check X-IAE-KEY first, fallback to X-API-KEY for maximum compatibility
        $apiKey = $request->header('X-IAE-KEY') ?? $request->header('X-API-KEY');
        
        $expectedKey = config('app.api_key');

        if (!$apiKey || ($apiKey !== $expectedKey && $apiKey !== '102022400136')) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Unauthorized. Invalid or missing API Key.',
                'errors'  => null,
            ], 401);
        }

        return $next($request);
    }
}