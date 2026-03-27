<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 权限表
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->string('description')->nullable();
            $table->integer('parent_id')->nullable()->default(null);
            $table->timestamps();
        });

        // 角色-权限关联表
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->integer('role_id');
            $table->integer('permission_id');
            $table->timestamps();
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
        
        // 插入子权限
        $childPermissions = [
            ['name' => 'view_users', 'display_name' => '查看用户', 'description' => '查看用户列表', 'parent_id' => $parentIds['users'], 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'create_users', 'display_name' => '创建用户', 'description' => '创建新用户', 'parent_id' => $parentIds['users'], 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'edit_users', 'display_name' => '编辑用户', 'description' => '编辑用户信息', 'parent_id' => $parentIds['users'], 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'delete_users', 'display_name' => '删除用户', 'description' => '删除用户', 'parent_id' => $parentIds['users'], 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'view_roles', 'display_name' => '查看角色', 'description' => '查看角色列表', 'parent_id' => $parentIds['roles'], 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'create_roles', 'display_name' => '创建角色', 'description' => '创建新角色', 'parent_id' => $parentIds['roles'], 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'edit_roles', 'display_name' => '编辑角色', 'description' => '编辑角色信息', 'parent_id' => $parentIds['roles'], 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'delete_roles', 'display_name' => '删除角色', 'description' => '删除角色', 'parent_id' => $parentIds['roles'], 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'view_departments', 'display_name' => '查看部门', 'description' => '查看部门列表', 'parent_id' => $parentIds['departments'], 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'create_departments', 'display_name' => '创建部门', 'description' => '创建新部门', 'parent_id' => $parentIds['departments'], 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'edit_departments', 'display_name' => '编辑部门', 'description' => '编辑部门信息', 'parent_id' => $parentIds['departments'], 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'delete_departments', 'display_name' => '删除部门', 'description' => '删除部门', 'parent_id' => $parentIds['departments'], 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'view_plans', 'display_name' => '查看评估计划', 'description' => '查看评估计划列表', 'parent_id' => $parentIds['plans'], 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'create_plans', 'display_name' => '创建评估计划', 'description' => '创建新评估计划', 'parent_id' => $parentIds['plans'], 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'edit_plans', 'display_name' => '编辑评估计划', 'description' => '编辑评估计划信息', 'parent_id' => $parentIds['plans'], 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'delete_plans', 'display_name' => '删除评估计划', 'description' => '删除评估计划', 'parent_id' => $parentIds['plans'], 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'view_tasks', 'display_name' => '查看评估任务', 'description' => '查看评估任务列表', 'parent_id' => $parentIds['tasks'], 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'create_tasks', 'display_name' => '创建评估任务', 'description' => '创建新评估任务', 'parent_id' => $parentIds['tasks'], 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'edit_tasks', 'display_name' => '编辑评估任务', 'description' => '编辑评估任务信息', 'parent_id' => $parentIds['tasks'], 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'delete_tasks', 'display_name' => '删除评估任务', 'description' => '删除评估任务', 'parent_id' => $parentIds['tasks'], 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'view_dimensions', 'display_name' => '查看评估维度', 'description' => '查看评估维度列表', 'parent_id' => $parentIds['dimensions'], 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'create_dimensions', 'display_name' => '创建评估维度', 'description' => '创建新评估维度', 'parent_id' => $parentIds['dimensions'], 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'edit_dimensions', 'display_name' => '编辑评估维度', 'description' => '编辑评估维度信息', 'parent_id' => $parentIds['dimensions'], 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'delete_dimensions', 'display_name' => '删除评估维度', 'description' => '删除评估维度', 'parent_id' => $parentIds['dimensions'], 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'view_questions', 'display_name' => '查看评估问题', 'description' => '查看评估问题列表', 'parent_id' => $parentIds['questions'], 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'create_questions', 'display_name' => '创建评估问题', 'description' => '创建新评估问题', 'parent_id' => $parentIds['questions'], 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'edit_questions', 'display_name' => '编辑评估问题', 'description' => '编辑评估问题信息', 'parent_id' => $parentIds['questions'], 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'delete_questions', 'display_name' => '删除评估问题', 'description' => '删除评估问题', 'parent_id' => $parentIds['questions'], 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'view_reports', 'display_name' => '查看评估报告', 'description' => '查看评估报告列表', 'parent_id' => $parentIds['reports'], 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'generate_reports', 'display_name' => '生成评估报告', 'description' => '生成评估报告', 'parent_id' => $parentIds['reports'], 'created_at' => now(), 'updated_at' => now()],
        ];
        
        foreach ($childPermissions as $permission) {
            \Illuminate\Support\Facades\DB::table('permissions')->insert($permission);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
    }
}