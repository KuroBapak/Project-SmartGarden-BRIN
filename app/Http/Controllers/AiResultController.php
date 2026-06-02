<?php

namespace App\Http\Controllers;

use App\Models\EnergyAnalysis;
use App\Models\PlantScan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AiResultController extends Controller
{
    // ──────────────────────────────────────────────────────
    // AI Server → Dashboard (Receive & Store Results)
    // ──────────────────────────────────────────────────────

    /**
     * Receive energy analysis results from AI Server.
     * POST /api/ai/energy-analysis
     */
    public function storeEnergyAnalysis(Request $request): JsonResponse
    {
        $data = $request->validate([
            'analysis_text'     => 'required|string',
            'status'            => 'required|string|in:normal,hoarding,emergency',
            'model'             => 'nullable|string',
            'risk_score'        => 'nullable|numeric',
            'net_power'         => 'nullable|numeric',
            'solar_power'       => 'nullable|numeric',
            'load_power'        => 'nullable|numeric',
            'battery_pct'       => 'nullable|numeric',
            'endurance_hours'   => 'nullable|numeric',
            'solar_forecast'    => 'nullable|numeric',
            'can_survive_night' => 'nullable|boolean',
            'time_to_full'      => 'nullable|numeric',
            'time_to_empty'     => 'nullable|numeric',
            'raw_data'          => 'nullable|array',
        ]);

        // Synchronous cleanup: delete all previous records so we only keep the latest one
        EnergyAnalysis::truncate();

        $record = EnergyAnalysis::create($data);

        return response()->json([
            'message' => 'Energy analysis stored.',
            'id'      => $record->id,
        ], 201);
    }

    /**
     * Receive plant scan results from AI Server.
     * POST /api/ai/plant-scan
     */
    public function storePlantScan(Request $request): JsonResponse
    {
        $data = $request->validate([
            'status'           => 'required|string|in:healthy,warning,critical,mild',
            'status_label'     => 'required|string',
            'status_emoji'     => 'required|string',
            'message'          => 'nullable|string',
            'detections'       => 'nullable',
            'total_detections' => 'required|integer|min:0',
            'scan_source'      => 'nullable|string|in:auto,manual,raspi_upload',
            'image'            => 'nullable|file|image|max:5120',          // Annotated image
            'image_original'   => 'nullable|file|image|max:5120',         // Original image
        ]);

        // Synchronous cleanup: delete all previous records and their images
        $oldScans = PlantScan::all();
        foreach ($oldScans as $oldScan) {
            if ($oldScan->image_path && Storage::disk('public')->exists($oldScan->image_path)) {
                Storage::disk('public')->delete($oldScan->image_path);
            }
            if ($oldScan->image_original_path && Storage::disk('public')->exists($oldScan->image_original_path)) {
                Storage::disk('public')->delete($oldScan->image_original_path);
            }
            $oldScan->delete();
        }

        $record = new PlantScan();
        $record->status           = $data['status'];
        $record->status_label     = $data['status_label'];
        $record->status_emoji     = $data['status_emoji'];
        $record->message          = $data['message'] ?? null;
        
        $detections = $data['detections'] ?? [];
        if (is_string($detections)) {
            $detections = json_decode($detections, true) ?? [];
        }
        $record->detections       = $detections;
        
        $record->total_detections = $data['total_detections'];
        $record->scan_source      = $data['scan_source'] ?? 'auto';

        // Store uploaded images
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('plant_scans', 'public');
            $record->image_path = $path;
        }

        if ($request->hasFile('image_original')) {
            $path = $request->file('image_original')->store('plant_scans', 'public');
            $record->image_original_path = $path;
        }

        $record->save();

        return response()->json([
            'message' => 'Plant scan stored.',
            'id'      => $record->id,
        ], 201);
    }

    // ──────────────────────────────────────────────────────
    // Dashboard → Frontend (Serve Pre-Computed Results)
    // ──────────────────────────────────────────────────────

    /**
     * Get latest energy analysis for frontend display.
     * GET /api/ai/energy-analysis/latest
     */
    public function latestEnergyAnalysis(): JsonResponse
    {
        $latest = EnergyAnalysis::latest()->first();

        if (!$latest) {
            return response()->json([
                'status'  => null,
                'message' => 'Belum ada data analisis energi dari AI Server.',
            ]);
        }

        return response()->json([
            'id'                => $latest->id,
            'analysis_text'     => $latest->analysis_text,
            'status'            => $latest->status,
            'model'             => $latest->model,
            'risk_score'        => $latest->risk_score,
            'net_power'         => $latest->net_power,
            'solar_power'       => $latest->solar_power,
            'load_power'        => $latest->load_power,
            'battery_pct'       => $latest->battery_pct,
            'endurance_hours'   => $latest->endurance_hours,
            'solar_forecast'    => $latest->solar_forecast,
            'can_survive_night' => $latest->can_survive_night,
            'time_to_full'      => $latest->time_to_full,
            'time_to_empty'     => $latest->time_to_empty,
            'updated_at'        => $latest->updated_at->toIso8601String(),
        ]);
    }

    /**
     * Get latest plant scan for frontend display.
     * GET /api/ai/plant-scan/latest
     */
    public function latestPlantScan(): JsonResponse
    {
        $latest = PlantScan::latest()->first();

        if (!$latest) {
            return response()->json([
                'status' => null,
                'error'  => 'Belum ada data scan dari AI Server.',
            ]);
        }

        return response()->json([
            'id'                  => $latest->id,
            'status'              => [
                'status'       => $latest->status,
                'status_label' => $latest->status_label,
                'status_emoji' => $latest->status_emoji,
                'message'      => $latest->message,
                'diseases'     => collect($latest->detections ?? [])
                    ->filter(fn($d) => !str_contains(strtolower($d['class'] ?? $d['name'] ?? ''), 'healthy'))
                    ->map(fn($d) => [
                        'name'     => $d['name'] ?? $d['class'] ?? 'Unknown',
                        'accuracy' => $d['accuracy'] ?? $d['confidence'] ?? 0,
                    ])
                    ->values()
                    ->all(),
            ],
            'total_detections'    => $latest->total_detections,
            'timestamp'           => $latest->created_at->toIso8601String(),
            'timestamp_formatted' => $latest->created_at->format('d M Y, H:i:s'),
            'image'               => $latest->image_path
                ? '/storage/' . $latest->image_path
                : null,
            'image_original'      => $latest->image_original_path
                ? '/storage/' . $latest->image_original_path
                : null,
            'scan_source'         => $latest->scan_source,
        ]);
    }

}
