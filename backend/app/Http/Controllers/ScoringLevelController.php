<?php

namespace App\Http\Controllers;

use App\ScoringLevel;
use Illuminate\Http\Request;

class ScoringLevelController extends Controller
{
    public function index()
    {
        $levels = ScoringLevel::orderBy('sort_order')->get();
        return response()->json($levels);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:scoring_levels,code',
            'min_score' => 'required|numeric|min:0|max:100',
            'max_score' => 'required|numeric|min:0|max:100',
            'color' => 'nullable|string',
            'sort_order' => 'nullable|integer'
        ]);

        $level = ScoringLevel::create([
            'name' => $request->name,
            'code' => $request->code,
            'min_score' => $request->min_score,
            'max_score' => $request->max_score,
            'color' => $request->color ?? '#409EFF',
            'sort_order' => $request->sort_order ?? 0,
            'is_active' => true
        ]);

        return response()->json($level, 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:scoring_levels,code,' . $id,
            'min_score' => 'required|numeric|min:0|max:100',
            'max_score' => 'required|numeric|min:0|max:100',
            'color' => 'nullable|string',
            'sort_order' => 'nullable|integer'
        ]);

        $level = ScoringLevel::findOrFail($id);
        $level->update([
            'name' => $request->name,
            'code' => $request->code,
            'min_score' => $request->min_score,
            'max_score' => $request->max_score,
            'color' => $request->color ?? '#409EFF',
            'sort_order' => $request->sort_order ?? 0
        ]);

        return response()->json($level);
    }

    public function destroy($id)
    {
        $level = ScoringLevel::findOrFail($id);
        $level->update(['is_active' => false]);
        return response()->json(null, 204);
    }

    public function getLevelByScore($score)
    {
        $level = ScoringLevel::getLevelByScore($score);
        return response()->json($level);
    }
}
