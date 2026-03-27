<template>
  <div class="departments-container">
    <div class="page-header">
      <h2 class="page-title">部门管理</h2>
      <el-button type="primary" @click="addDepartmentVisible = true">
        <el-icon><Plus /></el-icon>
        新增部门
      </el-button>
    </div>
    
    <el-card class="content-card">
      <div class="departments-content">
        <el-table :data="departments" style="width: 100%">
          <el-table-column prop="id" label="ID" width="80" />
          <el-table-column prop="name" label="部门名称" />
          <el-table-column prop="parent.name" label="上级部门" />
          <el-table-column prop="leader.name" label="部门负责人" />
          <el-table-column label="操作" width="200">
            <template #default="scope">
              <el-button type="primary" size="small" @click="editDepartment(scope.row)">编辑</el-button>
              <el-button type="danger" size="small" @click="deleteDepartment(scope.row.id)">删除</el-button>
            </template>
          </el-table-column>
        </el-table>
      </div>
    </el-card>

    <!-- 新增/编辑部门对话框 -->
    <el-dialog v-model="addDepartmentVisible" :title="isEdit ? '编辑部门' : '新增部门'">
      <el-form :model="departmentForm" :rules="departmentRules" ref="departmentFormRef" label-width="100px">
        <el-form-item label="部门名称" prop="name">
          <el-input v-model="departmentForm.name" />
        </el-form-item>
        <el-form-item label="上级部门" prop="parent_id">
          <el-select v-model="departmentForm.parent_id" placeholder="选择上级部门">
            <el-option label="无" value="" />
            <el-option v-for="dept in departments" :key="dept.id" :label="dept.name" :value="dept.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="部门负责人" prop="leader_id">
          <el-select v-model="departmentForm.leader_id" placeholder="选择部门负责人">
            <el-option v-for="user in users" :key="user.id" :label="user.name" :value="user.id" />
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="addDepartmentVisible = false">取消</el-button>
          <el-button type="primary" @click="saveDepartment">保存</el-button>
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
  name: 'Departments',
  components: {
    Plus
  },
  setup() {
    const departments = ref([])
    const users = ref([])
    const addDepartmentVisible = ref(false)
    const departmentFormRef = ref(null)
    const isEdit = ref(false)
    const departmentForm = ref({
      name: '',
      parent_id: '',
      leader_id: ''
    })

    const departmentRules = {
      name: [
        { required: true, message: '请输入部门名称', trigger: 'blur' }
      ]
    }

    const loadDepartments = async () => {
      try {
        const response = await axios.get('/api/departments', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        departments.value = response.data
      } catch (error) {
        console.error('Failed to load departments:', error)
      }
    }

    const loadUsers = async () => {
      try {
        const response = await axios.get('/api/users', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        users.value = response.data
      } catch (error) {
        console.error('Failed to load users:', error)
      }
    }

    const addDepartment = () => {
      isEdit.value = false
      departmentForm.value = {
        name: '',
        parent_id: '',
        leader_id: ''
      }
      addDepartmentVisible.value = true
    }

    const editDepartment = (department) => {
      isEdit.value = true
      departmentForm.value = { ...department }
      addDepartmentVisible.value = true
    }

    const saveDepartment = async () => {
      if (!departmentFormRef.value) return
      
      await departmentFormRef.value.validate(async (valid) => {
        if (valid) {
          try {
            if (isEdit.value) {
              await axios.put(`/api/departments/${departmentForm.value.id}`, departmentForm.value, {
                headers: {
                  'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
              })
            } else {
              await axios.post('/api/departments', departmentForm.value, {
                headers: {
                  'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
              })
            }
            addDepartmentVisible.value = false
            loadDepartments()
            ElMessage.success('保存成功')
          } catch (error) {
            console.error('Failed to save department:', error)
            ElMessage.error('保存失败，请重试')
          }
        }
      })
    }

    const deleteDepartment = async (id) => {
      if (confirm('确定要删除这个部门吗？')) {
        try {
          await axios.delete(`/api/departments/${id}`, {
            headers: {
              'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
          })
          loadDepartments()
          ElMessage.success('删除成功')
        } catch (error) {
          console.error('Failed to delete department:', error)
          ElMessage.error('删除失败，请重试')
        }
      }
    }

    onMounted(() => {
      loadDepartments()
      loadUsers()
    })

    return {
      departments,
      users,
      addDepartmentVisible,
      departmentForm,
      departmentRules,
      departmentFormRef,
      isEdit,
      loadDepartments,
      addDepartment,
      editDepartment,
      saveDepartment,
      deleteDepartment
    }
  }
}
</script>

<style scoped>
.departments-container {
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

.departments-content {
  margin-top: var(--spacing-md);
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: var(--spacing-sm);
}
</style>