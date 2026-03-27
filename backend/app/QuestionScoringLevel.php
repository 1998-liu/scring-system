<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class QuestionScoringLevel extends Model
{
    protected $fillable = ['question_id', 'level_name', 'score', 'description', 'sort_order'];

    public function question()
    {
        return $this->belongsTo(EvaluationQuestion::class);
    }
}
