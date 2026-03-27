<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EvaluationAnswer extends Model
{
    protected $fillable = ['task_id', 'question_id', 'evaluator_id', 'evaluatee_id', 'score', 'comment'];

    public function task()
    {
        return $this->belongsTo(EvaluationTask::class, 'task_id');
    }

    public function question()
    {
        return $this->belongsTo(EvaluationQuestion::class, 'question_id');
    }

    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function evaluatee()
    {
        return $this->belongsTo(User::class, 'evaluatee_id');
    }
}
