<?php
namespace App\Http\Controllers;

use App\Department;
use App\EvaluationAnswer;
use App\EvaluationDimension;
use App\EvaluationPlan;
use App\EvaluationQuestion;
use App\EvaluationReport;
use App\EvaluationTask;
use App\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class EvaluationController extends Controller
{
    // 评估计划管理
    public function createPlan(Request $request)
    {
        $plan = EvaluationPlan::create($request->all());
        return response()->json($plan);
    }

    public function getPlans()
    {
        $plans = EvaluationPlan::all();
        return response()->json($plans);
    }

    public function getPlan($id)
    {
        $plan = EvaluationPlan::find($id);
        return response()->json($plan);
    }

    public function updatePlan(Request $request, $id)
    {
        $plan = EvaluationPlan::find($id);

        // 检查计划状态，如果是进行中则只允许更新为已完成状态
        if ($plan->status === 'ongoing') {
            // 只有当更新为已完成状态时才允许操作
            if ($request->input('status') !== 'completed') {
                return response()->json(['error' => '进行中的评估计划不允许编辑'], 400);
            }
        }

        // 当要结束计划时，检查是否有关联的进行中任务
        if ($request->input('status') === 'completed') {
            $tasks = EvaluationTask::where('plan_id', $id)->where('status', '=', 'ongoing')->count();
            if ($tasks > 0) {
                return response()->json(['error' => '评估计划关联的任务中有进行中的任务，不能结束评估计划'], 400);
            }
        }

        $plan->update($request->all());
        return response()->json($plan);
    }

    public function deletePlan($id)
    {
        $plan = EvaluationPlan::find($id);
        $plan->delete();
        return response()->json(['message' => 'Plan deleted successfully']);
    }

    // 评估任务管理
    public function createTask(Request $request)
    {
        try {
            // 检查评估任务的截止日期是否晚于评估计划的结束日期
            $planId   = $request->input('plan_id');
            $deadline = $request->input('deadline');

            if ($planId && $deadline) {
                $plan = EvaluationPlan::find($planId);
                if ($plan) {
                    $taskDeadline = new \DateTime($deadline);
                    $planEndDate  = new \DateTime($plan->end_date);
                    if ($taskDeadline > $planEndDate) {
                        return response()->json(['error' => '评估任务的截止日期不得晚于评估计划的结束日期'], 400);
                    }
                }
            }

            // 创建评估任务
            $task = EvaluationTask::create([
                'plan_id'  => $request->input('plan_id'),
                'status'   => $request->input('status', 'pending'),
                'deadline' => $request->input('deadline'),
            ]);

            // 关联评估者
            if ($request->has('evaluator_ids')) {
                $task->evaluators()->attach($request->input('evaluator_ids'));
            }

            // 关联被评估者
            if ($request->has('evaluatee_ids')) {
                $task->evaluatees()->attach($request->input('evaluatee_ids'));
            }

            // 加载关联数据
            $task->load(['evaluators', 'evaluatees', 'plan']);

            return response()->json($task);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getTasks()
    {
        $tasks = EvaluationTask::with(['evaluators', 'evaluatees', 'plan'])->get();
        return response()->json($tasks);
    }

    public function getTask($id)
    {
        $task = EvaluationTask::with(['evaluators', 'evaluatees', 'plan'])->find($id);
        return response()->json($task);
    }

    public function updateTask(Request $request, $id)
    {
        try {
            $task = EvaluationTask::find($id);
            if (! $task) {
                return response()->json(['error' => 'Task not found'], 404);
            }

            // 当要开启任务时，检查评估计划的状态
            if ($request->input('status') === 'ongoing') {
                $plan = EvaluationPlan::find($task->plan_id);
                if ($plan && $plan->status !== 'ongoing') {
                    return response()->json(['error' => '评估计划的状态为待进行或已结束，无法开启任务'], 400);
                }
            }

            // 如果任务已完成，则允许编辑
            if ($task->status !== 'completed') {
                // 检查评估任务的截止日期是否晚于评估计划的结束日期
                $planId   = $request->input('plan_id', $task->plan_id);
                $deadline = $request->input('deadline', $task->deadline);

                if ($planId && $deadline) {
                    $plan = EvaluationPlan::find($planId);
                    if ($plan) {
                        $taskDeadline = new \DateTime($deadline);
                        $planEndDate  = new \DateTime($plan->end_date);
                        if ($taskDeadline > $planEndDate) {
                            return response()->json(['error' => '评估任务的截止日期不得晚于评估计划的结束日期'], 400);
                        }
                    }
                }
            }

            // 更新评估任务
            $task->update([
                'plan_id'  => $request->input('plan_id', $task->plan_id),
                'status'   => $request->input('status', $task->status),
                'deadline' => $request->input('deadline', $task->deadline),
            ]);

            // 更新评估者关联
            if ($request->has('evaluator_ids')) {
                $task->evaluators()->sync($request->input('evaluator_ids'));
            }

            // 更新被评估者关联
            if ($request->has('evaluatee_ids')) {
                $task->evaluatees()->sync($request->input('evaluatee_ids'));
            }

            // 加载关联数据
            $task->load(['evaluators', 'evaluatees', 'plan']);

            return response()->json($task);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteTask($id)
    {
        $task = EvaluationTask::find($id);
        $task->delete();
        return response()->json(['message' => 'Task deleted successfully']);
    }

    // 评估维度管理
    public function createDimension(Request $request)
    {
        $data = $request->all();

        // 如果传入了target_role_id，自动填充target_role名称
        if (isset($data['target_role_id']) && ! empty($data['target_role_id'])) {
            $role = \App\Role::find($data['target_role_id']);
            if ($role) {
                $data['target_role'] = $role->name;
            }
        }

        // 权重合规性校验：检查该角色下所有维度权重总和是否超过100%
        if (isset($data['target_role_id']) && ! empty($data['target_role_id'])) {
            $existingWeight = EvaluationDimension::where('target_role_id', $data['target_role_id'])
                ->sum('weight');
            $newWeight   = floatval($data['weight'] ?? 0);
            $totalWeight = $existingWeight + $newWeight;

            if ($totalWeight > 100) {
                return response()->json([
                    'error' => '当前角色维度权重总和已超过100%，无法保存，请调整权重后重试',
                ], 422);
            }
        }

        $dimension = EvaluationDimension::create($data);
        return response()->json($dimension);
    }

    public function getDimensions()
    {
        $currentUser = User::with('roles')->find(auth()->id());

        if (! $currentUser) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // 获取当前用户可查看的角色ID列表
        $visibleRoleIds = $this->getVisibleRoleIds($currentUser);

        // 只返回可见角色下的维度，同时加载角色关联
        $dimensions = EvaluationDimension::with('targetRole')
            ->whereIn('target_role_id', $visibleRoleIds)
            ->orWhereIn('target_role', $this->getVisibleRoles($currentUser))
            ->get();

        return response()->json($dimensions);
    }

    // 获取当前用户可查看的角色ID列表
    private function getVisibleRoleIds($user)
    {
        $roleIds = [];

        foreach ($user->roles as $userRole) {
            $roleIds[] = $userRole->id;
            $roleIds   = array_merge($roleIds, $this->getChildRoleIds($userRole->id));
        }

        return array_unique($roleIds);
    }

    // 获取当前用户可查看的角色名称列表
    private function getVisibleRoles($user)
    {
        $roleNames = [];

        foreach ($user->roles as $userRole) {
            $roleNames[] = $userRole->name;
            $roleNames   = array_merge($roleNames, $this->getChildRoleNames($userRole->id));
        }

        return array_unique($roleNames);
    }

    // 递归获取所有子角色ID
    private function getChildRoleIds($parentId)
    {
        $childRoles = \App\Role::where('parent_id', $parentId)->get();
        $ids        = [];

        foreach ($childRoles as $role) {
            $ids[] = $role->id;
            $ids   = array_merge($ids, $this->getChildRoleIds($role->id));
        }

        return $ids;
    }

    // 递归获取所有子角色名称
    private function getChildRoleNames($parentId)
    {
        $childRoles = \App\Role::where('parent_id', $parentId)->get();
        $names      = [];

        foreach ($childRoles as $role) {
            $names[] = $role->name;
            $names   = array_merge($names, $this->getChildRoleNames($role->id));
        }

        return $names;
    }

    // 获取当前用户可管理的角色列表（用于维度管理页面）
    public function getManageableRoles()
    {
        $currentUser = User::with('roles')->find(auth()->id());

        if (! $currentUser) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // 获取当前用户可管理的子角色列表
        $manageableRoles = $this->getManageableRolesList($currentUser);

        return response()->json($manageableRoles);
    }

    // 获取当前用户可管理的子角色列表
    private function getManageableRolesList($user)
    {
        $roles = [];

        foreach ($user->roles as $userRole) {
            $childRoles = $this->getChildRolesRecursive($userRole->id);
            $roles      = array_merge($roles, $childRoles);
        }

        return $roles;
    }

    // 递归获取所有子角色（包含详细信息）
    private function getChildRolesRecursive($parentId)
    {
        $childRoles = \App\Role::where('parent_id', $parentId)->get()->toArray();
        $result     = [];

        foreach ($childRoles as $role) {
            $result[] = $role;
            $result   = array_merge($result, $this->getChildRolesRecursive($role['id']));
        }

        return $result;
    }

    public function updateDimension(Request $request, $id)
    {
        $dimension = EvaluationDimension::find($id);
        if (! $dimension) {
            return response()->json(['error' => 'Dimension not found'], 404);
        }

        $data = $request->all();

        // 权重合规性校验：检查该角色下所有维度权重总和是否超过100%
        $targetRoleId = $data['target_role_id'] ?? $dimension->target_role_id;
        if ($targetRoleId) {
            // 计算该角色下其他维度的权重总和（排除当前正在编辑的维度）
            $existingWeight = EvaluationDimension::where('target_role_id', $targetRoleId)
                ->where('id', '!=', $id)
                ->sum('weight');
            $newWeight   = floatval($data['weight'] ?? $dimension->weight);
            $totalWeight = $existingWeight + $newWeight;

            if ($totalWeight > 100) {
                return response()->json([
                    'error' => '当前角色维度权重总和已超过100%，无法保存，请调整权重后重试',
                ], 422);
            }
        }

        $dimension->update($data);
        return response()->json($dimension);
    }

    public function deleteDimension($id)
    {
        $dimension = EvaluationDimension::find($id);

        if (! $dimension) {
            return response()->json(['error' => 'Dimension not found'], 404);
        }

        // 检查是否有关联的评估问题
        $questionsCount = EvaluationQuestion::where('dimension_id', $id)->count();
        if ($questionsCount > 0) {
            return response()->json([
                'error' => '该评估维度已关联评估问题，无法删除，请先解除关联后再操作',
            ], 422);
        }

        $dimension->delete();
        return response()->json(['message' => 'Dimension deleted successfully']);
    }

    // 评估问题管理
    public function createQuestion(Request $request)
    {
        $question = EvaluationQuestion::create($request->all());
        return response()->json($question);
    }

    public function getQuestions()
    {
        $questions = EvaluationQuestion::with('dimension.targetRole')->get();
        return response()->json($questions);
    }

    public function getQuestionsByRole($roleId)
    {
        $questions = EvaluationQuestion::with('dimension.targetRole')
            ->whereHas('dimension', function ($query) use ($roleId) {
                $query->where('target_role_id', $roleId);
            })
            ->get();
        return response()->json($questions);
    }

    public function updateQuestion(Request $request, $id)
    {
        $question = EvaluationQuestion::find($id);
        $question->update($request->all());
        return response()->json($question);
    }

    public function deleteQuestion($id)
    {
        $question = EvaluationQuestion::find($id);
        $question->delete();
        return response()->json(['message' => 'Question deleted successfully']);
    }

    // 评估答案提交
    public function submitAnswer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_id'      => 'required|exists:evaluation_tasks,id',
            'question_id'  => 'required|exists:evaluation_questions,id',
            'evaluator_id' => 'required|exists:users,id',
            'evaluatee_id' => 'required|exists:users,id',
            'score'        => 'required|numeric|min:0|max:100',
            'comment'      => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // 检查任务状态
        $task = EvaluationTask::find($request->task_id);
        if ($task->status !== 'ongoing') {
            return response()->json(['error' => '评估任务未开启或已结束'], 400);
        }

        // 检查评估者是否被授权
        $isAuthorized = $task->evaluators()->where('user_id', $request->evaluator_id)->exists();
        if (! $isAuthorized) {
            return response()->json(['error' => '您没有被授权参与此评估任务'], 403);
        }

        // 检查被评估者是否在任务中
        $isEvaluatee = $task->evaluatees()->where('user_id', $request->evaluatee_id)->exists();
        if (! $isEvaluatee) {
            return response()->json(['error' => '被评估者不在当前评估任务中'], 400);
        }

        // 检查是否已经提交过答案
        $existingAnswer = EvaluationAnswer::where([
            'task_id'      => $request->task_id,
            'question_id'  => $request->question_id,
            'evaluator_id' => $request->evaluator_id,
            'evaluatee_id' => $request->evaluatee_id,
        ])->first();

        if ($existingAnswer) {
            // 更新已有答案
            $existingAnswer->update([
                'score'   => $request->score,
                'comment' => $request->comment,
            ]);
            return response()->json($existingAnswer);
        }

        // 创建新答案
        $answer = EvaluationAnswer::create($request->all());
        return response()->json($answer, 201);
    }

    // 批量提交评估答案
    public function submitAnswers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_id'               => 'required|exists:evaluation_tasks,id',
            'evaluator_id'          => 'required|exists:users,id',
            'evaluatee_id'          => 'required|exists:users,id',
            'answers'               => 'required|array',
            'answers.*.question_id' => 'required|exists:evaluation_questions,id',
            'answers.*.score'       => 'required|numeric|min:0|max:100',
            'answers.*.comment'     => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // 检查任务状态
        $task = EvaluationTask::find($request->task_id);
        if ($task->status !== 'ongoing') {
            return response()->json(['error' => '评估任务未开启或已结束'], 400);
        }

        // 检查评估者是否被授权
        $isAuthorized = $task->evaluators()->where('user_id', $request->evaluator_id)->exists();
        if (! $isAuthorized) {
            return response()->json(['error' => '您没有被授权参与此评估任务'], 403);
        }

        // 检查被评估者是否在任务中
        $isEvaluatee = $task->evaluatees()->where('user_id', $request->evaluatee_id)->exists();
        if (! $isEvaluatee) {
            return response()->json(['error' => '被评估者不在当前评估任务中'], 400);
        }

        DB::beginTransaction();
        try {
            foreach ($request->answers as $answerData) {
                // 检查是否已经提交过答案
                $existingAnswer = EvaluationAnswer::where([
                    'task_id'      => $request->task_id,
                    'question_id'  => $answerData['question_id'],
                    'evaluator_id' => $request->evaluator_id,
                    'evaluatee_id' => $request->evaluatee_id,
                ])->first();

                if ($existingAnswer) {
                    // 更新已有答案
                    $existingAnswer->update([
                        'score'   => $answerData['score'],
                        'comment' => $answerData['comment'] ?? null,
                    ]);
                } else {
                    // 创建新答案
                    EvaluationAnswer::create([
                        'task_id'      => $request->task_id,
                        'question_id'  => $answerData['question_id'],
                        'evaluator_id' => $request->evaluator_id,
                        'evaluatee_id' => $request->evaluatee_id,
                        'score'        => $answerData['score'],
                        'comment'      => $answerData['comment'] ?? null,
                    ]);
                }
            }

            DB::commit();
            return response()->json(['message' => '评估答案提交成功']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => '提交失败：' . $e->getMessage()], 500);
        }
    }

    // 获取用户的评估任务
    public function getUserTasks($userId)
    {
        $tasks = EvaluationTask::whereHas('evaluators', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->with(['evaluators', 'evaluatees', 'plan', 'answers' => function ($query) use ($userId) {
            $query->where('evaluator_id', $userId);
        }])->get();

        return response()->json($tasks);
    }

    // 获取评估任务的答案
    public function getTaskAnswers($taskId, $evaluatorId)
    {
        $answers = EvaluationAnswer::where('task_id', $taskId)
            ->where('evaluator_id', $evaluatorId)
            ->with('question')
            ->get();

        return response()->json($answers);
    }

    // 评估报告生成
    public function generateReport($planId, $evaluateeId)
    {
        // 获取被评估人信息
        $evaluatee     = User::find($evaluateeId);
        $evaluateeRole = $evaluatee->company_role;

        // 计算不同角色的评议得分
        $tasks = EvaluationTask::where('plan_id', $planId)->where('evaluatee_id', $evaluateeId)->with('evaluator')->get();

        // 按评估人角色分组
        $roleScores = [
            '正职领导'    => ['score' => 0, 'count' => 0],
            '中层正职'    => ['score' => 0, 'count' => 0],
            '中层副职及助理' => ['score' => 0, 'count' => 0],
            '管理人员'    => ['score' => 0, 'count' => 0],
        ];

        $dimensionScores = [];

        foreach ($tasks as $task) {
            $evaluatorRole = $task->evaluator->company_role;
            $answers       = $task->answers;

            foreach ($answers as $answer) {
                $question  = $answer->question;
                $dimension = $question->dimension;

                // 计算维度得分
                if (! isset($dimensionScores[$dimension->id])) {
                    $dimensionScores[$dimension->id] = [
                        'name'   => $dimension->name,
                        'weight' => $dimension->weight,
                        'score'  => 0,
                        'count'  => 0,
                    ];
                }
                $dimensionScores[$dimension->id]['score'] += $answer->score;
                $dimensionScores[$dimension->id]['count']++;

                // 按评估人角色计算得分
                if (isset($roleScores[$evaluatorRole])) {
                    $roleScores[$evaluatorRole]['score'] += $answer->score;
                    $roleScores[$evaluatorRole]['count']++;
                }
            }
        }

        // 计算各角色的平均分
        foreach ($roleScores as &$roleScore) {
            if ($roleScore['count'] > 0) {
                $roleScore['average'] = $roleScore['score'] / $roleScore['count'];
            } else {
                $roleScore['average'] = 0;
            }
        }

        // 计算综合得分
        $totalScore = 0;
        switch ($evaluateeRole) {
            case '中层正职':
                $totalScore = $roleScores['正职领导']['average'] * 0.5 +
                    $roleScores['中层正职']['average'] * 0.4 +
                    $roleScores['管理人员']['average'] * 0.1;
                break;
            case '中层副职及助理':
                $totalScore = $roleScores['正职领导']['average'] * 0.5 +
                    $roleScores['中层正职']['average'] * 0.4 +
                    $roleScores['管理人员']['average'] * 0.1;
                break;
            case '管理人员':
                $totalScore = $roleScores['正职领导']['average'] * 0.4 +
                    $roleScores['中层正职']['average'] * 0.3 +
                    $roleScores['中层副职及助理']['average'] * 0.2 +
                    $roleScores['管理人员']['average'] * 0.1;
                break;
            default:
                // 正职领导的评分逻辑可以根据实际需求调整
                $totalScore = $roleScores['正职领导']['average'] * 0.5 +
                    $roleScores['中层正职']['average'] * 0.3 +
                    $roleScores['中层副职及助理']['average'] * 0.1 +
                    $roleScores['管理人员']['average'] * 0.1;
        }

        // 计算维度加权得分
        foreach ($dimensionScores as &$score) {
            if ($score['count'] > 0) {
                $score['average'] = $score['score'] / $score['count'];
            }
        }

        // 创建报告
        $report = EvaluationReport::create([
            'evaluatee_id' => $evaluateeId,
            'plan_id'      => $planId,
            'total_score'  => $totalScore,
            'scores'       => json_encode($dimensionScores),
            'role_scores'  => json_encode($roleScores),
            'strengths'    => '待分析',
            'improvements' => '待分析',
        ]);

        return response()->json($report);
    }

    public function getReports()
    {
        $reports = EvaluationReport::with(['evaluatee', 'plan'])->get();
        return response()->json($reports);
    }

    // 用户管理
    public function getUsers(Request $request)
    {
        $query = User::with('department');

        // 姓名模糊搜索
        if ($request->has('name') && ! empty($request->name)) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        // 手机号模糊搜索
        if ($request->has('phone') && ! empty($request->phone)) {
            $query->where('phone', 'like', '%' . $request->phone . '%');
        }

        // 公司角色精确匹配
        if ($request->has('company_role') && ! empty($request->company_role)) {
            $query->where('company_role', $request->company_role);
        }

        // 分页处理
        $pageSize = $request->input('page_size', 10);
        $page     = $request->input('page', 1);

        $total = $query->count();
        $users = $query->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->get();

        return response()->json([
            'data'         => $users,
            'total'        => $total,
            'current_page' => $page,
            'per_page'     => $pageSize,
            'last_page'    => ceil($total / $pageSize),
        ]);
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::find($id);
        $user->update($request->all());

        // 更新角色关联
        if ($request->company_role) {
            $role = \App\Role::where('name', $request->company_role)->first();
            if ($role) {
                // 先清除现有角色关联
                $user->roles()->detach();
                // 关联新角色
                $user->roles()->attach($role->id);
            }
        }

        $user->load('roles', 'roles.permissions');
        return response()->json($user);
    }

    public function getUser($id)
    {
        $user = User::with('roles', 'roles.permissions', 'department')->find($id);
        if (! $user) {
            return response()->json(['error' => 'User not found'], 404);
        }
        return response()->json($user);
    }

    // 部门管理
    public function getDepartments()
    {
        $departments = Department::with(['parent', 'leader'])->get();
        return response()->json($departments);
    }

    public function createDepartment(Request $request)
    {
        $department = Department::create($request->all());
        return response()->json($department);
    }

    public function updateDepartment(Request $request, $id)
    {
        $department = Department::find($id);
        $department->update($request->all());
        return response()->json($department);
    }

    public function deleteDepartment($id)
    {
        $department = Department::find($id);
        $department->delete();
        return response()->json(['message' => 'Department deleted successfully']);
    }

    // 用户管理
    public function deleteUser($id)
    {
        $user = User::find($id);
        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }

    public function changeUserPassword(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'newPassword' => 'required|min:8|max:16|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,16}$/',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $user = User::find($id);

        if (! $user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // 更新密码
        $user->password = Hash::make($request->newPassword);
        $user->save();

        return response()->json(['message' => 'Password changed successfully']);
    }

    // 角色管理
    public function getRoles(Request $request)
    {
        // 获取查询参数
        $page      = $request->input('page', 1);
        $pageSize  = $request->input('pageSize', 10);
        $sortBy    = $request->input('sortBy', 'id');
        $sortOrder = $request->input('sortOrder', 'asc');
        $search    = $request->input('search', '');

        // 构建查询
        $query = \App\Role::query();

        // 搜索功能
        if ($search) {
            $query->where('name', 'like', '%' . $search . '%')
                ->orWhere('description', 'like', '%' . $search . '%');
        }

        // 排序功能
        $query->orderBy($sortBy, $sortOrder);

        // 分页功能
        $roles = $query->paginate($pageSize, ['*'], 'page', $page);

        // 转换为数组并返回
        return response()->json([
            'items'    => $roles->items(),
            'total'    => $roles->total(),
            'page'     => $roles->currentPage(),
            'pageSize' => $roles->perPage(),
            'pages'    => $roles->lastPage(),
        ]);
    }

    public function createRole(Request $request)
    {
        // Validate parent_id to prevent circular relationships
        if ($request->parent_id) {
            $this->validateParentRelationship($request->parent_id, null);
        }

        $role = \App\Role::create($request->all());
        return response()->json($role);
    }

    public function updateRole(Request $request, $id)
    {
        $role = \App\Role::find($id);

        // Validate parent_id to prevent circular relationships
        if ($request->parent_id) {
            $this->validateParentRelationship($request->parent_id, $id);
        }

        $role->update($request->all());
        return response()->json($role);
    }

    // Validate parent-child relationship to prevent circular references
    private function validateParentRelationship($parentId, $currentRoleId)
    {
        // Prevent self-reference
        if ($parentId == $currentRoleId) {
            throw new \Exception('A role cannot be its own parent');
        }

        // Check for circular references
        $parentRole = \App\Role::find($parentId);
        while ($parentRole) {
            if ($parentRole->id == $currentRoleId) {
                throw new \Exception('Circular parent-child relationship detected');
            }
            $parentRole = $parentRole->parent;
        }
    }

    // Get role hierarchy
    public function getRoleHierarchy()
    {
        $roles   = \App\Role::all();
        $roleMap = [];

        // Create a map of roles by ID
        foreach ($roles as $role) {
            $roleMap[$role->id] = [
                'id'          => $role->id,
                'name'        => $role->name,
                'description' => $role->description,
                'parent_id'   => $role->parent_id,
                'children'    => [],
            ];
        }

        // Build the hierarchy
        $hierarchy = [];
        foreach ($roleMap as &$role) {
            if (is_null($role['parent_id'])) {
                $hierarchy[] = &$role;
            } else if (isset($roleMap[$role['parent_id']])) {
                $roleMap[$role['parent_id']]['children'][] = &$role;
            }
        }

        return response()->json($hierarchy);
    }

    // Get inherited permissions for a role
    public function getInheritedPermissions($roleId)
    {
        $role                 = \App\Role::find($roleId);
        $inheritedPermissions = [];

        // Traverse up the hierarchy to collect inherited permissions
        while ($role) {
            $rolePermissions = $role->permissions;
            foreach ($rolePermissions as $permission) {
                $inheritedPermissions[$permission->id] = $permission;
            }
            $role = $role->parent;
        }

        return response()->json(array_values($inheritedPermissions));
    }

    // Get permission differences between child and parent role
    public function getPermissionDifferences($roleId)
    {
        $role = \App\Role::find($roleId);

        if (! $role->parent) {
            return response()->json([
                'inherited'  => [],
                'additional' => $role->permissions,
                'removed'    => [],
            ]);
        }

        // Get parent permissions
        $parentPermissions = [];
        $tempRole          = $role->parent;
        while ($tempRole) {
            foreach ($tempRole->permissions as $permission) {
                $parentPermissions[$permission->id] = $permission;
            }
            $tempRole = $tempRole->parent;
        }

        // Get child permissions
        $childPermissions = [];
        foreach ($role->permissions as $permission) {
            $childPermissions[$permission->id] = $permission;
        }

        // Calculate differences
        $inherited  = array_intersect_key($childPermissions, $parentPermissions);
        $additional = array_diff_key($childPermissions, $parentPermissions);
        $removed    = array_diff_key($parentPermissions, $childPermissions);

        return response()->json([
            'inherited'  => array_values($inherited),
            'additional' => array_values($additional),
            'removed'    => array_values($removed),
        ]);
    }

    public function deleteRole($id)
    {
        $role = \App\Role::find($id);
        $role->delete();
        return response()->json(['message' => 'Role deleted successfully']);
    }

    // 权限管理
    public function getPermissions()
    {
        $permissions = \App\Permission::all();
        return response()->json($permissions);
    }

    public function getRolePermissions($roleId)
    {
        $role = \App\Role::find($roleId);
        if (! $role) {
            return response()->json([]);
        }
        $permissions = $role->permissions;
        return response()->json($permissions);
    }

    public function assignPermissions(Request $request, $roleId)
    {
        $permissions = $request->input('permissions', []);

        // 先清除现有权限
        DB::table('role_permissions')->where('role_id', $roleId)->delete();

        // 分配新权限
        if (is_array($permissions)) {
            foreach ($permissions as $permissionId) {
                DB::table('role_permissions')->insert([
                    'role_id'       => $roleId,
                    'permission_id' => $permissionId,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }

        return response()->json(['message' => 'Permissions assigned successfully']);
    }

    // 自动为超级管理员分配所有权限
    public function autoAssignSuperAdminPermissions()
    {
        // 获取超级管理员角色（假设角色名称为"超级管理员"）
        $superAdminRole = \App\Role::where('name', '超级管理员')->first();

        if ($superAdminRole) {
            // 获取所有权限
            $allPermissions = \App\Permission::all();

            // 先清除现有权限
            DB::table('role_permissions')->where('role_id', $superAdminRole->id)->delete();

            // 分配所有权限
            foreach ($allPermissions as $permission) {
                DB::table('role_permissions')->insert([
                    'role_id'       => $superAdminRole->id,
                    'permission_id' => $permission->id,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }

            return response()->json(['message' => 'Super admin permissions auto-assigned successfully']);
        }

        return response()->json(['error' => 'Super admin role not found'], 404);
    }
}
