<?php

namespace App\Http\Controllers;

use App\Models\PlantPreset;
use Illuminate\Http\Request;

class PlantPresetController extends Controller
{
    /**
     * Validate rule structure.
     */
    private function ruleValidation(): array
    {
        return [
            'rules'              => 'nullable|array',
            'rules.*.sensor'     => 'required|string|in:ph,tds,turbidity,water_temp,air_temp,humidity,light',
            'rules.*.condition'  => 'required|string|in:<,>',
            'rules.*.value'      => 'required|numeric',
            'rules.*.pump'       => 'required|integer|min:1|max:4',
            'rules.*.pulse'      => 'required|integer|min:1|max:60',
            'rules.*.stabilize'  => 'required|integer|min:1|max:120',
            'rules.*.max_pulses' => 'required|integer|min:1|max:100',
            'rules.*.cooldown'   => 'required|integer|min:0|max:3600',
        ];
    }

    public function store(Request $request)
    {
        if (is_string($request->rules)) {
            $request->merge(['rules' => json_decode($request->rules, true)]);
        }

        $validated = $request->validate(array_merge([
            'name'       => 'required|string|max:255|unique:plant_presets,name',
        ], $this->ruleValidation()));

        PlantPreset::create([
            'name'       => $validated['name'],
            'rules'      => $validated['rules'] ?? [],
        ]);

        return redirect()->back()->with('status', 'preset-created');
    }

    public function update(Request $request, PlantPreset $plantPreset)
    {
        if (is_string($request->rules)) {
            $request->merge(['rules' => json_decode($request->rules, true)]);
        }

        $validated = $request->validate(array_merge([
            'name'       => 'required|string|max:255|unique:plant_presets,name,' . $plantPreset->id,
        ], $this->ruleValidation()));

        $plantPreset->update([
            'name'       => $validated['name'],
            'rules'      => $validated['rules'] ?? [],
        ]);

        return redirect()->back()->with('status', 'preset-updated');
    }

    public function destroy(PlantPreset $plantPreset)
    {
        $plantPreset->delete();

        return redirect()->back()->with('status', 'preset-deleted');
    }
}
