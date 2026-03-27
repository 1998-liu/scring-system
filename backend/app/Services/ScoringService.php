<?php

namespace App\Services;

use App\ScoringRule;
use App\ScoringLevel;
use App\EvaluationAnswer;
use App\EvaluationReport;
use App\EvaluationQuestion;
use App\User;
use App\Role;
use Illuminate\Support\Facades\DB;

class ScoringService
{
    public function calculateDimensionScore($evaluateeId, $planId, $dimensionId)
    {
        $questions = EvaluationQuestion::where('dimension_id', $dimensionId)->get();
        
        $totalWeightedScore = 0;
        $totalWeight = 0;
        
        foreach ($questions as $question) {
            $answers = EvaluationAnswer::where('evaluatee_id', $evaluateeId)
                ->where('question_id', $question->id)
                ->whereHas('task', function($query) use ($planId) {
                    $query->where('plan_id', $planId);
                })
                ->get();
            
            if ($answers->count() > 0) {
                $averageScore = $answers->avg('score');
                $weightedScore = $averageScore * ($question->weight / 100);
                $totalWeightedScore += $weightedScore;
                $totalWeight += $question->weight;
            }
        }
        
        return $totalWeight > 0 ? ($totalWeightedScore / $totalWeight) * 100 : 0;
    }

    public function calculateScoreByEvaluatorRoleId($evaluateeId, $planId)
    {
        $answers = EvaluationAnswer::where('evaluatee_id', $evaluateeId)
            ->whereHas('task', function($query) use ($planId) {
                $query->where('plan_id', $planId);
            })
            ->with(['evaluator', 'evaluator.roles'])
            ->get();
        
        $scoresByRoleId = [];
        $roleNames = [];
        
        foreach ($answers as $answer) {
            $evaluator = $answer->evaluator;
            if ($evaluator && $evaluator->roles->isNotEmpty()) {
                $role = $evaluator->roles->first();
                $roleId = $role->id;
                $roleName = $role->name;
                
                if (!isset($scoresByRoleId[$roleId])) {
                    $scoresByRoleId[$roleId] = [];
                    $roleNames[$roleId] = $roleName;
                }
                $scoresByRoleId[$roleId][] = $answer->score;
            }
        }
        
        $averageScoresByRoleId = [];
        foreach ($scoresByRoleId as $roleId => $scores) {
            $averageScoresByRoleId[$roleId] = [
                'average_score' => array_sum($scores) / count($scores),
                'role_name' => $roleNames[$roleId],
                'count' => count($scores)
            ];
        }
        
        return $averageScoresByRoleId;
    }

    public function getEvaluateeRoleId($evaluateeId)
    {
        $evaluatee = User::find($evaluateeId);
        if (!$evaluatee || $evaluatee->roles->isEmpty()) {
            return null;
        }
        
        return $evaluatee->roles->first()->id;
    }

    public function calculateTotalScore($evaluateeId, $planId)
    {
        $roleId = $this->getEvaluateeRoleId($evaluateeId);
        
        if (!$roleId) {
            return [
                'total_score' => 0,
                'error' => '被评估者没有分配角色'
            ];
        }
        
        $rule = ScoringRule::with(['weights.evaluatorRole', 'targetRole'])
            ->where('target_role_id', $roleId)
            ->where('is_active', true)
            ->first();
        
        if (!$rule) {
            return [
                'total_score' => 0,
                'error' => '未找到该角色的评分规则'
            ];
        }
        
        $scoresByRoleId = $this->calculateScoreByEvaluatorRoleId($evaluateeId, $planId);
        
        $scoresForCalculation = [];
        foreach ($scoresByRoleId as $roleId => $data) {
            $scoresForCalculation[$roleId] = $data['average_score'];
        }
        
        $totalScore = $rule->calculateScore($scoresForCalculation);
        
        $level = ScoringLevel::getLevelByScore($totalScore);
        
        return [
            'total_score' => $totalScore,
            'level' => $level ? $level->name : null,
            'level_color' => $level ? $level->color : null,
            'scores_by_role' => $scoresByRoleId,
            'rule' => $rule,
            'rule_name' => $rule->name
        ];
    }

    public function generateReport($evaluateeId, $planId)
    {
        $roleId = $this->getEvaluateeRoleId($evaluateeId);
        $evaluatee = User::find($evaluateeId);
        
        $rule = null;
        if ($roleId) {
            $rule = ScoringRule::with(['weights.evaluatorRole', 'targetRole'])
                ->where('target_role_id', $roleId)
                ->where('is_active', true)
                ->first();
        }
        
        $scoresByRoleId = $this->calculateScoreByEvaluatorRoleId($evaluateeId, $planId);
        $totalScoreResult = $this->calculateTotalScore($evaluateeId, $planId);
        
        $targetRoleName = $evaluatee && $evaluatee->roles->isNotEmpty() 
            ? $evaluatee->roles->first()->name 
            : '未分配角色';
        
        $dimensions = \App\EvaluationDimension::where('target_role_id', $roleId)
            ->orWhereNull('target_role_id')
            ->get();
        
        $dimensionScores = [];
        foreach ($dimensions as $dimension) {
            $dimensionScores[$dimension->name] = [
                'score' => $this->calculateDimensionScore($evaluateeId, $planId, $dimension->id),
                'weight' => $dimension->weight
            ];
        }
        
        $formulaSnapshot = null;
        if ($rule) {
            $formulaSnapshot = [
                'rule_id' => $rule->id,
                'rule_name' => $rule->name,
                'target_role_name' => $rule->targetRole ? $rule->targetRole->name : null,
                'weights' => $rule->weights->map(function($w) {
                    return [
                        'evaluator_role_id' => $w->evaluator_role_id,
                        'evaluator_role_name' => $w->evaluatorRole ? $w->evaluatorRole->name : null,
                        'weight' => $w->weight
                    ];
                })->toArray(),
                'calculated_at' => now()->toDateTimeString()
            ];
        }
        
        $evaluatorScores = [];
        foreach ($scoresByRoleId as $roleId => $data) {
            $evaluatorScores[$data['role_name']] = $data['average_score'];
        }
        
        $report = EvaluationReport::updateOrCreate(
            ['evaluatee_id' => $evaluateeId, 'plan_id' => $planId],
            [
                'total_score' => $totalScoreResult['total_score'],
                'scores' => $evaluatorScores,
                'dimension_scores' => $dimensionScores,
                'evaluator_scores' => $evaluatorScores,
                'level' => $totalScoreResult['level'],
                'formula_snapshot' => $formulaSnapshot,
                'strengths' => null,
                'improvements' => null
            ]
        );
        
        return $report;
    }

    public function batchGenerateReports($planId)
    {
        $tasks = \App\EvaluationTask::where('plan_id', $planId)
            ->where('status', 'completed')
            ->get();
        
        $evaluateeIds = [];
        foreach ($tasks as $task) {
            foreach ($task->evaluatees as $evaluatee) {
                $evaluateeIds[] = $evaluatee->id;
            }
        }
        
        $evaluateeIds = array_unique($evaluateeIds);
        
        $reports = [];
        foreach ($evaluateeIds as $evaluateeId) {
            $reports[] = $this->generateReport($evaluateeId, $planId);
        }
        
        return $reports;
    }
}
