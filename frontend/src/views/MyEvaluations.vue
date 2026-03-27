<template>
  <div class="my-evaluations-container">
    <div class="page-header">
      <h2 class="page-title">我的评估</h2>
    </div>

    <el-tabs v-model="activeTab" class="evaluation-tabs">
      <el-tab-pane label="待评估" name="pending">
        <div v-if="pendingTasks.length === 0" class="empty-state">
          <el-empty description="暂无待评估任务" />
        </div>
        <div v-else class="task-list">
          <el-card v-for="task in pendingTasks" :key="task.id" class="task-card">
            <div class="task-header">
              <div class="task-info">
                <h3 class="task-title">{{ task.plan?.name || '评估任务' }}</h3>
                <el-tag :type="getStatusType(task.status)" size="small">
                  {{ getStatusText(task.status) }}
                </el-tag>
              </div>
              <div class="task-deadline">
                <el-icon><Clock /></el-icon>
                <span>截止日期：{{ formatDateTime(task.deadline) }}</span>
              </div>
            </div>
            <div class="task-content">
              <div class="evaluatee-info">
                <span class="label">被评估者：</span>
                <div class="evaluatee-list">
                  <el-tag 
                    v-for="evaluatee in task.evaluatees" 
                    :key="evaluatee.id"
                    class="evaluatee-tag"
                  >
                    {{ evaluatee.name }} ({{ evaluatee.company_role || '未设置角色' }})
                  </el-tag>
                </div>
              </div>
              <div class="progress-info">
                <span class="label">评估进度：</span>
                <el-progress 
                  :percentage="getTaskProgress(task)" 
                  :status="getTaskProgress(task) === 100 ? 'success' : ''"
                />
              </div>
            </div>
            <div class="task-actions">
              <el-button 
                type="primary" 
                @click="startEvaluation(task)"
                :disabled="task.status !== 'ongoing'"
              >
                <el-icon><Edit /></el-icon>
                {{ getTaskProgress(task) > 0 ? '继续评估' : '开始评估' }}
              </el-button>
            </div>
          </el-card>
        </div>
      </el-tab-pane>

      <el-tab-pane label="已完成" name="completed">
        <div v-if="completedTasks.length === 0" class="empty-state">
          <el-empty description="暂无已完成评估" />
        </div>
        <div v-else class="task-list">
          <el-card v-for="task in completedTasks" :key="task.id" class="task-card completed">
            <div class="task-header">
              <div class="task-info">
                <h3 class="task-title">{{ task.plan?.name || '评估任务' }}</h3>
                <el-tag type="success" size="small">已完成</el-tag>
              </div>
              <div class="task-complete-time">
                <el-icon><Check /></el-icon>
                <span>完成时间：{{ formatDateTime(task.completed_at) }}</span>
              </div>
            </div>
            <div class="task-content">
              <div class="evaluatee-info">
                <span class="label">被评估者：</span>
                <div class="evaluatee-list">
                  <el-tag 
                    v-for="evaluatee in task.evaluatees" 
                    :key="evaluatee.id"
                    class="evaluatee-tag"
                    type="success"
                  >
                    {{ evaluatee.name }} ({{ evaluatee.company_role || '未设置角色' }})
                  </el-tag>
                </div>
              </div>
            </div>
            <div class="task-actions">
              <el-button @click="viewEvaluationResult(task)">
                <el-icon><View /></el-icon>
                查看结果
              </el-button>
            </div>
          </el-card>
        </div>
      </el-tab-pane>
    </el-tabs>

    <el-dialog 
      v-model="evaluationDialogVisible" 
      :title="evaluationDialogTitle"
      width="900px"
      :close-on-click-modal="false"
    >
      <div v-if="currentTask" class="evaluation-form">
        <div class="evaluatee-selector">
          <span class="label">选择被评估者：</span>
          <el-select 
            v-model="currentEvaluateeId" 
            placeholder="请选择被评估者"
            @change="loadEvaluationQuestions"
          >
            <el-option 
              v-for="evaluatee in currentTask.evaluatees" 
              :key="evaluatee.id"
              :label="`${evaluatee.name} (${evaluatee.company_role || '未设置角色'})`"
              :value="evaluatee.id"
            />
          </el-select>
        </div>

        <div v-if="currentEvaluateeId && questions.length > 0" class="questions-section">
          <div class="questions-header">
            <h4>评估问题</h4>
            <div class="progress-indicator">
              已完成：{{ answeredCount }} / {{ questions.length }}
            </div>
          </div>

          <el-form :model="evaluationForm" ref="evaluationFormRef" label-position="top">
            <div 
              v-for="(question, index) in questions" 
              :key="question.id"
              class="question-item"
              :class="{ 'answered': evaluationForm.answers[question.id] }"
            >
              <div class="question-header">
                <span class="question-number">{{ index + 1 }}.</span>
                <span class="question-text">{{ question.content }}</span>
                <el-tag size="small" type="info">{{ question.dimension?.name }}</el-tag>
              </div>
              <div class="question-options">
                <el-rate 
                  v-model="evaluationForm.answers[question.id].score"
                  :max="100"
                  :allow-half="true"
                  show-score
                  :score-template="evaluationForm.answers[question.id].score + '分'"
                />
              </div>
              <div class="question-comment">
                <el-input 
                  v-model="evaluationForm.answers[question.id].comment"
                  type="textarea"
                  placeholder="请输入评语（选填）"
                  :rows="2"
                />
              </div>
            </div>
          </el-form>
        </div>

        <div v-else-if="currentEvaluateeId" class="empty-questions">
          <el-empty description="暂无评估问题" />
        </div>
      </div>

      <template #footer>
        <span class="dialog-footer">
          <el-button @click="evaluationDialogVisible = false">取消</el-button>
          <el-button 
            type="primary" 
            @click="submitEvaluation"
            :loading="submitting"
            :disabled="!canSubmit"
          >
            提交评估
          </el-button>
        </span>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Clock, Edit, Check, View } from '@element-plus/icons-vue'
