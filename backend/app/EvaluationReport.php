<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EvaluationReport extends Model
{
    protected $fillable = [
        'evaluatee_id',
        'plan_id',
        'total_score',
        'scores',
        'dimension_scores',
        'evaluator_scores',
        'level',
        'formula_snapshot',
        'strengths',
        'improvements'
    ];

    protected $casts = [
        'scores' => 'array',
        'dimension_scores' => 'array',
        'evaluator_scores' => 'array',
        'formula_snapshot' => 'array'
    ];

    public function evaluatee()
    {
        return $this->belongsTo(User::class, 'evaluatee_id');
    }

    public function plan()
    {
        return $this->belongsTo(EvaluationPlan::class, 'plan_id');
    }
}
