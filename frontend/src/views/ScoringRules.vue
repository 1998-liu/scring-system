<template>
  <div class="scoring-rules-container">
    <div class="page-header">
      <h2 class="page-title">评分规则管理</h2>
      <el-button type="primary" @click="openCreateDialog">
        <el-icon><Plus /></el-icon>
        新增规则
      </el-button>
    </div>

    <el-card class="content-card">
      <el-table :data="rules" style="width: 100%">
        <el-table-column prop="name" label="规则名称" width="200" />
        <el-table-column label="目标角色" width="150">
          <template #default="scope">
            {{ scope.row.target_role_name || getRoleName(scope.row.target_role_id) }}
          </template>
        </el-table-column>
        <el-table-column label="权重配置" min-width="300">
          <template #default="scope">
            <div class="weight-list">
              <el-tag 
                v-for="weight in scope.row.weights" 
                :key="weight.id" 
                class="weight-tag"
                type="info"
              >
                {{ weight.evaluator_role_name || getRoleName(weight.evaluator_role_id) }}: {{ weight.weight }}%
              </el-tag>
            </div>
          </template>
        </el-table-column>
        <el-table-column prop="description" label="描述" min-width="200" />
        <el-table-column label="操作" width="200" fixed="right">
          <template #default="scope">
            <el-button size="small" @click="editRule(scope.row)">编辑</el-button>
            <el-button size="small" type="info" @click="viewHistory(scope.row)">历史</el-button>
            <el-button size="small" type="danger" @click="deleteRule(scope.row.id)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-dialog v-model="dialogVisible" :title="dialogTitle" width="600px">
      <el-form :model="ruleForm" :rules="ruleRules" ref="ruleFormRef" label-width="100px">
        <el-form-item label="规则名称" prop="name">
          <el-input v-model="ruleForm.name" placeholder="请输入规则名称" />
        </el-form-item>
        <el-form-item label="目标角色" prop="target_role_id">
          <el-select v-model="ruleForm.target_role_id" placeholder="选择目标角色" style="width: 100%">
            <el-option 
              v-for="role in roles" 
              :key="role.id" 
              :label="role.name" 
              :value="role.id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="描述" prop="description">
          <el-input v-model="ruleForm.description" type="textarea" placeholder="请输入描述" />
        </el-form-item>
        <el-form-item label="权重配置" required>
          <div class="weight-config">
            <div v-for="(weight, index) in ruleForm.weights" :key="index" class="weight-item">
              <el-select v-model="weight.evaluator_role_id" placeholder="选择评议角色" style="width: 200px">
                <el-option 
                  v-for="role in roles" 
                  :key="role.id" 
                  :label="role.name" 
                  :value="role.id"
                />
              </el-select>
              <el-input-number 
                v-model="weight.weight" 
                :min="0" 
                :max="100" 
                :precision="2"
                placeholder="权重%"
                style="width: 120px; margin-left: 10px"
              />
              <el-button 
                type="danger" 
                :icon="Delete" 
                circle 
                @click="removeWeight(index)"
                style="margin-left: 10px"
              />
            </div>
            <div class="weight-summary">
              <span>权重总和: </span>
              <el-tag :type="totalWeight === 100 ? 'success' : 'danger'">
                {{ totalWeight }}%
              </el-tag>
              <span v-if="totalWeight !== 100" class="weight-warning">
                (权重总和必须等于100%)
              </span>
            </div>
            <el-button 
              type="success" 
              class="add-weight-btn"
              :disabled="isAddWeightDisabled"
              @click="addWeight"
            >
              添加权重
            </el-button>
            <span v-if="isAddWeightDisabled" class="disabled-tip">
              (权重已达100%，无法继续添加)
            </span>
          </div>
        </el-form-item>
        <el-form-item label="修改原因" prop="change_reason" v-if="isEdit">
          <el-input v-model="ruleForm.change_reason" type="textarea" placeholder="请输入修改原因" />
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="dialogVisible = false">取消</el-button>
          <el-button type="primary" @click="saveRule">保存</el-button>
        </span>
      </template>
    </el-dialog>

    <el-dialog v-model="historyDialogVisible" title="评分规则历史" width="900px">
      <div v-if="histories.length === 0" class="empty-history">
        <el-empty description="暂无历史记录" />
      </div>
      <el-timeline v-else>
        <el-timeline-item 
          v-for="history in histories" 
          :key="history.id"
          :timestamp="formatDateTime(history.created_at)"
          placement="top"
        >
          <el-card class="history-card">
            <template #header>
              <div class="history-header">
                <span class="history-title">{{ history.name }}</span>
                <el-tag size="small" type="info">{{ history.target_role_name }}</el-tag>
              </div>
            </template>
            <div class="history-content">
              <div class="history-info">
                <div class="info-item">
                  <span class="info-label">修改人：</span>
                  <span class="info-value">{{ history.changed_by?.name || '系统' }}</span>
                </div>
                <div class="info-item">
                  <span class="info-label">修改原因：</span>
                  <span class="info-value">{{ history.change_reason || '无' }}</span>
                </div>
              </div>
              <div class="history-description" v-if="history.description">
                <div class="description-label">规则描述：</div>
                <div class="description-content">{{ history.description }}</div>
              </div>
              <div class="history-weights">
                <div class="weights-label">权重配置：</div>
                <div class="weights-list">
                  <el-tag 
                    v-for="(weight, index) in history.weights" 
                    :key="index"
                    class="weight-tag"
                    type="primary"
                  >
                    {{ weight.evaluator_role_name }}: {{ weight.weight }}%
                  </el-tag>
                </div>
                <div class="weights-summary">
                  <span class="summary-label">权重总和：</span>
                  <el-tag :type="getTotalWeight(history.weights) === 100 ? 'success' : 'danger'">
                    {{ getTotalWeight(history.weights) }}%
                  </el-tag>
                </div>
              </div>
            </div>
          </el-card>
        </el-timeline-item>
      </el-timeline>
    </el-dialog>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Plus, Delete } from '@element-plus/icons-vue'
