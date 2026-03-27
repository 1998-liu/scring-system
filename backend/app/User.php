<?php
namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'phone', 'password', 'department_id', 'position', 'employee_id', 'email', 'role', 'company_role',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * Find the user by phone for authentication.
     *
     * @param  string  $phone
     * @return \App\User|null
     */
    public function findForPassport($phone)
    {
        return $this->where('phone', $phone)->first();
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function evaluationTasksAsEvaluator()
    {
        return $this->hasMany(EvaluationTask::class, 'evaluator_id');
    }

    public function evaluationTasksAsEvaluatee()
    {
        return $this->hasMany(EvaluationTask::class, 'evaluatee_id');
    }

    public function evaluationReports()
    {
        return $this->hasMany(EvaluationReport::class, 'evaluatee_id');
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles')->withTimestamps();
    }

    public function permissions()
    {
        return $this->hasManyThrough(Permission::class, Role::class, 'id', 'id', 'id', 'role_id');
    }
}
