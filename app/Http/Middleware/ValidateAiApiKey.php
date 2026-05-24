<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateAiApiKey
{
    /**
     * Validate that the incoming request has a valid AI Server API key.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $expectedKey = config('services.ai_server.api_key');

        if (empty($expectedKey)) {
            return response()->json(['error' => 'AI API key not configured on server.'], 500);
        }

        $providedKey = $request->header('X-API-Key');

        if (empty($providedKey) || $providedKey !== $expectedKey) {
            return response()->json(['error' => 'Unauthorized. Invalid API key.'], 401);
        }

        return $next($request);
    }
}
