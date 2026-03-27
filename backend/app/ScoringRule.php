<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ScoringRule extends Model
{
    protected $fillable = ['name', 'target_role_id', 'description', 'is_active'];

    public function targetRole()
    {
        return $this->belongsTo(Role::class, 'target_role_id');
    }

    public function weights()
    {
        return $this->hasMany(ScoringRuleWeight::class);
    }

    public function histories()
    {
        return $this->hasMany(ScoringRuleHistory::class);
    }

    public function getWeightByRoleId($roleId)
    {
        $weightRecord = $this->weights()->where('evaluator_role_id', $roleId)->first();
        return $weightRecord ? $weightRecord->weight : 0;
    }

    public function calculateScore($scoresByRoleId)
    {
        $totalScore = 0;
        foreach ($scoresByRoleId as $roleId => $score) {
            $weight = $this->getWeightByRoleId($roleId);
            $totalScore += $score * ($weight / 100);
        }
        return $totalScore;
    }

    public function saveHistory($userId, $reason = null)
    {
        $weightsData = $this->weights->map(function($w) {
            return [
                'evaluator_role_id' => $w->evaluator_role_id,
                'evaluator_role_name' => $w->evaluatorRole ? $w->evaluatorRole->name : null,
                'weight' => $w->weight
            ];
        })->toArray();

        return $this->histories()->create([
            'name' => $this->name,
            'target_role_id' => $this->target_role_id,
            'target_role_name' => $this->targetRole ? $this->targetRole->name : null,
            'description' => $this->description,
            'weights' => $weightsData,
            'changed_by' => $userId,
            'change_reason' => $reason
        ]);
    }
}
