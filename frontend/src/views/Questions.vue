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
        <el-table-column type="index" label="序号" width="80" :index="indexMethod" />
        <el-table-column label="所属维度" width="200">
          <template #default="scope">
            {{ getDimensionDisplay(scope.row.dimension) }}
          </template>
        </el-table-column>
        <el-table-column prop="content" label="问题内容" />
        <el-table-column label="类型" width="120">
          <template #default="scope">
            {{ getTypeDisplay(scope.row.type) }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="200">
          <template #default="scope">
            <el-button size="small" @click="editQuestion(scope.row)">编辑</el-button>
            <el-button size="small" type="primary" @click="copyQuestion(scope.row)">复制</el-button>
            <el-button size="small" type="danger" @click="deleteQuestion(scope.row.id)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
      
      <div class="pagination-container">
        <el-pagination
          :current-page="pagination.currentPage"
          :page-size="pagination.pageSize"
          :page-sizes="[10, 20, 50, 100]"
          :total="pagination.total"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
        />
      </div>
    </el-card>

    <!-- 创建/编辑问题对话框 -->
    <el-dialog v-model="dialogVisible" :title="dialogTitle" width="600px">
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
          <el-input v-model="questionForm.content" type="textarea" placeholder="请输入问题内容" :rows="3" />
        </el-form-item>
        <el-form-item label="类型" prop="type">
          <el-select v-model="questionForm.type" placeholder="选择类型" style="width: 100%" @change="handleTypeChange">
            <el-option label="分数范围" value="score_range" />
            <el-option label="分数选项" value="score_options" />
          </el-select>
        </el-form-item>
        
        <!-- 分数范围类型的配置 -->
        <template v-if="questionForm.type === 'score_range'">
          <el-form-item label="最低分" prop="min_score">
            <el-input-number 
              v-model="questionForm.min_score" 
              :min="0" 
              :precision="2" 
              placeholder="请输入最低分"
              style="width: 100%"
            />
          </el-form-item>
          <el-form-item label="最高分" prop="max_score">
            <el-input-number 
              v-model="questionForm.max_score" 
              :min="0" 
              :precision="2" 
              placeholder="请输入最高分"
              style="width: 100%"
            />
          </el-form-item>
        </template>
        
        <!-- 分数选项类型的配置 -->
        <template v-if="questionForm.type === 'score_options'">
          <el-form-item label="选项配置">
            <div class="options-container">
              <div v-for="(option, index) in questionForm.options" :key="index" class="option-item">
                <el-input 
                  v-model="option.text" 
                  placeholder="选项文本" 
                  style="width: 200px; margin-right: 10px;"
                />
                <el-input-number 
                  v-model="option.score" 
                  :min="0" 
                  :precision="2" 
                  placeholder="分数"
                  style="width: 120px; margin-right: 10px;"
                />
                <el-button 
                  type="danger" 
                  :icon="Delete" 
                  circle 
                  @click="removeOption(index)"
                  :disabled="questionForm.options.length <= 1"
                />
              </div>
              <el-button type="primary" :icon="Plus" @click="addOption" style="margin-top: 10px;">
                添加选项
              </el-button>
            </div>
          </el-form-item>
        </template>
        
        <el-form-item label="评分标准" prop="scoring_criteria">
          <el-input v-model="questionForm.scoring_criteria" type="textarea" placeholder="请输入评分标准" :rows="3" />
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
import { Plus, Delete } from '@element-plus/icons-vue'
import axios from 'axios'

export default {
  name: 'Questions',
  components: {
    Plus,
    Delete
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
      type: 'score_range',
      min_score: 0,
      max_score: 100,
      options: [{ text: '', score: 0 }],
      scoring_criteria: ''
    })

    const pagination = ref({
      currentPage: 1,
      pageSize: 10,
      total: 0
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
          params: {
            page: pagination.value.currentPage,
            page_size: pagination.value.pageSize
          },
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        questions.value = response.data.data
        pagination.value.total = response.data.total
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

    const getTypeDisplay = (type) => {
      const typeMap = {
        'score_range': '分数范围',
        'score_options': '分数选项',
        'single': '单选题',
        'multiple': '多选题',
        'text': '简答题'
      }
      return typeMap[type] || type
    }

    const handleTypeChange = (type) => {
      if (type === 'score_range') {
        questionForm.value.min_score = 0
        questionForm.value.max_score = 100
        questionForm.value.options = null
      } else if (type === 'score_options') {
        questionForm.value.min_score = null
        questionForm.value.max_score = null
        questionForm.value.options = [{ text: '', score: 0 }]
      }
    }

    const addOption = () => {
      questionForm.value.options.push({ text: '', score: 0 })
    }

    const removeOption = (index) => {
      if (questionForm.value.options.length > 1) {
        questionForm.value.options.splice(index, 1)
      }
    }

    const saveQuestion = async () => {
      if (!questionFormRef.value) return
      
      await questionFormRef.value.validate(async (valid) => {
        if (valid) {
          // 验证分数范围类型
          if (questionForm.value.type === 'score_range') {
            if (questionForm.value.min_score >= questionForm.value.max_score) {
              ElMessage.error('最低分必须小于最高分')
              return
            }
          }
          
          // 验证分数选项类型
          if (questionForm.value.type === 'score_options') {
            const validOptions = questionForm.value.options.filter(opt => opt.text && opt.score !== undefined)
            if (validOptions.length < 2) {
              ElMessage.error('请至少添加2个有效选项')
              return
            }
          }
          
          try {
            if (questionForm.value.id) {
              await axios.put(`/api/questions/${questionForm.value.id}`, questionForm.value, {
                headers: {
                  'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
              })
            } else {
              await axios.post('/api/questions', questionForm.value, {
                headers: {
                  'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
              })
            }
            dialogVisible.value = false
            loadQuestions()
            resetForm()
            ElMessage.success('保存成功')
          } catch (error) {
            console.error('Failed to save question:', error)
            ElMessage.error('保存失败，请重试')
          }
        }
      })
    }

    const editQuestion = (question) => {
      const dimensionId = question.dimension_id 
        ? Number(question.dimension_id) 
        : (question.dimension ? Number(question.dimension.id) : '')
      
      questionForm.value = {
        id: question.id,
        dimension_id: dimensionId,
        content: question.content,
        type: question.type || 'score_range',
        min_score: question.min_score || 0,
        max_score: question.max_score || 100,
        options: question.options || [{ text: '', score: 0 }],
        scoring_criteria: question.scoring_criteria || ''
      }
      dialogTitle.value = '编辑评估问题'
      dialogVisible.value = true
    }

    const openCreateDialog = () => {
      resetForm()
      dialogVisible.value = true
    }

    const copyQuestion = (question) => {
      // 复制问题：预填所有字段，除了dimension_id
      questionForm.value = {
        id: null, // 新建，不设置id
        dimension_id: '', // 所属维度留空，需要用户手动选择
        content: question.content,
        type: question.type || 'score_range',
        min_score: question.min_score || 0,
        max_score: question.max_score || 100,
        options: question.options ? JSON.parse(JSON.stringify(question.options)) : [{ text: '', score: 0 }],
        scoring_criteria: question.scoring_criteria || ''
      }
      dialogTitle.value = '创建评估问题'
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
          ElMessage.success('删除成功')
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
        type: 'score_range',
        min_score: 0,
        max_score: 100,
        options: [{ text: '', score: 0 }],
        scoring_criteria: ''
      }
      dialogTitle.value = '创建评估问题'
    }

    const handleSizeChange = (val) => {
      pagination.value.pageSize = val
      pagination.value.currentPage = 1
      loadQuestions()
    }

    const handleCurrentChange = (val) => {
      pagination.value.currentPage = val
      loadQuestions()
    }

    const indexMethod = (index) => {
      return (pagination.value.currentPage - 1) * pagination.value.pageSize + index + 1
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
      pagination,
      loadQuestions,
      saveQuestion,
      editQuestion,
      openCreateDialog,
      copyQuestion,
      deleteQuestion,
      getDimensionLabel,
      getDimensionDisplay,
      getTypeDisplay,
      handleTypeChange,
      addOption,
      removeOption,
      handleSizeChange,
      handleCurrentChange,
      indexMethod,
      Plus,
      Delete
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

.options-container {
  width: 100%;
}

.option-item {
  display: flex;
  align-items: center;
  margin-bottom: 10px;
}

.pagination-container {
  display: flex;
  justify-content: flex-end;
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid var(--border-color);
}
</style>
