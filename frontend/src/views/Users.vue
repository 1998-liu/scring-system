<template>
  <div class="users-container">
    <div class="page-header">
      <h2 class="page-title">用户管理</h2>
      <el-button type="primary" @click="addUser">
        <el-icon><Plus /></el-icon>
        新增用户
      </el-button>
    </div>
    
    <!-- 搜索表单 -->
    <el-card class="search-card">
      <div class="search-form">
        <el-form :model="searchForm" inline>
          <el-form-item label="用户姓名">
            <el-input 
              v-model="searchForm.name" 
              placeholder="请输入用户姓名" 
              clearable
              @input="handleSearch"
              @clear="handleSearch"
            />
          </el-form-item>
          <el-form-item label="手机号">
            <el-input 
              v-model="searchForm.phone" 
              placeholder="请输入手机号" 
              clearable
              @input="handleSearch"
              @clear="handleSearch"
            />
          </el-form-item>
          <el-form-item label="公司角色">
            <el-select 
              v-model="searchForm.company_role" 
              placeholder="选择公司角色" 
              clearable
              @change="handleSearch"
            >
              <el-option
                v-for="role in flatRoles"
                :key="role.id"
                :label="role.displayName"
                :value="role.name"
              />
            </el-select>
          </el-form-item>
          <el-form-item>
            <el-button @click="resetSearch">
              重置
            </el-button>
          </el-form-item>
        </el-form>
      </div>
    </el-card>
    
    <el-card class="content-card">
      <div class="users-content">
        <!-- 搜索结果信息 -->
        <div class="search-results-info" v-if="hasSearchCondition">
          <span>搜索条件：</span>
          <el-tag v-if="searchForm.name" size="small" effect="plain" closable @close="clearSearchField('name')">
            姓名: {{ searchForm.name }}
          </el-tag>
          <el-tag v-if="searchForm.phone" size="small" effect="plain" closable @close="clearSearchField('phone')">
            手机号: {{ searchForm.phone }}
          </el-tag>
          <el-tag v-if="searchForm.company_role" size="small" effect="plain" closable @close="clearSearchField('company_role')">
            公司角色: {{ getRoleDisplayName(searchForm.company_role) }}
          </el-tag>
          <span class="result-count">共 {{ pagination.total }} 条结果</span>
        </div>
        
        <!-- 空状态 -->
        <el-empty v-if="users.length === 0 && hasSearchCondition" description="没有找到匹配的用户" />
        
        <el-table v-else :data="users" class="user-table">
          <el-table-column prop="id" label="ID" width="80" />
          <el-table-column prop="name" label="姓名" />
          <el-table-column prop="phone" label="手机号" />
          <el-table-column prop="email" label="邮箱" />
          <el-table-column prop="department.name" label="部门" />
          <el-table-column prop="position" label="岗位" />
          <el-table-column prop="role" label="系统角色">
            <template #default="scope">
              <el-tag :type="scope.row.role === 'admin' ? 'danger' : 'success'" effect="light">
                {{ scope.row.role === 'admin' ? '管理员' : '普通用户' }}
              </el-tag>
            </template>
          </el-table-column>
          <el-table-column prop="company_role" label="公司角色">
            <template #default="scope">
              <el-tag type="info" effect="plain">{{ scope.row.company_role || '未设置' }}</el-tag>
            </template>
          </el-table-column>
          <el-table-column label="操作" width="300">
            <template #default="scope">
              <div class="action-buttons">
                <el-button type="primary" size="small" @click="editUser(scope.row)">
                  <el-icon><Edit /></el-icon>
                  编辑
                </el-button>
                <el-button type="warning" size="small" @click="openChangePasswordDialog(scope.row)">
                  <el-icon><Key /></el-icon>
                  修改密码
                </el-button>
                <el-button type="danger" size="small" @click="confirmDeleteUser(scope.row.id)" :disabled="scope.row.id === currentUser.id">
                  <el-icon><Delete /></el-icon>
                  删除
                </el-button>
              </div>
            </template>
          </el-table-column>
        </el-table>
        
        <!-- 分页组件 -->
        <div class="pagination-container" v-if="users.length > 0">
          <el-pagination
            :current-page="pagination.currentPage"
            :page-size="pagination.pageSize"
            :page-sizes="[10, 20, 50, 100]"
            layout="total, sizes, prev, pager, next, jumper"
            :total="pagination.total"
            @size-change="handleSizeChange"
            @current-change="handleCurrentChange"
          />
        </div>
      </div>
    </el-card>

    <!-- 新增/编辑用户对话框 -->
    <el-dialog v-model="addUserVisible" :title="isEdit ? '编辑用户' : '新增用户'">
      <el-form :model="userForm" :rules="userRules" ref="userFormRef" label-width="100px">
        <el-form-item label="姓名" prop="name">
          <el-input v-model="userForm.name" />
        </el-form-item>
        <el-form-item label="手机号" prop="phone">
          <el-input v-model="userForm.phone" />
        </el-form-item>
        <el-form-item label="邮箱" prop="email">
          <el-input v-model="userForm.email" />
        </el-form-item>
        <el-form-item label="部门" prop="department_id">
          <el-select v-model="userForm.department_id" placeholder="选择部门">
            <el-option v-for="dept in departments" :key="dept.id" :label="dept.name" :value="dept.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="岗位" prop="position">
          <el-input v-model="userForm.position" />
        </el-form-item>
        <el-form-item label="工号" prop="employee_id">
          <el-input v-model="userForm.employee_id" />
        </el-form-item>
        <el-form-item label="系统角色" prop="role">
          <el-select v-model="userForm.role" placeholder="选择系统角色">
            <el-option label="普通用户" value="user" />
            <el-option label="管理员" value="admin" />
          </el-select>
        </el-form-item>
        <el-form-item label="公司角色" prop="company_role">
          <el-select
            v-model="userForm.company_role"
            placeholder="选择公司角色"
            style="width: 100%"
          >
            <el-option
              v-for="role in flatRoles"
              :key="role.id"
              :label="role.displayName"
              :value="role.name"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="密码" prop="password" v-if="!isEdit">
          <el-tooltip
            :content="passwordTooltipContent"
            placement="top"
            :visible="showUserPasswordTooltip"
            :effect="'light'"
          >
            <el-input
              v-model="userForm.password"
              type="password"
              placeholder="请输入密码"
              show-password
              @focus="showUserPasswordTooltip = true"
              @blur="showUserPasswordTooltip = false"
              @input="showUserPasswordTooltip = true"
            />
          </el-tooltip>
        </el-form-item>
        <el-form-item label="确认密码" prop="confirmPassword" v-if="!isEdit">
          <el-input v-model="userForm.confirmPassword" type="password" show-password />
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="addUserVisible = false">取消</el-button>
          <el-button type="primary" @click="saveUser">保存</el-button>
        </span>
      </template>
    </el-dialog>

    <!-- 修改密码对话框 -->
    <el-dialog v-model="changePasswordVisible" title="修改密码">
      <el-form :model="passwordForm" :rules="passwordRules" ref="passwordFormRef" label-width="120px">
        <el-form-item label="新密码" prop="newPassword">
          <el-tooltip
            :content="passwordTooltipContent"
            placement="top"
            :visible="showChangePasswordTooltip"
            :effect="'light'"
          >
            <el-input
              v-model="passwordForm.newPassword"
              type="password"
              placeholder="请输入新密码"
              show-password
              @focus="showChangePasswordTooltip = true"
              @blur="showChangePasswordTooltip = false"
              @input="showChangePasswordTooltip = true"
            />
          </el-tooltip>
        </el-form-item>
        <el-form-item label="确认新密码" prop="confirmPassword">
          <el-input v-model="passwordForm.confirmPassword" type="password" show-password />
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="changePasswordVisible = false">取消</el-button>
          <el-button type="primary" @click="changePassword">保存</el-button>
        </span>
      </template>
    </el-dialog>

    <!-- 确认删除对话框 -->
    <CustomConfirmDialog
      v-model="confirmDialogVisible"
      message="确定要删除这个用户吗？"
      @confirm="deleteUser"
    />
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue'
import { ElMessage } from 'element-plus'
import CustomConfirmDialog from '../components/CustomConfirmDialog.vue'
import axios from 'axios'
import { Plus, Edit, Key, Delete } from '@element-plus/icons-vue'

