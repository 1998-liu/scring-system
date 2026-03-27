<template>
  <div class="profile-container">
    <el-card>
      <template #header>
        <div class="card-header">
          <h2>个人中心</h2>
        </div>
      </template>
      <div class="profile-content">
        <el-avatar :size="100" :src="userAvatar" class="avatar" />
        <h3>{{ user.name }}</h3>
        <el-form :model="user" label-width="100px">
          <el-form-item label="邮箱">
            <el-input v-model="user.email" disabled />
          </el-form-item>
          <el-form-item label="部门">
            <el-input :value="user.department?.name || '未设置'" disabled />
          </el-form-item>
          <el-form-item label="岗位">
            <el-input :value="user.position || '未设置'" disabled />
          </el-form-item>
          <el-form-item label="工号">
            <el-input :value="user.employee_id || '未设置'" disabled />
          </el-form-item>
          <el-form-item label="电话">
            <el-input :value="user.phone || '未设置'" disabled />
          </el-form-item>
        </el-form>
        <div class="button-group">
          <el-button type="primary" @click="editProfile">编辑个人信息</el-button>
          <el-button type="warning" @click="changePasswordVisible = true">修改密码</el-button>
        </div>
      </div>
    </el-card>

    <!-- 编辑个人信息对话框 -->
    <el-dialog v-model="editVisible" title="编辑个人信息">
      <el-form :model="editForm" :rules="editRules" ref="editFormRef" label-width="100px">
        <el-form-item label="姓名" prop="name">
          <el-input v-model="editForm.name" />
        </el-form-item>
        <el-form-item label="邮箱" prop="email">
          <el-input v-model="editForm.email" />
        </el-form-item>
        <el-form-item label="部门" prop="department_id">
          <el-select v-model="editForm.department_id" placeholder="选择部门">
            <el-option v-for="dept in departments" :key="dept.id" :label="dept.name" :value="dept.id" />
          </el-select>
        </el-form-item>
        <el-form-item label="岗位" prop="position">
          <el-input v-model="editForm.position" />
        </el-form-item>
        <el-form-item label="工号" prop="employee_id">
          <el-input v-model="editForm.employee_id" />
        </el-form-item>
        <el-form-item label="电话" prop="phone">
          <el-input v-model="editForm.phone" />
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="editVisible = false">取消</el-button>
          <el-button type="primary" @click="saveProfile">保存</el-button>
        </span>
      </template>
    </el-dialog>

    <!-- 修改密码对话框 -->
    <el-dialog v-model="changePasswordVisible" title="修改密码">
      <el-form :model="passwordForm" :rules="passwordRules" ref="passwordFormRef" label-width="100px">
        <el-form-item label="新密码" prop="newPassword">
          <el-input v-model="passwordForm.newPassword" type="password" show-password />
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
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import axios from 'axios'

export default {
  name: 'Profile',
  setup() {
    const user = ref({})
    const departments = ref([])
    const editVisible = ref(false)
    const editFormRef = ref(null)
    const editForm = ref({
      name: '',
      email: '',
      department_id: '',
      position: '',
      employee_id: '',
      phone: ''
    })

    const editRules = {
      name: [
        { required: true, message: '请输入姓名', trigger: 'blur' }
      ],
      email: [
        { type: 'email', message: '请输入正确的邮箱格式', trigger: 'blur' }
      ]
    }

    // 修改密码相关
    const changePasswordVisible = ref(false)
    const passwordForm = ref({
      newPassword: '',
      confirmPassword: ''
    })
    const passwordFormRef = ref(null)
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
          validator: (rule, value, callback) => {
            if (value !== passwordForm.value.newPassword) {
              callback(new Error('两次输入的密码不一致'))
            } else {
              callback()
            }
          },
          trigger: 'blur'
        }
      ]
    }

    const userAvatar = ref('https://cube.elemecdn.com/3/7c/3ea6beec64369c2642b92c6726f1epng.png')

    const loadUserInfo = async () => {
      try {
        const response = await axios.get('/api/me', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        user.value = response.data
      } catch (error) {
        console.error('Failed to load user info:', error)
      }
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

    const editProfile = () => {
      editForm.value = { ...user.value }
      editVisible.value = true
    }

    const saveProfile = async () => {
      if (!editFormRef.value) return
      
      await editFormRef.value.validate(async (valid) => {
        if (valid) {
          try {
            await axios.put('/api/users/' + user.value.id, editForm.value, {
              headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
              }
            })
            editVisible.value = false
            loadUserInfo()
            ElMessage.success('保存成功')
          } catch (error) {
            console.error('Failed to save profile:', error)
            ElMessage.error('保存失败，请重试')
          }
        }
      })
    }

    const changePassword = async () => {
      if (!passwordFormRef.value) return
      
      await passwordFormRef.value.validate(async (valid) => {
        if (valid) {
          try {
            await axios.post('/api/change-password', {
              newPassword: passwordForm.value.newPassword
            }, {
              headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
              }
            })
            changePasswordVisible.value = false
            passwordForm.value = {
              newPassword: '',
              confirmPassword: ''
            }
            ElMessage.success('密码修改成功')
          } catch (error) {
            console.error('Failed to change password:', error)
            ElMessage.error('密码修改失败，请重试')
          }
        }
      })
    }

    onMounted(() => {
      loadUserInfo()
      loadDepartments()
    })

    return {
      user,
      departments,
      editVisible,
      editForm,
      editRules,
      editFormRef,
      userAvatar,
      loadUserInfo,
      editProfile,
      saveProfile,
      changePasswordVisible,
      passwordForm,
      passwordFormRef,
      passwordRules,
      changePassword
    }
  }
}
</script>

<style scoped>
.profile-container {
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

.profile-content {
  text-align: center;
  padding: 20px;
}

.avatar {
  margin: 0 auto 20px;
}

.profile-content h3 {
  margin-bottom: 30px;
  color: #409EFF;
}

.el-form {
  max-width: 600px;
  margin: 0 auto 30px;
  text-align: left;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.button-group {
  display: flex;
  gap: 10px;
  justify-content: center;
  margin-top: 20px;
}
</style>