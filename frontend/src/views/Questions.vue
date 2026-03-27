<template>
  <div class="questions-container">
    <div class="page-header">
      <h2 class="page-title">评估问题管理</h2>
      <el-button type="primary" @click="openCreateDialog">
        <el-icon><Plus /></el-icon>
        创建问题
      </el-button>
    </div>
    
    <el-card class="content-card">
      <el-table :data="questions" style="width: 100%">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column label="所属维度" width="200">
          <template #default="scope">
            {{ getDimensionDisplay(scope.row.dimension) }}
          </template>
        </el-table-column>
        <el-table-column prop="content" label="问题内容" />
        <el-table-column prop="type" label="类型" width="100" />
        <el-table-column label="操作" width="150">
          <template #default="scope">
            <el-button size="small" @click="editQuestion(scope.row)">编辑</el-button>
            <el-button size="small" type="danger" @click="deleteQuestion(scope.row.id)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <!-- 创建/编辑问题对话框 -->
    <el-dialog v-model="dialogVisible" :title="dialogTitle">
      <el-form :model="questionForm" :rules="questionRules" ref="questionFormRef" label-width="100px">
        <el-form-item label="所属维度" prop="dimension_id">
          <el-select v-model="questionForm.dimension_id" placeholder="选择维度" style="width: 100%">
            <el-option 
              v-for="dimension in dimensions" 
              :key="dimension.id" 
              :label="getDimensionLabel(dimension)" 
              :value="dimension.id" 
            />
          </el-select>
        </el-form-item>
        <el-form-item label="问题内容" prop="content">
          <el-input v-model="questionForm.content" type="textarea" placeholder="请输入问题内容" />
        </el-form-item>
        <el-form-item label="类型" prop="type">
          <el-select v-model="questionForm.type" placeholder="选择类型">
            <el-option label="单选题" value="single" />
            <el-option label="多选题" value="multiple" />
            <el-option label="简答题" value="text" />
          </el-select>
        </el-form-item>
        <el-form-item label="评分标准" prop="scoring_criteria">
          <el-input v-model="questionForm.scoring_criteria" type="textarea" placeholder="请输入评分标准" />
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="dialogVisible = false">取消</el-button>
          <el-button type="primary" @click="saveQuestion">保存</el-button>
        </span>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Plus } from '@element-plus/icons-vue'
import axios from 'axios'

export default {
  name: 'Questions',
  components: {
    Plus
  },
  setup() {
    const questions = ref([])
    const dimensions = ref([])
    const dialogVisible = ref(false)
    const dialogTitle = ref('创建评估问题')
    const questionFormRef = ref(null)
    const questionForm = ref({
      id: null,
      dimension_id: '',
      content: '',
      type: 'single',
      scoring_criteria: ''
    })

    const questionRules = {
      dimension_id: [
        { required: true, message: '请选择所属维度', trigger: 'change' }
      ],
      content: [
        { required: true, message: '请输入问题内容', trigger: 'blur' }
      ],
      type: [
        { required: true, message: '请选择类型', trigger: 'change' }
      ]
    }

    const loadQuestions = async () => {
      try {
        const response = await axios.get('/api/questions', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        questions.value = response.data
      } catch (error) {
        console.error('Failed to load questions:', error)
      }
    }

    const loadDimensions = async () => {
      try {
        const response = await axios.get('/api/dimensions', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        dimensions.value = response.data
      } catch (error) {
        console.error('Failed to load dimensions:', error)
      }
    }

    const getDimensionLabel = (dimension) => {
      let roleName = '未知角色'
      if (dimension.target_role && typeof dimension.target_role === 'object' && dimension.target_role.name) {
        roleName = dimension.target_role.name
      } else if (dimension.targetRole && dimension.targetRole.name) {
        roleName = dimension.targetRole.name
      } else if (dimension.target_role && typeof dimension.target_role === 'string') {
        roleName = dimension.target_role
      }
      return `${roleName}-${dimension.name}`
    }

    const getDimensionDisplay = (dimension) => {
      if (!dimension) return '-'
      let roleName = '未知角色'
      if (dimension.target_role && typeof dimension.target_role === 'object' && dimension.target_role.name) {
        roleName = dimension.target_role.name
      } else if (dimension.targetRole && dimension.targetRole.name) {
        roleName = dimension.targetRole.name
      } else if (dimension.target_role && typeof dimension.target_role === 'string') {
        roleName = dimension.target_role
      }
      return `${roleName}-${dimension.name}`
    }

    const saveQuestion = async () => {
      if (!questionFormRef.value) return
      
      await questionFormRef.value.validate(async (valid) => {
        if (valid) {
          try {
            if (questionForm.value.id) {
              // 编辑问题
              await axios.put(`/api/questions/${questionForm.value.id}`, questionForm.value, {
                headers: {
                  'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
              })
            } else {
              // 创建问题
              await axios.post('/api/questions', questionForm.value, {
                headers: {
                  'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
              })
            }
            dialogVisible.value = false
            loadQuestions()
            resetForm()
          } catch (error) {
            console.error('Failed to save question:', error)
            ElMessage.error('保存失败，请重试')
          }
        }
      })
    }

    const editQuestion = (question) => {
      questionForm.value = { ...question }
      dialogTitle.value = '编辑评估问题'
      dialogVisible.value = true
    }

    const openCreateDialog = () => {
      resetForm()
      dialogVisible.value = true
    }

    const deleteQuestion = async (id) => {
      if (confirm('确定要删除这个问题吗？')) {
        try {
          await axios.delete(`/api/questions/${id}`, {
            headers: {
              'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
          })
          loadQuestions()
        } catch (error) {
          console.error('Failed to delete question:', error)
          ElMessage.error('删除失败，请重试')
        }
      }
    }

    const resetForm = () => {
      questionForm.value = {
        id: null,
        dimension_id: '',
        content: '',
        type: 'single',
        scoring_criteria: ''
      }
      dialogTitle.value = '创建评估问题'
    }

    onMounted(() => {
      loadQuestions()
      loadDimensions()
    })

    return {
      questions,
      dimensions,
      dialogVisible,
      dialogTitle,
      questionForm,
      questionRules,
      questionFormRef,
      loadQuestions,
      saveQuestion,
      editQuestion,
      openCreateDialog,
      deleteQuestion,
      getDimensionLabel,
      getDimensionDisplay
    }
  }
}
</script>

<style scoped>
.questions-container {
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

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: var(--spacing-sm);
}
</style>