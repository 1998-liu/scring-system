<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ScoringRuleWeight extends Model
{
    protected $fillable = ['scoring_rule_id', 'evaluator_role_id', 'weight'];

    public function scoringRule()
    {
        return $this->belongsTo(ScoringRule::class);
    }

    public function evaluatorRole()
    {
        return $this->belongsTo(Role::class, 'evaluator_role_id');
    }
}