export default {
  name: 'Users',
  components: {
    Plus,
    Edit,
    Key,
    Delete,
    CustomConfirmDialog
  },
  setup() {
    const users = ref([])
    const departments = ref([])
    const roles = ref([])
    const roleHierarchy = ref([])
    const addUserVisible = ref(false)
    const changePasswordVisible = ref(false)
    const confirmDialogVisible = ref(false)
    const userFormRef = ref(null)
    const passwordFormRef = ref(null)
    const isEdit = ref(false)
    const currentUser = ref({ id: null })
    let deleteUserId = ref(null)
    const userForm = ref({
        name: '',
        phone: '',
        email: '',
        department_id: '',
        position: '',
        employee_id: '',
        role: 'user',
        company_role: '',
        password: '',
        confirmPassword: ''
    })
    const passwordForm = ref({
        userId: null,
        newPassword: '',
        confirmPassword: ''
    })

    const showUserPasswordTooltip = ref(false)
    const showChangePasswordTooltip = ref(false)
    const passwordTooltipContent = '密码规则：8-16位，包含大小写字母、数字和特殊符号(@$!%*?&)'
    
    // 搜索表单
    const searchForm = ref({
      name: '',
      phone: '',
      company_role: ''
    })
    
    // 分页数据
    const pagination = ref({
      currentPage: 1,
      pageSize: 10,
      total: 0
    })
    
    // 防抖定时器
    let debounceTimer = null
    const DEBOUNCE_DELAY = 300 // 300ms防抖
    
    // 是否有搜索条件
    const hasSearchCondition = computed(() => {
      return searchForm.value.name || searchForm.value.phone || searchForm.value.company_role
    })

    const userRules = {
      name: [
        { required: true, message: '请输入姓名', trigger: 'blur' }
      ],
      phone: [
        { required: true, message: '请输入手机号', trigger: 'blur' },
        { pattern: /^1[3-9]\d{9}$/, message: '请输入正确的手机号格式', trigger: 'blur' }
      ],
      email: [
        { type: 'email', message: '请输入正确的邮箱格式', trigger: 'blur' }
      ],
      company_role: [
        { required: true, message: '请选择公司角色', trigger: 'blur' }
      ],
      password: [
        { required: true, message: '请输入密码', trigger: 'blur' },
        { min: 8, message: '密码长度不能少于8位', trigger: 'blur' },
        { max: 16, message: '密码长度不能超过16位', trigger: 'blur' },
        { pattern: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,16}$/, message: '密码必须包含大小写字母、数字和特殊符号', trigger: 'blur' }
      ],
      confirmPassword: [
        { required: true, message: '请确认密码', trigger: 'blur' },
        {
          validator: (_, value, callback) => {
            if (value !== userForm.value.password) {
              callback(new Error('两次输入的密码不一致'))
            } else {
              callback()
            }
          },
          trigger: 'blur'
        }
      ]
    }

    const passwordRules = {
      newPassword: [
        { required: true, message: '请输入新密码', trigger: 'blur' },
        { min: 8, message: '密码长度不能少于8位', trigger: 'blur' },
        { max: 16, message: '密码长度不能超过16位', trigger: 'blur' },
        { pattern: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,16}$/, message: '密码必须包含大小写字母、数字和特殊符号', trigger: 'blur' }
      ],
      confirmPassword: [
        { required: true, message: '请确认新密码', trigger: 'blur' },
        {
          validator: (_, value, callback) => {
            if (value !== passwordForm.value.newPassword) {
              callback(new Error('两次输入的新密码不一致'))
            } else {
              callback()
            }
          },
          trigger: 'blur'
        }
      ]
    }

    const loadUsers = async () => {
      try {
        const response = await axios.get('/api/users', {
          params: {
            name: searchForm.value.name,
            phone: searchForm.value.phone,
            company_role: searchForm.value.company_role,
            page: pagination.value.currentPage,
            page_size: pagination.value.pageSize
          },
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        users.value = response.data.data || response.data
        pagination.value.total = response.data.total || users.value.length
      } catch (error) {
        console.error('Failed to load users:', error)
      }
    }
    
    // 搜索处理（带防抖）
    const handleSearch = () => {
      clearTimeout(debounceTimer)
      debounceTimer = setTimeout(() => {
        pagination.value.currentPage = 1
        loadUsers()
      }, DEBOUNCE_DELAY)
    }
    
    // 重置搜索
    const resetSearch = () => {
      searchForm.value = {
        name: '',
        phone: '',
        company_role: ''
      }
      pagination.value.currentPage = 1
      loadUsers()
    }
    
    // 清除单个搜索字段
    const clearSearchField = (field) => {
      searchForm.value[field] = ''
      pagination.value.currentPage = 1
      loadUsers()
    }
    
    // 分页处理
    const handleSizeChange = (size) => {
      pagination.value.pageSize = size
      pagination.value.currentPage = 1
      loadUsers()
    }
    
    const handleCurrentChange = (current) => {
      pagination.value.currentPage = current
      loadUsers()
    }
    
    // 获取角色显示名称
    const getRoleDisplayName = (roleName) => {
      const role = flatRoles.value.find(r => r.name === roleName)
      return role ? role.displayName : roleName
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

    const loadRoles = async () => {
      try {
        const response = await axios.get('/api/roles', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        roles.value = response.data
      } catch (error) {
        console.error('Failed to load roles:', error)
      }
    }

    const convertRoleHierarchyTypes = (nodes) => {
      if (!nodes || !Array.isArray(nodes)) {
        return []
      }
      return nodes.map(node => ({
        ...node,
        id: Number(node.id),
        parent_id: node.parent_id ? Number(node.parent_id) : null,
        children: node.children ? convertRoleHierarchyTypes(node.children) : []
      }))
    }

    const loadRoleHierarchy = async () => {
      try {
        const response = await axios.get('/api/roles/hierarchy', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        roleHierarchy.value = convertRoleHierarchyTypes(response.data)
      } catch (error) {
        console.error('Failed to load role hierarchy:', error)
      }
    }

    // 扁平化角色层级数据，用于下拉列表显示
    const flatRoles = computed(() => {
      const flatten = (nodes, level = 0) => {
        let result = []
        nodes.forEach(node => {
          result.push({
            ...node,
            level,
            displayName: '  '.repeat(level) + node.name
          })
          if (node.children && node.children.length > 0) {
            result = result.concat(flatten(node.children, level + 1))
          }
        })
        return result
      }
      return flatten(roleHierarchy.value)
    })

    const loadCurrentUser = async () => {
      try {
        const response = await axios.get('/api/me', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        currentUser.value = response.data
      } catch (error) {
        console.error('Failed to load current user:', error)
      }
    }

    const addUser = () => {
      isEdit.value = false
      userForm.value = {
        name: '',
        phone: '',
        email: '',
        department_id: '',
        position: '',
        employee_id: '',
        role: 'user',
        company_role: '',
        password: '',
        confirmPassword: ''
      }
      showUserPasswordTooltip.value = false
      addUserVisible.value = true
    }

    const editUser = (user) => {
      isEdit.value = true
      userForm.value = { ...user }
      addUserVisible.value = true
    }

    const openChangePasswordDialog = (user) => {
      passwordForm.value = {
        userId: user.id,
        newPassword: '',
        confirmPassword: ''
      }
      showChangePasswordTooltip.value = false
      changePasswordVisible.value = true
    }

    const saveUser = async () => {
      if (!userFormRef.value) return
      
      await userFormRef.value.validate(async (valid) => {
        if (valid) {
          try {
            if (isEdit.value) {
              await axios.put(`/api/users/${userForm.value.id}`, userForm.value, {
                headers: {
                  'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
              })
            } else {
              // 移除confirmPassword字段，后端不需要这个字段
              const formData = { ...userForm.value }
              delete formData.confirmPassword
              await axios.post('/api/register', formData, {
                headers: {
                  'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
              })
            }
            addUserVisible.value = false
            loadUsers()
            ElMessage.success('保存成功')
          } catch (error) {
            console.error('Failed to save user:', error)
            // 显示具体的错误信息
            if (error.response && error.response.data && error.response.data.error) {
              const errorMessages = []
              for (const key in error.response.data.error) {
                errorMessages.push(error.response.data.error[key][0])
              }
              ElMessage.error('保存失败：' + errorMessages.join('\n'))
            } else {
              ElMessage.error('保存失败，请重试')
            }
          }
        }
      })
    }

    const changePassword = async () => {
      if (!passwordFormRef.value) return
      
      await passwordFormRef.value.validate(async (valid) => {
        if (valid) {
          try {
            await axios.post(`/api/users/${passwordForm.value.userId}/change-password`, {
              newPassword: passwordForm.value.newPassword
            }, {
              headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
              }
            })
            changePasswordVisible.value = false
            ElMessage.success('密码修改成功')
            loadUsers()
          } catch (error) {
            console.error('Failed to change password:', error)
            // 显示具体的错误信息
            if (error.response && error.response.data && error.response.data.error) {
              if (typeof error.response.data.error === 'object') {
                const errorMessages = []
                for (const key in error.response.data.error) {
                  errorMessages.push(error.response.data.error[key][0])
                }
                ElMessage.error('密码修改失败：' + errorMessages.join('\n'))
              } else {
                ElMessage.error('密码修改失败：' + error.response.data.error)
              }
            } else {
              ElMessage.error('密码修改失败，请重试')
            }
          }
        }
      })
    }

    const confirmDeleteUser = (id) => {
      // 检查是否是当前登录用户
      if (id === currentUser.value.id) {
        ElMessage.warning('无法删除自己的账号')
        return
      }
      deleteUserId.value = id
      confirmDialogVisible.value = true
    }

    const deleteUser = async () => {
      try {
        await axios.delete(`/api/users/${deleteUserId.value}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        loadUsers()
        confirmDialogVisible.value = false
        ElMessage.success('删除成功')
      } catch (error) {
        console.error('Failed to delete user:', error)
        confirmDialogVisible.value = false
        ElMessage.error('删除失败，请重试')
      }
    }

    onMounted(() => {
      loadUsers()
      loadDepartments()
      loadRoles()
      loadRoleHierarchy()
      loadCurrentUser()
    })

    return {
      users,
      departments,
      roles,
      roleHierarchy,
      flatRoles,
      addUserVisible,
      changePasswordVisible,
      confirmDialogVisible,
      userForm,
      passwordForm,
      searchForm,
      pagination,
      hasSearchCondition,
      userRules,
      passwordRules,
      userFormRef,
      passwordFormRef,
      isEdit,
      currentUser,
      showUserPasswordTooltip,
      showChangePasswordTooltip,
      passwordTooltipContent,
      loadUsers,
      loadRoles,
      addUser,
      editUser,
      saveUser,
      changePassword,
      openChangePasswordDialog,
      deleteUser,
      confirmDeleteUser,
      handleSearch,
      resetSearch,
      clearSearchField,
      handleSizeChange,
      handleCurrentChange,
      getRoleDisplayName
    }
  }
}
</script>

<style scoped>
.users-container {
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

.users-content {
  margin-top: var(--spacing-lg);
}

.user-table {
  border-radius: var(--radius-lg);
  overflow: hidden;
}

.user-table :deep(.el-table__header-wrapper) {
  background: var(--background-color);
}

.user-table :deep(.el-table__row) {
  transition: all var(--transition-fast);
}

.user-table :deep(.el-table__row:hover > td) {
  background-color: rgba(79, 70, 229, 0.04) !important;
}

.action-buttons {
  display: flex;
  gap: var(--spacing-xs);
}

.action-buttons .el-button {
  border-radius: var(--radius-md);
  transition: all var(--transition-fast);
}

.action-buttons .el-button:hover {
  transform: translateY(-1px);
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: var(--spacing-sm);
}

.search-card {
  margin-bottom: var(--spacing-lg);
  border-radius: var(--radius-lg);
}

.search-form {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-end;
}

.search-form :deep(.el-form-item) {
  margin-bottom: 0;
  margin-right: var(--spacing-md);
}

.search-results-info {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: var(--spacing-sm);
  margin-bottom: var(--spacing-md);
  padding: var(--spacing-sm) var(--spacing-md);
  background-color: var(--background-color);
  border-radius: var(--radius-md);
  border: 1px solid var(--border-color);
}

.result-count {
  margin-left: auto;
  font-weight: var(--font-weight-medium);
  color: var(--text-secondary);
}

.pagination-container {
  display: flex;
  justify-content: flex-end;
  margin-top: var(--spacing-lg);
  padding-top: var(--spacing-md);
}

@media (max-width: 768px) {
  .page-header {
    flex-direction: column;
    align-items: flex-start;
    gap: var(--spacing-md);
  }
  
  .action-buttons {
    flex-wrap: wrap;
  }
  
  .search-form {
    flex-direction: column;
    align-items: stretch;
  }
  
  .search-form :deep(.el-form-item) {
    width: 100%;
    margin-right: 0;
  }
  
  .pagination-container {
    justify-content: center;
  }
  
  .search-results-info {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .result-count {
    margin-left: 0;
    margin-top: var(--spacing-sm);
  }
}
</style>