<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// 认证路由
Route::post('register', 'AuthController@register');
Route::post('login', 'AuthController@login');

// 需要认证的路由
Route::middleware('auth:api')->group(function () {
    Route::get('me', 'AuthController@me');
    Route::post('logout', 'AuthController@logout');
    Route::post('change-password', 'AuthController@changePassword');

    // 评估管理路由
    Route::post('plans', 'EvaluationController@createPlan');
    Route::get('plans', 'EvaluationController@getPlans');
    Route::get('plans/{id}', 'EvaluationController@getPlan');
    Route::put('plans/{id}', 'EvaluationController@updatePlan');
    Route::delete('plans/{id}', 'EvaluationController@deletePlan');

    Route::post('tasks', 'EvaluationController@createTask');
    Route::get('tasks', 'EvaluationController@getTasks');
    Route::get('tasks/{id}', 'EvaluationController@getTask');
    Route::put('tasks/{id}', 'EvaluationController@updateTask');
    Route::delete('tasks/{id}', 'EvaluationController@deleteTask');

    Route::post('dimensions', 'EvaluationController@createDimension');
    Route::get('dimensions', 'EvaluationController@getDimensions');
    Route::get('dimensions/manageable-roles', 'EvaluationController@getManageableRoles');
    Route::post('dimensions/copy', 'EvaluationController@copyDimensions');
    Route::put('dimensions/{id}', 'EvaluationController@updateDimension');
    Route::delete('dimensions/{id}', 'EvaluationController@deleteDimension');

    Route::post('questions', 'EvaluationController@createQuestion');
    Route::get('questions', 'EvaluationController@getQuestions');
    Route::get('questions/by-role/{roleId}', 'EvaluationController@getQuestionsByRole');
    Route::put('questions/{id}', 'EvaluationController@updateQuestion');
    Route::delete('questions/{id}', 'EvaluationController@deleteQuestion');

    Route::post('answers', 'EvaluationController@submitAnswer');
    Route::post('answers/batch', 'EvaluationController@submitAnswers');
    Route::get('user-tasks/{userId}', 'EvaluationController@getUserTasks');
    Route::get('task-answers/{taskId}/{evaluatorId}', 'EvaluationController@getTaskAnswers');

    Route::post('reports/{planId}/{evaluateeId}', 'EvaluationController@generateReport');
    Route::get('reports', 'EvaluationController@getReports');

    // 用户管理
    Route::get('users', 'EvaluationController@getUsers');
    Route::get('users/{id}', 'EvaluationController@getUser');
    Route::put('users/{id}', 'EvaluationController@updateUser');
    Route::delete('users/{id}', 'EvaluationController@deleteUser');
    Route::post('users/{id}/change-password', 'EvaluationController@changeUserPassword');

    // 部门管理
    Route::get('departments', 'EvaluationController@getDepartments');
    Route::post('departments', 'EvaluationController@createDepartment');
    Route::put('departments/{id}', 'EvaluationController@updateDepartment');
    Route::delete('departments/{id}', 'EvaluationController@deleteDepartment');

    // 角色管理
    Route::get('roles', 'EvaluationController@getRoles');
    Route::post('roles', 'EvaluationController@createRole');
    Route::put('roles/{id}', 'EvaluationController@updateRole');
    Route::delete('roles/{id}', 'EvaluationController@deleteRole');
    Route::get('roles/hierarchy', 'EvaluationController@getRoleHierarchy');
    Route::get('roles/{id}/inherited-permissions', 'EvaluationController@getInheritedPermissions');
    Route::get('roles/{id}/permission-differences', 'EvaluationController@getPermissionDifferences');

    // 权限管理
    Route::get('permissions', 'EvaluationController@getPermissions');
    Route::get('roles/{id}/permissions', 'EvaluationController@getRolePermissions');
    Route::post('roles/{id}/permissions', 'EvaluationController@assignPermissions');
    Route::post('roles/auto-assign-super-admin', 'EvaluationController@autoAssignSuperAdminPermissions');

    // 评分规则管理
    Route::get('scoring-rules', 'ScoringRuleController@index');
    Route::post('scoring-rules', 'ScoringRuleController@store');
    Route::get('scoring-rules/roles', 'ScoringRuleController@getRoles');
    Route::get('scoring-rules/role/{roleId}', 'ScoringRuleController@getByRoleId');
    Route::post('scoring-rules/calculate', 'ScoringRuleController@calculateScore');
    Route::get('scoring-rules/{id}', 'ScoringRuleController@show');
    Route::put('scoring-rules/{id}', 'ScoringRuleController@update');
    Route::delete('scoring-rules/{id}', 'ScoringRuleController@destroy');
    Route::get('scoring-rules/{id}/history', 'ScoringRuleController@history');

    // 评分等级管理
    Route::get('scoring-levels', 'ScoringLevelController@index');
    Route::post('scoring-levels', 'ScoringLevelController@store');
    Route::put('scoring-levels/{id}', 'ScoringLevelController@update');
    Route::delete('scoring-levels/{id}', 'ScoringLevelController@destroy');
    Route::get('scoring-levels/score/{score}', 'ScoringLevelController@getLevelByScore');
});
