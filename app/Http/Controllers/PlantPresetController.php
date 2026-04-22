<?php

namespace App\Http\Controllers;

use App\Models\PlantPreset;
use Illuminate\Http\Request;

class PlantPresetController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:plant_presets,name',
            'min_ph' => 'required|numeric|min:0|max:14',
            'min_tds' => 'required|numeric|min:0',
            'max_turb' => 'required|numeric|min:0',
            'max_temp' => 'required|numeric|min:0|max:60',
        ]);

        PlantPreset::create($validated);

        return redirect()->back()->with('status', 'preset-created');
    }

    public function update(Request $request, PlantPreset $plantPreset)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:plant_presets,name,' . $plantPreset->id,
            'min_ph' => 'required|numeric|min:0|max:14',
            'min_tds' => 'required|numeric|min:0',
            'max_turb' => 'required|numeric|min:0',
            'max_temp' => 'required|numeric|min:0|max:60',
        ]);

        $plantPreset->update($validated);

        return redirect()->back()->with('status', 'preset-updated');
    }

    public function destroy(PlantPreset $plantPreset)
    {
        $plantPreset->delete();

        return redirect()->back()->with('status', 'preset-deleted');
    }
}
