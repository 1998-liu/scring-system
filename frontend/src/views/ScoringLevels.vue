<template>
  <div class="scoring-levels-container">
    <div class="page-header">
      <h2 class="page-title">评分等级管理</h2>
      <el-button type="primary" @click="openCreateDialog">
        <el-icon><Plus /></el-icon>
        新增等级
      </el-button>
    </div>

    <el-card class="content-card">
      <el-table :data="levels" style="width: 100%">
        <el-table-column prop="name" label="等级名称" width="150" />
        <el-table-column label="分数区间" width="200">
          <template #default="scope">
            {{ scope.row.min_score }} - {{ scope.row.max_score }}分
          </template>
        </el-table-column>
        <el-table-column label="颜色标识" width="100">
          <template #default="scope">
            <el-tag :color="scope.row.color" style="color: white">
              {{ scope.row.name }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="sort_order" label="排序" width="80" />
        <el-table-column label="状态" width="100">
          <template #default="scope">
            <el-tag :type="scope.row.is_active ? 'success' : 'info'">
              {{ scope.row.is_active ? '启用' : '禁用' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="操作" width="150" fixed="right">
          <template #default="scope">
            <el-button size="small" @click="editLevel(scope.row)">编辑</el-button>
            <el-button size="small" type="danger" @click="deleteLevel(scope.row.id)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <el-dialog v-model="dialogVisible" :title="dialogTitle" width="500px">
      <el-form :model="levelForm" :rules="levelRules" ref="levelFormRef" label-width="100px">
        <el-form-item label="等级名称" prop="name">
          <el-input v-model="levelForm.name" placeholder="请输入等级名称" />
        </el-form-item>
        <el-form-item label="等级代码" prop="code">
          <el-input v-model="levelForm.code" placeholder="请输入等级代码（英文）" />
        </el-form-item>
        <el-form-item label="最低分数" prop="min_score">
          <el-input-number v-model="levelForm.min_score" :min="0" :max="100" :precision="2" style="width: 100%" />
        </el-form-item>
        <el-form-item label="最高分数" prop="max_score">
          <el-input-number v-model="levelForm.max_score" :min="0" :max="100" :precision="2" style="width: 100%" />
        </el-form-item>
        <el-form-item label="颜色标识" prop="color">
          <el-color-picker v-model="levelForm.color" />
        </el-form-item>
        <el-form-item label="排序" prop="sort_order">
          <el-input-number v-model="levelForm.sort_order" :min="0" style="width: 100%" />
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="dialogVisible = false">取消</el-button>
          <el-button type="primary" @click="saveLevel">保存</el-button>
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
  name: 'ScoringLevels',
  components: {
    Plus
  },
  setup() {
    const levels = ref([])
    const dialogVisible = ref(false)
    const dialogTitle = ref('新增评分等级')
    const levelFormRef = ref(null)
    const levelForm = ref({
      id: null,
      name: '',
      code: '',
      min_score: 0,
      max_score: 100,
      color: '#409EFF',
      sort_order: 0
    })

    const levelRules = {
      name: [{ required: true, message: '请输入等级名称', trigger: 'blur' }],
      code: [{ required: true, message: '请输入等级代码', trigger: 'blur' }],
      min_score: [{ required: true, message: '请输入最低分数', trigger: 'blur' }],
      max_score: [{ required: true, message: '请输入最高分数', trigger: 'blur' }]
    }

    const loadLevels = async () => {
      try {
        const response = await axios.get('/api/scoring-levels', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        levels.value = response.data
      } catch (error) {
        console.error('Failed to load levels:', error)
        ElMessage.error('加载评分等级失败')
      }
    }

    const openCreateDialog = () => {
      dialogTitle.value = '新增评分等级'
      levelForm.value = {
        id: null,
        name: '',
        code: '',
        min_score: 0,
        max_score: 100,
        color: '#409EFF',
        sort_order: 0
      }
      dialogVisible.value = true
    }

    const editLevel = (level) => {
      dialogTitle.value = '编辑评分等级'
      levelForm.value = {
        id: level.id,
        name: level.name,
        code: level.code,
        min_score: level.min_score,
        max_score: level.max_score,
        color: level.color,
        sort_order: level.sort_order
      }
      dialogVisible.value = true
    }

    const saveLevel = async () => {
      if (!levelFormRef.value) return

      await levelFormRef.value.validate(async (valid) => {
        if (valid) {
          if (levelForm.value.min_score >= levelForm.value.max_score) {
            ElMessage.error('最低分数必须小于最高分数')
            return
          }

          try {
            if (levelForm.value.id) {
              await axios.put(`/api/scoring-levels/${levelForm.value.id}`, levelForm.value, {
                headers: {
                  'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
              })
              ElMessage.success('更新成功')
            } else {
              await axios.post('/api/scoring-levels', levelForm.value, {
                headers: {
                  'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
              })
              ElMessage.success('创建成功')
            }
            dialogVisible.value = false
            loadLevels()
          } catch (error) {
            console.error('Failed to save level:', error)
            ElMessage.error('保存失败')
          }
        }
      })
    }

    const deleteLevel = async (id) => {
      try {
        await axios.delete(`/api/scoring-levels/${id}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        ElMessage.success('删除成功')
        loadLevels()
      } catch (error) {
        console.error('Failed to delete level:', error)
        ElMessage.error('删除失败')
      }
    }

    onMounted(() => {
      loadLevels()
    })

    return {
      levels,
      dialogVisible,
      dialogTitle,
      levelForm,
      levelRules,
      levelFormRef,
      loadLevels,
      openCreateDialog,
      editLevel,
      saveLevel,
      deleteLevel,
      Plus
    }
  }
}
</script>

<style scoped>
.scoring-levels-container {
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
