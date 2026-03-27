<?php

namespace App\Http\Controllers;

use App\ScoringRule;
use App\ScoringRuleWeight;
use App\ScoringRuleHistory;
use App\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScoringRuleController extends Controller
{
    public function index()
    {
        $rules = ScoringRule::with(['weights.evaluatorRole', 'targetRole'])->where('is_active', true)->get();
        
        $rules->each(function($rule) {
            $rule->weights->each(function($weight) {
                $weight->evaluator_role_name = $weight->evaluatorRole ? $weight->evaluatorRole->name : null;
            });
            $rule->target_role_name = $rule->targetRole ? $rule->targetRole->name : null;
        });
        
        return response()->json($rules);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'target_role_id' => 'required|exists:roles,id',
            'description' => 'nullable|string',
            'weights' => 'required|array',
            'weights.*.evaluator_role_id' => 'required|exists:roles,id',
            'weights.*.weight' => 'required|numeric|min:0|max:100'
        ]);

        $totalWeight = collect($request->weights)->sum('weight');
        if (abs($totalWeight - 100) > 0.01) {
            return response()->json(['error' => '权重总和必须等于100%'], 400);
        }

        $rule = ScoringRule::create([
            'name' => $request->name,
            'target_role_id' => $request->target_role_id,
            'description' => $request->description,
            'is_active' => true
        ]);

        foreach ($request->weights as $weightData) {
            ScoringRuleWeight::create([
                'scoring_rule_id' => $rule->id,
                'evaluator_role_id' => $weightData['evaluator_role_id'],
                'weight' => $weightData['weight']
            ]);
        }

        $rule->saveHistory(Auth::id(), '创建评分规则');

        $rule->load(['weights.evaluatorRole', 'targetRole']);
        $rule->weights->each(function($weight) {
            $weight->evaluator_role_name = $weight->evaluatorRole ? $weight->evaluatorRole->name : null;
        });
        $rule->target_role_name = $rule->targetRole ? $rule->targetRole->name : null;

        return response()->json($rule, 201);
    }

    public function show($id)
    {
        $rule = ScoringRule::with(['weights.evaluatorRole', 'targetRole'])->findOrFail($id);
        
        $rule->weights->each(function($weight) {
            $weight->evaluator_role_name = $weight->evaluatorRole ? $weight->evaluatorRole->name : null;
        });
        $rule->target_role_name = $rule->targetRole ? $rule->targetRole->name : null;
        
        return response()->json($rule);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'target_role_id' => 'required|exists:roles,id',
            'description' => 'nullable|string',
            'weights' => 'required|array',
            'weights.*.evaluator_role_id' => 'required|exists:roles,id',
            'weights.*.weight' => 'required|numeric|min:0|max:100',
            'change_reason' => 'nullable|string'
        ]);

        $totalWeight = collect($request->weights)->sum('weight');
        if (abs($totalWeight - 100) > 0.01) {
            return response()->json(['error' => '权重总和必须等于100%'], 400);
        }

        $rule = ScoringRule::findOrFail($id);
        
        $rule->update([
            'name' => $request->name,
            'target_role_id' => $request->target_role_id,
            'description' => $request->description
        ]);

        $rule->weights()->delete();
        foreach ($request->weights as $weightData) {
            ScoringRuleWeight::create([
                'scoring_rule_id' => $rule->id,
                'evaluator_role_id' => $weightData['evaluator_role_id'],
                'weight' => $weightData['weight']
            ]);
        }

        $rule->saveHistory(Auth::id(), $request->change_reason ?? '更新评分规则');

        $rule->load(['weights.evaluatorRole', 'targetRole']);
        $rule->weights->each(function($weight) {
            $weight->evaluator_role_name = $weight->evaluatorRole ? $weight->evaluatorRole->name : null;
        });
        $rule->target_role_name = $rule->targetRole ? $rule->targetRole->name : null;

        return response()->json($rule);
    }

    public function destroy($id)
    {
        $rule = ScoringRule::findOrFail($id);
        $rule->update(['is_active' => false]);
        return response()->json(null, 204);
    }

    public function history($id)
    {
        $rule = ScoringRule::findOrFail($id);
        $histories = $rule->histories()->with('changedBy')->orderBy('created_at', 'desc')->get();
        return response()->json($histories);
    }

    public function getByRoleId($roleId)
    {
        $rule = ScoringRule::with(['weights.evaluatorRole', 'targetRole'])
            ->where('target_role_id', $roleId)
            ->where('is_active', true)
            ->first();
        
        if (!$rule) {
            return response()->json(['error' => '未找到该角色的评分规则'], 404);
        }
        
        $rule->weights->each(function($weight) {
            $weight->evaluator_role_name = $weight->evaluatorRole ? $weight->evaluatorRole->name : null;
        });
        $rule->target_role_name = $rule->targetRole ? $rule->targetRole->name : null;
        
        return response()->json($rule);
    }

    public function calculateScore(Request $request)
    {
        $request->validate([
            'target_role_id' => 'required|exists:roles,id',
            'scores' => 'required|array',
            'scores.*.evaluator_role_id' => 'required|exists:roles,id',
            'scores.*.average_score' => 'required|numeric|min:0|max:100'
        ]);

        $rule = ScoringRule::with('weights')
            ->where('target_role_id', $request->target_role_id)
            ->where('is_active', true)
            ->first();

        if (!$rule) {
            return response()->json(['error' => '未找到该角色的评分规则'], 404);
        }

        $scoresByRoleId = [];
        foreach ($request->scores as $scoreData) {
            $scoresByRoleId[$scoreData['evaluator_role_id']] = $scoreData['average_score'];
        }

        $totalScore = $rule->calculateScore($scoresByRoleId);

        return response()->json([
            'total_score' => $totalScore,
            'rule' => $rule,
            'scores_by_role_id' => $scoresByRoleId
        ]);
    }

    public function getRoles()
    {
        $roles = Role::orderBy('name')->get(['id', 'name', 'description']);
        return response()->json($roles);
    }
}
