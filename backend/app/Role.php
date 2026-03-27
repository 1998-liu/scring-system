<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name', 'description', 'parent_id'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles')->withTimestamps();
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function parent()
    {
        return $this->belongsTo(Role::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Role::class, 'parent_id');
    }

    public function scoringRules()
    {
        return $this->hasMany(ScoringRule::class, 'target_role_id');
    }

    public function evaluatorWeights()
    {
        return $this->hasMany(ScoringRuleWeight::class, 'evaluator_role_id');
    }

    // Get all inherited permissions including from parent roles
    public function getAllPermissions()
    {
        $permissions = $this->permissions->toArray();
        $permissionIds = collect($permissions)->pluck('id')->toArray();
        
        // Traverse up the hierarchy to get parent permissions
        $parent = $this->parent;
        while ($parent) {
            foreach ($parent->permissions as $permission) {
                if (!in_array($permission->id, $permissionIds)) {
                    $permissions[] = $permission->toArray();
                    $permissionIds[] = $permission->id;
                }
            }
            $parent = $parent->parent;
        }
        
        return $permissions;
    }
}