<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ScoringRuleHistory extends Model
{
    protected $fillable = [
        'scoring_rule_id',
        'name',
        'target_role_id',
        'target_role_name',
        'description',
        'weights',
        'changed_by',
        'change_reason'
    ];

    protected $casts = [
        'weights' => 'array'
    ];

    public function scoringRule()
    {
        return $this->belongsTo(ScoringRule::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
