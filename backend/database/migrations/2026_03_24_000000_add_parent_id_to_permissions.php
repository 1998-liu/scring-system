<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddParentIdToPermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->integer('parent_id')->nullable()->default(null)->after('description');
        });
        
        // 插入父权限
        $parentPermissions = [
            ['name' => 'users', 'display_name' => '用户管理', 'description' => '用户管理模块', 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'roles', 'display_name' => '角色管理', 'description' => '角色管理模块', 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'departments', 'display_name' => '部门管理', 'description' => '部门管理模块', 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'plans', 'display_name' => '评估计划', 'description' => '评估计划模块', 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'tasks', 'display_name' => '评估任务', 'description' => '评估任务模块', 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'dimensions', 'display_name' => '评估维度', 'description' => '评估维度模块', 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'questions', 'display_name' => '评估问题', 'description' => '评估问题模块', 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'reports', 'display_name' => '评估报告', 'description' => '评估报告模块', 'parent_id' => null, 'created_at' => now(), 'updated_at' => now()],
        ];
        
        foreach ($parentPermissions as $permission) {
            \Illuminate\Support\Facades\DB::table('permissions')->insert($permission);
        }
        
        // 获取父权限ID
        $parentIds = [];
        foreach ($parentPermissions as $permission) {
            $parentIds[$permission['name']] = \Illuminate\Support\Facades\DB::table('permissions')->where('name', $permission['name'])->value('id');
        }
        
        // 更新现有权限的parent_id
        $permissionMappings = [
            'view_users' => $parentIds['users'],
            'create_users' => $parentIds['users'],
            'edit_users' => $parentIds['users'],
            'delete_users' => $parentIds['users'],
            'view_roles' => $parentIds['roles'],
            'create_roles' => $parentIds['roles'],
            'edit_roles' => $parentIds['roles'],
            'delete_roles' => $parentIds['roles'],
            'view_departments' => $parentIds['departments'],
            'create_departments' => $parentIds['departments'],
            'edit_departments' => $parentIds['departments'],
            'delete_departments' => $parentIds['departments'],
            'view_plans' => $parentIds['plans'],
            'create_plans' => $parentIds['plans'],
            'edit_plans' => $parentIds['plans'],
            'delete_plans' => $parentIds['plans'],
            'view_tasks' => $parentIds['tasks'],
            'create_tasks' => $parentIds['tasks'],
            'edit_tasks' => $parentIds['tasks'],
            'delete_tasks' => $parentIds['tasks'],
            'view_dimensions' => $parentIds['dimensions'],
            'create_dimensions' => $parentIds['dimensions'],
            'edit_dimensions' => $parentIds['dimensions'],
            'delete_dimensions' => $parentIds['dimensions'],
            'view_questions' => $parentIds['questions'],
            'create_questions' => $parentIds['questions'],
            'edit_questions' => $parentIds['questions'],
            'delete_questions' => $parentIds['questions'],
            'view_reports' => $parentIds['reports'],
            'generate_reports' => $parentIds['reports'],
        ];
        
        foreach ($permissionMappings as $name => $parentId) {
            \Illuminate\Support\Facades\DB::table('permissions')->where('name', $name)->update(['parent_id' => $parentId]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn('parent_id');
        });
        
        // 删除新增的父权限
        $parentPermissionNames = ['users', 'roles', 'departments', 'plans', 'tasks', 'dimensions', 'questions', 'reports'];
        \Illuminate\Support\Facades\DB::table('permissions')->whereIn('name', $parentPermissionNames)->delete();
    }
}