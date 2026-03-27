<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class EvaluationQuestion extends Model
{
    protected $fillable = [
        'dimension_id',
        'content',
        'type',
        'scoring_criteria',
        'weight',
        'max_score',
        'target_role',
        'min_score',
        'options',
    ];

    protected $casts = [
        'options'   => 'array',
        'min_score' => 'decimal:2',
        'max_score' => 'decimal:2',
    ];

    public function dimension()
    {
        return $this->belongsTo(EvaluationDimension::class, 'dimension_id');
    }

    public function answers()
    {
        return $this->hasMany(EvaluationAnswer::class, 'question_id');
    }

    public function scoringLevels()
    {
        return $this->hasMany(QuestionScoringLevel::class, 'question_id')->orderBy('sort_order');
    }
}
