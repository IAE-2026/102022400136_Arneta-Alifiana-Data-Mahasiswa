<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('X-API-KEY');

        if (!$apiKey || $apiKey !== config('app.api_key')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Invalid or missing API Key.',
                'data' => null,
            ], 401);
        }

        return $next($request);
    }
}