<template>
  <div class="tasks-container">
    <el-card>
      <template #header>
        <div class="card-header">
          <h2>评估任务管理</h2>
          <el-button type="primary" @click="openCreateTaskDialog">
            <el-icon><Plus /></el-icon>
            创建任务
          </el-button>
        </div>
      </template>
      <el-table :data="formattedTasks" style="width: 100%">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column label="评估者" width="200">
          <template #default="scope">
            {{ scope.row.evaluators && scope.row.evaluators.length > 0 ? scope.row.evaluators.map(e => e.name).join(', ') : '-' }}
          </template>
        </el-table-column>
        <el-table-column label="被评估者" width="200">
          <template #default="scope">
            {{ scope.row.evaluatees && scope.row.evaluatees.length > 0 ? scope.row.evaluatees.map(e => e.name).join(', ') : '-' }}
          </template>
        </el-table-column>
        <el-table-column label="计划" width="150">
          <template #default="scope">
            {{ scope.row.plan?.name || '-' }}
          </template>
        </el-table-column>
        <el-table-column label="状态">
          <template #default="scope">
            {{ scope.row.formattedStatus }}
          </template>
        </el-table-column>
        <el-table-column label="截止日期">
          <template #default="scope">
            {{ scope.row.formattedDeadline }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="300">
          <template #default="scope">
            <el-button size="small" @click="editTask(scope.row)">编辑</el-button>
            <el-button
              size="small"
              type="primary"
              @click="updateTaskStatus(scope.row.id, 'ongoing')"
              :disabled="scope.row.status !== 'pending'"
              >开启</el-button
            >
            <el-button
              size="small"
              type="warning"
              @click="updateTaskStatus(scope.row.id, 'completed')"
              :disabled="scope.row.status !== 'ongoing'"
              >结束</el-button
            >
            <el-button
              size="small"
              type="danger"
              @click="confirmDeleteTask(scope.row.id)"
              :disabled="scope.row.status === 'ongoing'"
              >删除</el-button
            >
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <!-- 创建/编辑任务对话框 -->
    <el-dialog v-model="dialogVisible" :title="dialogTitle">
      <el-form :model="taskForm" :rules="taskRules" ref="taskFormRef" label-width="100px">
        <el-form-item label="评估计划" prop="plan_id">
          <el-select v-model="taskForm.plan_id" placeholder="选择评估计划">
            <el-option v-for="plan in filteredPlans" :key="plan.id" :label="plan.name" :value="plan.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="评估者" prop="evaluator_ids">
          <el-cascader
            v-model="taskForm.evaluator_ids"
            :options="userTree"
            :props="{
              multiple: true,
              checkStrictly: false
            }"
            placeholder="选择评估者"
          />
        </el-form-item>
        <el-form-item label="被评估者" prop="evaluatee_ids">
          <el-cascader
            v-model="taskForm.evaluatee_ids"
            :options="userTree"
            :props="{
              multiple: true,
              checkStrictly: false
            }"
            placeholder="选择被评估者"
          />
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-select v-model="taskForm.status" placeholder="选择状态">
            <el-option label="待开始" value="pending" />
            <el-option label="进行中" value="ongoing" />
            <el-option label="已完成" value="completed" />
          </el-select>
        </el-form-item>
        <el-form-item label="截止日期" prop="deadline">
          <el-date-picker v-model="taskForm.deadline" type="datetime" placeholder="选择截止日期" style="width: 100%" />
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="dialogVisible = false">取消</el-button>
          <el-button type="primary" @click="saveTask">保存</el-button>
        </span>
      </template>
    </el-dialog>

    <!-- 确认删除对话框 -->
    <CustomConfirmDialog
      v-model:visible="confirmDialogVisible"
      message="确定要删除这个任务吗？"
      @confirm="deleteTask"
    />
  </div>
</template>

<script>
import { ref, onMounted, computed, nextTick } from 'vue'
import { ElMessage } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import CustomConfirmDialog from '../components/CustomConfirmDialog.vue'
import axios from 'axios'

