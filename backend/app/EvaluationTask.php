<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class EvaluationTask extends Model
{
    protected $fillable = ['plan_id', 'status', 'deadline'];

    public function plan()
    {
        return $this->belongsTo(EvaluationPlan::class, 'plan_id');
    }

    public function evaluators()
    {
        return $this->belongsToMany(User::class, 'evaluation_task_evaluators');
    }

    public function evaluatees()
    {
        return $this->belongsToMany(User::class, 'evaluation_task_evaluatees');
    }

    public function answers()
    {
        return $this->hasMany(EvaluationAnswer::class, 'task_id');
    }
}
