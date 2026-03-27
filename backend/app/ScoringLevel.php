<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ScoringLevel extends Model
{
    protected $fillable = ['name', 'code', 'min_score', 'max_score', 'color', 'sort_order', 'is_active'];

    public static function getLevelByScore($score)
    {
        return static::where('is_active', true)
            ->where('min_score', '<=', $score)
            ->where('max_score', '>=', $score)
            ->orderBy('sort_order')
            ->first();
    }
}