import axios from 'axios'

export default {
  name: 'MyEvaluations',
  components: {
    Clock,
    Edit,
    Check,
    View
  },
  setup() {
    const activeTab = ref('pending')
    const tasks = ref([])
    const currentTask = ref(null)
    const currentEvaluateeId = ref(null)
    const questions = ref([])
    const evaluationDialogVisible = ref(false)
    const submitting = ref(false)
    const evaluationFormRef = ref(null)
    const evaluationForm = ref({
      answers: {}
    })

    const pendingTasks = computed(() => {
      return tasks.value.filter(task => 
        task.status === 'ongoing' && !isTaskCompleted(task)
      )
    })

    const completedTasks = computed(() => {
      return tasks.value.filter(task => 
        task.status === 'completed' || isTaskCompleted(task)
      )
    })

    const evaluationDialogTitle = computed(() => {
      if (currentTask.value) {
        return `评估任务：${currentTask.value.plan?.name || '评估'}`
      }
      return '评估'
    })

    const answeredCount = computed(() => {
      return Object.values(evaluationForm.value.answers).filter(a => a.score > 0).length
    })

    const canSubmit = computed(() => {
      return currentEvaluateeId.value && 
             questions.value.length > 0 && 
             answeredCount.value === questions.value.length
    })

    const isTaskCompleted = (task) => {
      return task.answers && task.answers.some(a => a.evaluator_id === getCurrentUserId())
    }

    const getCurrentUserId = () => {
      const user = JSON.parse(localStorage.getItem('user') || '{}')
      return user.id
    }

    const getStatusType = (status) => {
      const types = {
        pending: 'info',
        ongoing: 'warning',
        completed: 'success'
      }
      return types[status] || 'info'
    }

    const getStatusText = (status) => {
      const texts = {
        pending: '待开始',
        ongoing: '进行中',
        completed: '已完成'
      }
      return texts[status] || status
    }

    const formatDateTime = (dateString) => {
      if (!dateString) return '-'
      const date = new Date(dateString)
      return date.toLocaleString('zh-CN', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
      })
    }

    const getTaskProgress = (task) => {
      if (!task.evaluatees || task.evaluatees.length === 0) return 0
      
      const currentUserId = getCurrentUserId()
      const totalEvaluatees = task.evaluatees.length
      let completedCount = 0

      if (task.answers) {
        const evaluatedEvaluatees = new Set()
        task.answers.forEach(answer => {
          if (answer.evaluator_id === currentUserId) {
            evaluatedEvaluatees.add(answer.evaluatee_id)
          }
        })
        completedCount = evaluatedEvaluatees.size
      }

      return Math.round((completedCount / totalEvaluatees) * 100)
    }

    const loadTasks = async () => {
      try {
        const currentUserId = getCurrentUserId()
        const response = await axios.get('/api/tasks', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        
        tasks.value = response.data.filter(task => 
          task.evaluators && task.evaluators.some(e => e.id === currentUserId)
        )
      } catch (error) {
        console.error('Failed to load tasks:', error)
        ElMessage.error('加载评估任务失败')
      }
    }

    const startEvaluation = (task) => {
      currentTask.value = task
      currentEvaluateeId.value = null
      questions.value = []
      evaluationForm.value = { answers: {} }
      evaluationDialogVisible.value = true
    }

    const loadEvaluationQuestions = async () => {
      if (!currentEvaluateeId.value) {
        questions.value = []
        return
      }

      try {
        // 获取被评估者信息
        const evaluatee = currentTask.value.evaluatees.find(e => e.id === currentEvaluateeId.value)
        if (!evaluatee) {
          ElMessage.error('未找到被评估者信息')
          return
        }

        // 获取被评估者的角色ID
        const userResponse = await axios.get(`/api/users/${currentEvaluateeId.value}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        
        const userRoles = userResponse.data.roles
        if (!userRoles || userRoles.length === 0) {
          ElMessage.warning('该用户未设置角色，无法加载评估问题')
          questions.value = []
          return
        }

        const roleId = userRoles[0].id

        // 根据角色ID获取评估问题
        const response = await axios.get(`/api/questions/by-role/${roleId}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        
        questions.value = response.data
        
        evaluationForm.value.answers = {}
        questions.value.forEach(q => {
          evaluationForm.value.answers[q.id] = {
            score: 0,
            comment: ''
          }
        })
      } catch (error) {
        console.error('Failed to load questions:', error)
        ElMessage.error('加载评估问题失败')
      }
    }

    const submitEvaluation = async () => {
      if (!canSubmit.value) {
        ElMessage.warning('请完成所有问题的评分')
        return
      }

      submitting.value = true

      try {
        const currentUserId = getCurrentUserId()
        const answers = Object.entries(evaluationForm.value.answers).map(([questionId, answer]) => ({
          task_id: currentTask.value.id,
          question_id: parseInt(questionId),
          evaluator_id: currentUserId,
          evaluatee_id: currentEvaluateeId.value,
          score: answer.score,
          comment: answer.comment
        }))

        for (const answer of answers) {
          await axios.post('/api/answers', answer, {
            headers: {
              'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
          })
        }

        ElMessage.success('评估提交成功')
        evaluationDialogVisible.value = false
        loadTasks()
      } catch (error) {
        console.error('Failed to submit evaluation:', error)
        ElMessage.error('提交评估失败')
      } finally {
        submitting.value = false
      }
    }

    const viewEvaluationResult = (task) => {
      ElMessage.info('评估结果查看功能开发中')
    }

    onMounted(() => {
      loadTasks()
    })

    return {
      activeTab,
      tasks,
      pendingTasks,
      completedTasks,
      currentTask,
      currentEvaluateeId,
      questions,
      evaluationDialogVisible,
      evaluationDialogTitle,
      evaluationForm,
      evaluationFormRef,
      submitting,
      answeredCount,
      canSubmit,
      getStatusType,
      getStatusText,
      formatDateTime,
      getTaskProgress,
      startEvaluation,
      loadEvaluationQuestions,
      submitEvaluation,
      viewEvaluationResult
    }
  }
}
</script>

<style scoped>
.my-evaluations-container {
  padding: 20px;
}

.page-header {
  margin-bottom: 20px;
}

.page-title {
  margin: 0;
  font-size: 24px;
  font-weight: 600;
}

.evaluation-tabs {
  background: #fff;
  border-radius: 8px;
  padding: 20px;
}

.empty-state {
  padding: 40px 0;
  text-align: center;
}

.task-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.task-card {
  border-radius: 8px;
}

.task-card.completed {
  opacity: 0.8;
}

.task-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 16px;
}

.task-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.task-title {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
}

.task-deadline,
.task-complete-time {
  display: flex;
  align-items: center;
  gap: 6px;
  color: #909399;
  font-size: 14px;
}

.task-content {
  margin-bottom: 16px;
}

.evaluatee-info {
  margin-bottom: 12px;
}

.label {
  font-weight: 500;
  color: #606266;
  margin-right: 8px;
}

.evaluatee-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 8px;
}

.evaluatee-tag {
  margin-right: 0;
}

.progress-info {
  margin-top: 12px;
}

.task-actions {
  display: flex;
  justify-content: flex-end;
}

.evaluation-form {
  padding: 10px 0;
}

.evaluatee-selector {
  margin-bottom: 20px;
  padding: 16px;
  background: #f5f7fa;
  border-radius: 8px;
}

.questions-section {
  margin-top: 20px;
}

.questions-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 1px solid #ebeef5;
}

.questions-header h4 {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
}

.progress-indicator {
  color: #909399;
  font-size: 14px;
}

.question-item {
  padding: 16px;
  margin-bottom: 16px;
  border: 1px solid #ebeef5;
  border-radius: 8px;
  transition: all 0.3s;
}

.question-item.answered {
  border-color: #67c23a;
  background-color: #f0f9eb;
}

.question-header {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  margin-bottom: 12px;
}

.question-number {
  font-weight: 600;
  color: #409eff;
}

.question-text {
  flex: 1;
  font-size: 15px;
  line-height: 1.6;
}

.question-options {
  margin: 12px 0;
}

.question-comment {
  margin-top: 12px;
}

.empty-questions {
  padding: 40px 0;
  text-align: center;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}
</style>
