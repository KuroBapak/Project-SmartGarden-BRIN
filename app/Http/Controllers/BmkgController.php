<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BmkgController extends Controller
{
    /**
     * Proxy BMKG weather forecast API with 30-minute caching.
     * Returns the JSON as-is for the frontend to consume.
     */
    public function forecast(): JsonResponse
    {
        $adm4 = config('services.bmkg.adm4', '32.16.09.2001');

        // Cache for 30 minutes to respect BMKG rate limits (60 req/min/IP)
        $data = Cache::remember("bmkg_forecast_{$adm4}", 1800, function () use ($adm4) {
            try {
                $response = Http::timeout(15)
                    ->withoutVerifying()
                    ->get("https://api.bmkg.go.id/publik/prakiraan-cuaca", [
                        'adm4' => $adm4,
                    ]);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::warning("BMKG API returned non-200 status: " . $response->status());
                return null;
            } catch (\Exception $e) {
                Log::error("BMKG API Error: " . $e->getMessage());
                return null;
            }
        });

        if ($data === null) {
            return response()->json([
                'error' => 'Unable to fetch weather data from BMKG',
            ], 503);
        }

        return response()->json($data);
    }
}
