<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EvaluationDimension extends Model
{
    protected $fillable = ['name', 'description', 'weight', 'target_role', 'target_role_id', 'category'];

    public function questions()
    {
        return $this->hasMany(EvaluationQuestion::class, 'dimension_id');
    }
    
    public function targetRole()
    {
        return $this->belongsTo(Role::class, 'target_role_id');
    }
}
