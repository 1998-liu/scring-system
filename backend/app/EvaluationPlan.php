<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EvaluationPlan extends Model
{
    protected $fillable = ['name', 'start_date', 'end_date', 'status'];

    public function tasks()
    {
        return $this->hasMany(EvaluationTask::class, 'plan_id');
    }

    public function reports()
    {
        return $this->hasMany(EvaluationReport::class, 'plan_id');
    }
}