import axios from 'axios'

export default {
  name: 'ScoringRules',
  components: {
    Plus,
    Delete
  },
  setup() {
    const rules = ref([])
    const roles = ref([])
    const histories = ref([])
    const dialogVisible = ref(false)
    const historyDialogVisible = ref(false)
    const dialogTitle = ref('新增评分规则')
    const isEdit = ref(false)
    const ruleFormRef = ref(null)
    const ruleForm = ref({
      id: null,
      name: '',
      target_role_id: null,
      description: '',
      weights: [{ evaluator_role_id: null, weight: 0 }],
      change_reason: ''
    })

    const ruleRules = {
      name: [{ required: true, message: '请输入规则名称', trigger: 'blur' }],
      target_role_id: [{ required: true, message: '请选择目标角色', trigger: 'change' }]
    }

    const totalWeight = computed(() => {
      return ruleForm.value.weights.reduce((sum, w) => sum + (parseFloat(w.weight) || 0), 0)
    })

    const isAddWeightDisabled = computed(() => {
      return totalWeight.value >= 100
    })

    const getRoleName = (roleId) => {
      const role = roles.value.find(r => r.id === roleId)
      return role ? role.name : '未知角色'
    }

    const formatDateTime = (dateTime) => {
      if (!dateTime) return ''
      const date = new Date(dateTime)
      return date.toLocaleString('zh-CN', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
      })
    }

    const getTotalWeight = (weights) => {
      if (!weights || !Array.isArray(weights)) return 0
      return weights.reduce((sum, w) => sum + (parseFloat(w.weight) || 0), 0)
    }

    const loadRoles = async () => {
      try {
        const response = await axios.get('/api/scoring-rules/roles', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        roles.value = response.data.map(role => ({
          ...role,
          id: parseInt(role.id)
        }))
      } catch (error) {
        console.error('Failed to load roles:', error)
        ElMessage.error('加载角色列表失败')
      }
    }

    const loadRules = async () => {
      try {
        const response = await axios.get('/api/scoring-rules', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        rules.value = response.data.map(rule => ({
          ...rule,
          target_role_id: parseInt(rule.target_role_id),
          weights: rule.weights.map(w => ({
            ...w,
            evaluator_role_id: parseInt(w.evaluator_role_id),
            weight: parseFloat(w.weight)
          }))
        }))
      } catch (error) {
        console.error('Failed to load rules:', error)
        ElMessage.error('加载评分规则失败')
      }
    }

    const openCreateDialog = () => {
      isEdit.value = false
      dialogTitle.value = '新增评分规则'
      ruleForm.value = {
        id: null,
        name: '',
        target_role_id: null,
        description: '',
        weights: [{ evaluator_role_id: null, weight: 0 }],
        change_reason: ''
      }
      dialogVisible.value = true
    }

    const editRule = (rule) => {
      isEdit.value = true
      dialogTitle.value = '编辑评分规则'
      ruleForm.value = {
        id: rule.id,
        name: rule.name,
        target_role_id: parseInt(rule.target_role_id),
        description: rule.description,
        weights: rule.weights.map(w => ({
          evaluator_role_id: parseInt(w.evaluator_role_id),
          weight: parseFloat(w.weight)
        })),
        change_reason: ''
      }
      dialogVisible.value = true
    }

    const addWeight = () => {
      ruleForm.value.weights.push({ evaluator_role_id: null, weight: 0 })
    }

    const removeWeight = (index) => {
      if (ruleForm.value.weights.length > 1) {
        ruleForm.value.weights.splice(index, 1)
      }
    }

    const saveRule = async () => {
      if (!ruleFormRef.value) return

      await ruleFormRef.value.validate(async (valid) => {
        if (valid) {
          if (Math.abs(totalWeight.value - 100) > 0.01) {
            ElMessage.error('权重总和必须等于100%')
            return
          }

          try {
            if (isEdit.value) {
              await axios.put(`/api/scoring-rules/${ruleForm.value.id}`, ruleForm.value, {
                headers: {
                  'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
              })
              ElMessage.success('更新成功')
            } else {
              await axios.post('/api/scoring-rules', ruleForm.value, {
                headers: {
                  'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
              })
              ElMessage.success('创建成功')
            }
            dialogVisible.value = false
            loadRules()
          } catch (error) {
            console.error('Failed to save rule:', error)
            ElMessage.error(error.response?.data?.error || '保存失败')
          }
        }
      })
    }

    const deleteRule = async (id) => {
      try {
        await axios.delete(`/api/scoring-rules/${id}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        ElMessage.success('删除成功')
        loadRules()
      } catch (error) {
        console.error('Failed to delete rule:', error)
        ElMessage.error('删除失败')
      }
    }

    const viewHistory = async (rule) => {
      try {
        const response = await axios.get(`/api/scoring-rules/${rule.id}/history`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        histories.value = response.data
        historyDialogVisible.value = true
      } catch (error) {
        console.error('Failed to load history:', error)
        ElMessage.error('加载历史记录失败')
      }
    }

    onMounted(async () => {
      await loadRoles()
      await loadRules()
    })

    return {
      rules,
      roles,
      histories,
      dialogVisible,
      historyDialogVisible,
      dialogTitle,
      isEdit,
      ruleForm,
      ruleRules,
      ruleFormRef,
      totalWeight,
      isAddWeightDisabled,
      getRoleName,
      formatDateTime,
      getTotalWeight,
      loadRoles,
      loadRules,
      openCreateDialog,
      editRule,
      addWeight,
      removeWeight,
      saveRule,
      deleteRule,
      viewHistory,
      Plus,
      Delete
    }
  }
}
</script>

<style scoped>
.scoring-rules-container {
  animation: fadeIn 0.3s ease-out;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-lg);
  padding-bottom: var(--spacing-md);
  border-bottom: 1px solid var(--border-color);
}

.page-title {
  margin: 0;
  font-size: var(--font-size-2xl);
  font-weight: var(--font-weight-bold);
  color: var(--text-primary);
}

.content-card {
  border-radius: var(--radius-lg);
}

.page-header .el-button {
  border-radius: var(--radius-md);
  transition: all var(--transition-fast);
  min-height: 32px;
  padding: 8px 15px;
  font-weight: var(--font-weight-medium);
}

.page-header .el-button:hover {
  transform: translateY(-1px);
}

.page-header .el-button--primary {
  background-color: var(--el-color-primary);
  border-color: var(--el-color-primary);
  color: #ffffff;
}

.page-header .el-button--primary:hover {
  background-color: var(--el-color-primary-light-3);
  border-color: var(--el-color-primary-light-3);
}

.weight-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.weight-tag {
  margin-right: 8px;
}

.weight-config {
  width: 100%;
}

.weight-item {
  display: flex;
  align-items: center;
  margin-bottom: 10px;
}

.weight-summary {
  margin: 15px 0;
  padding: 10px;
  background-color: #f5f7fa;
  border-radius: 4px;
}

.weight-warning {
  color: #f56c6c;
  margin-left: 10px;
}

.add-weight-btn {
  margin-top: 10px;
}

.disabled-tip {
  color: #909399;
  font-size: 12px;
  margin-left: 10px;
}

.history-weights {
  margin-top: 10px;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.empty-history {
  padding: 40px 0;
  text-align: center;
}

.history-card {
  margin-bottom: 10px;
}

.history-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.history-title {
  font-size: 16px;
  font-weight: 600;
  color: #303133;
}

.history-content {
  padding: 10px 0;
}

.history-info {
  display: flex;
  flex-wrap: wrap;
  gap: 20px;
  margin-bottom: 15px;
}

.info-item {
  display: flex;
  align-items: center;
}

.info-label {
  color: #909399;
  font-size: 14px;
  margin-right: 5px;
}

.info-value {
  color: #606266;
  font-size: 14px;
}

.history-description {
  margin-bottom: 15px;
  padding: 10px;
  background-color: #f5f7fa;
  border-radius: 4px;
}

.description-label {
  color: #909399;
  font-size: 14px;
  margin-bottom: 8px;
}

.description-content {
  color: #606266;
  font-size: 14px;
  line-height: 1.6;
}

.weights-label {
  color: #909399;
  font-size: 14px;
  margin-bottom: 8px;
}

.weights-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.weights-summary {
  margin-top: 10px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.summary-label {
  color: #909399;
  font-size: 14px;
}
</style>