export default {
  name: 'Tasks',
  components: {
    CustomConfirmDialog,
    Plus
  },
  setup() {
    const tasks = ref([])
    const plans = ref([])
    const users = ref([])
    const dialogVisible = ref(false)
    const confirmDialogVisible = ref(false)
    const dialogTitle = ref('创建评估任务')
    const taskFormRef = ref(null)
    const taskForm = ref({
      id: null,
      plan_id: '',
      evaluator_ids: [],
      evaluatee_ids: [],
      status: 'pending',
      deadline: ''
    })
    let deleteTaskId = ref(null)

    // 状态中文映射
    const statusMap = {
      pending: '待开始',
      ongoing: '进行中',
      completed: '已完成'
    }

    // 格式化日期时间为中文显示
    const formatDateTime = (dateString) => {
      if (!dateString) return '-'
      const date = new Date(dateString)
      const year = date.getFullYear()
      const month = String(date.getMonth() + 1).padStart(2, '0')
      const day = String(date.getDate()).padStart(2, '0')
      const hours = String(date.getHours()).padStart(2, '0')
      const minutes = String(date.getMinutes()).padStart(2, '0')
      const seconds = String(date.getSeconds()).padStart(2, '0')
      return `${year}年${month}月${day}日 ${hours}:${minutes}:${seconds}`
    }

    // 计算属性：格式化后的任务列表
    const formattedTasks = computed(() => {
      return tasks.value.map((task) => ({
        ...task,
        formattedStatus: statusMap[task.status] || task.status,
        formattedDeadline: formatDateTime(task.deadline)
      }))
    })

    // 计算属性：根据操作类型过滤计划
    const filteredPlans = computed(() => {
      // 编辑任务时显示所有计划，创建任务时只显示进行中的计划
      console.log('当前对话框标题:', dialogTitle.value)
      console.log('所有计划:', plans.value)
      if (dialogTitle.value === '编辑评估任务') {
        console.log('编辑任务时显示所有计划:', plans.value)
        return plans.value
      } else {
        const ongoingPlans = plans.value.filter(p => p.status === 'ongoing')
        console.log('创建任务时显示进行中的计划:', ongoingPlans)
        return ongoingPlans
      }
    })

    // 计算属性：构建用户树状结构
    const userTree = computed(() => {
      // 按公司角色分组用户
      const roleMap = {}
      if (Array.isArray(users.value)) {
        users.value.forEach(user => {
          const role = user.company_role || '其他'
          if (!roleMap[role]) {
            roleMap[role] = []
          }
          roleMap[role].push(user)
        })
      }
      
      // 构建树状结构
      return Object.entries(roleMap).map(([role, userList]) => ({
        label: role,
        value: role,
        children: userList.map(user => ({
          label: user.name,
          value: user.id
        }))
      }))
    })

    const taskRules = {
      plan_id: [
        { required: true, message: '请选择评估计划', trigger: 'change' }
      ],
      evaluator_ids: [
        { required: true, message: '请选择评估者', trigger: 'change' }
      ],
      evaluatee_ids: [
        { required: true, message: '请选择被评估者', trigger: 'change' }
      ],
      status: [
        { required: true, message: '请选择状态', trigger: 'change' }
      ],
      deadline: [
        { required: true, message: '请选择截止日期', trigger: 'change' }
      ]
    }

    const loadTasks = async () => {
      try {
        const response = await axios.get('/api/tasks', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        console.log('后端返回的任务数据:', response.data)
        tasks.value = response.data
      } catch (error) {
        console.error('Failed to load tasks:', error)
      }
    }

    const loadPlans = async () => {
      try {
        const response = await axios.get('/api/plans', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        plans.value = response.data
      } catch (error) {
        console.error('Failed to load plans:', error)
      }
    }

    const loadUsers = async () => {
      try {
        const response = await axios.get('/api/users', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        // 确保 users.value 是数组
        if (Array.isArray(response.data)) {
          users.value = response.data
        } else if (response.data && response.data.data && Array.isArray(response.data.data)) {
          users.value = response.data.data
        } else {
          users.value = []
        }
        console.log('加载的用户数据:', users.value)
        console.log('构建的用户树:', userTree.value)
      } catch (error) {
        console.error('Failed to load users:', error)
        users.value = []
      }
    }

    const saveTask = async () => {
      if (!taskFormRef.value) return
      
      taskFormRef.value.validate((valid) => {
        if (valid) {
          // 检查评估任务状态，如果是进行中则不允许编辑
          const existingTask = tasks.value.find(t => t.id === taskForm.value.id)
          if (existingTask && existingTask.status === 'ongoing') {
            ElMessage.warning('进行中的任务不允许编辑保存')
            return
          }
          
          // 检查评估计划状态，如果是进行中则不允许编辑任务状态为进行中
          const selectedPlan = plans.value.find(p => p.id === taskForm.value.plan_id)
          if (existingTask && selectedPlan && selectedPlan.status !== 'ongoing' && taskForm.value.status === 'ongoing') {
            ElMessage.warning('评估计划的状态为待进行或已结束，无法开启任务')
            return
          }
          
          // 检查评估任务的截止日期是否晚于评估计划的结束日期
          if (selectedPlan && taskForm.value.deadline) {
            const taskDeadline = new Date(taskForm.value.deadline)
            const planEndDate = new Date(selectedPlan.end_date)
            if (taskDeadline > planEndDate) {
              ElMessage.warning('评估任务的截止日期不得晚于评估计划的结束日期')
              return
            }
          }
          
          // 处理级联选择器返回的数组格式，只提取用户ID
          const processCascaderValues = (values) => {
            return values.map(value => {
              // 如果是数组，取最后一个元素作为用户ID
              if (Array.isArray(value)) {
                return value[value.length - 1]
              }
              return value
            })
          }
          
          const token = localStorage.getItem('token')
          console.log('保存任务数据:', taskForm.value)
          console.log('使用的token:', token)
          
          // 处理评估者和被评估者ID
          const processedEvaluatorIds = processCascaderValues(taskForm.value.evaluator_ids)
          const processedEvaluateeIds = processCascaderValues(taskForm.value.evaluatee_ids)
          
          // 构建请求数据
          const requestData = {
            ...taskForm.value,
            evaluator_ids: processedEvaluatorIds,
            evaluatee_ids: processedEvaluateeIds
          }
          console.log('处理后的数据:', requestData)
          
          let request
          if (taskForm.value.id) {
            // 编辑任务
            console.log('编辑任务，ID:', taskForm.value.id)
            request = axios.put(`/api/tasks/${taskForm.value.id}`, requestData, {
              headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
              }
            })
          } else {
            // 创建任务
            console.log('创建新任务')
            request = axios.post('/api/tasks', requestData, {
              headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
              }
            })
          }
          
          request.then(response => {
            console.log('保存成功，响应:', response.data)
            ElMessage.success('保存成功')
            dialogVisible.value = false
            loadTasks()
            resetForm()
          }).catch(error => {
            console.error('保存失败，错误:', error)
            console.error('错误响应:', error.response)
            console.error('错误状态:', error.response?.status)
            console.error('错误数据:', error.response?.data)
            const errorMessage = error.response?.data?.error || '保存失败，请重试'
            ElMessage.error(errorMessage)
          })
        }
      })
    }

    const editTask = async (task) => {
      // 检查任务状态，如果是进行中则不允许编辑
      if (task.status === 'ongoing') {
        ElMessage.warning('进行中的任务不允许编辑')
        return
      }
      
      console.log('开始编辑任务:', task)
      
      // 先设置对话框标题，确保filteredPlans计算属性能够正确工作
      console.log('设置对话框标题为: 编辑评估任务')
      dialogTitle.value = '编辑评估任务'
      
      // 确保计划和用户数据已加载
      console.log('加载计划数据...')
      await loadPlans()
      console.log('计划数据加载完成:', plans.value)
      
      console.log('加载用户数据...')
      await loadUsers()
      console.log('用户数据加载完成:', users.value)
      
      // 构建任务表单数据，确保下拉选择框能够正确显示
      console.log('构建任务表单数据:', task)
      // 确保ID值为数字类型，与options中的ID类型匹配
      taskForm.value = {
        id: task.id,
        plan_id: Number(task.plan_id),
        evaluator_ids: task.evaluators ? task.evaluators.map(e => Number(e.id)) : [],
        evaluatee_ids: task.evaluatees ? task.evaluatees.map(e => Number(e.id)) : [],
        status: task.status,
        deadline: task.deadline
      }
      console.log('任务表单数据:', taskForm.value)
      
      // 检查选择器选项数据
      console.log('计划选项数据:', plans.value)
      console.log('用户选项数据:', users.value)
      
      // 检查是否能找到对应的选项
      const selectedPlan = plans.value.find(p => p.id === task.plan_id)
      const selectedEvaluator = users.value.find(u => u.id === task.evaluator_id)
      const selectedEvaluatee = users.value.find(u => u.id === task.evaluatee_id)
      console.log('找到的计划:', selectedPlan)
      console.log('找到的评估者:', selectedEvaluator)
      console.log('找到的被评估者:', selectedEvaluatee)
      
      // 确保数据加载完成后再显示对话框
      console.log('显示对话框')
      dialogVisible.value = true
      
      // 使用nextTick确保选择器能够正确更新显示
      nextTick(() => {
        console.log('对话框已显示，选择器应该已更新')
        console.log('当前任务表单数据:', taskForm.value)
      })
    }

    const confirmDeleteTask = (id) => {
      deleteTaskId.value = id
      confirmDialogVisible.value = true
    }

    const deleteTask = async () => {
      try {
        await axios.delete(`/api/tasks/${deleteTaskId.value}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        ElMessage.success('删除成功')
        loadTasks()
      } catch (error) {
        console.error('Failed to delete task:', error)
        ElMessage.error('删除失败，请重试')
      }
    }

    const updateTaskStatus = async (id, status) => {
      // 当要开启任务时，检查评估计划的状态
      if (status === 'ongoing') {
        try {
          // 获取任务详情
          const taskResponse = await axios.get(`/api/tasks/${id}`, {
            headers: {
              'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
          })
          const task = taskResponse.data
          
          // 获取评估计划详情
          const planResponse = await axios.get(`/api/plans/${task.plan_id}`, {
            headers: {
              'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
          })
          const plan = planResponse.data
          
          // 检查评估计划的状态
          if (plan.status !== 'ongoing') {
            ElMessage.warning('评估计划的状态为待进行或已结束，无法开启任务')
            return
          }
        } catch (error) {
          console.error('Failed to check plan status:', error)
          ElMessage.error('检查评估计划状态失败，请重试')
          return
        }
      }
      
      try {
        await axios.put(`/api/tasks/${id}`, { status }, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        loadTasks()
      } catch (error) {
        console.error('Failed to update task status:', error)
        ElMessage.error('状态更新失败，请重试')
      }
    }

    const resetForm = () => {
      taskForm.value = {
        id: null,
        plan_id: '',
        evaluator_ids: [],
        evaluatee_ids: [],
        status: 'pending',
        deadline: ''
      }
      dialogTitle.value = '创建评估任务'
    }

    const openCreateTaskDialog = () => {
      // 重置表单数据
      resetForm()
      // 显示对话框
      dialogVisible.value = true
    }

    onMounted(() => {
      loadTasks()
      loadPlans()
      loadUsers()
    })

    return {
      tasks,
      formattedTasks,
      plans,
      users,
      userTree,
      filteredPlans,
      dialogVisible,
      confirmDialogVisible,
      dialogTitle,
      taskForm,
      taskRules,
      taskFormRef,
      loadTasks,
      saveTask,
      editTask,
      deleteTask,
      confirmDeleteTask,
      updateTaskStatus,
      openCreateTaskDialog
    }
  }
}
</script>

<style scoped>
.tasks-container {
  padding: 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.card-header h2 {
  margin: 0;
  font-size: 18px;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

/* 禁用按钮样式 - 确保禁用状态自动显示灰色 */
:deep(.el-button.is-disabled),
:deep(.el-button.is-disabled:hover),
:deep(.el-button.is-disabled:focus) {
  cursor: not-allowed;
  opacity: 0.6;
}

:deep(.el-button--primary.is-disabled),
:deep(.el-button--primary.is-disabled:hover),
:deep(.el-button--primary.is-disabled:focus) {
  background-color: #a0cfff !important;
  border-color: #a0cfff !important;
  color: #ffffff !important;
}

:deep(.el-button--warning.is-disabled),
:deep(.el-button--warning.is-disabled:hover),
:deep(.el-button--warning.is-disabled:focus) {
  background-color: #f3d19e !important;
  border-color: #f3d19e !important;
  color: #ffffff !important;
}

:deep(.el-button--danger.is-disabled),
:deep(.el-button--danger.is-disabled:hover),
:deep(.el-button--danger.is-disabled:focus) {
  background-color: #fab6b6 !important;
  border-color: #fab6b6 !important;
  color: #ffffff !important;
}
</style>