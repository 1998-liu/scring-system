<template>
  <el-card class="register-card">
    <template #header>
      <div class="card-header">
        <h2>注册</h2>
      </div>
    </template>
    <el-form :model="registerForm" :rules="registerRules" ref="registerFormRef" label-width="80px">
      <el-form-item label="姓名" prop="name">
        <el-input v-model="registerForm.name" placeholder="请输入姓名" />
      </el-form-item>
      <el-form-item label="手机号" prop="phone">
        <el-input v-model="registerForm.phone" placeholder="请输入手机号" />
      </el-form-item>
      <el-form-item label="密码" prop="password">
        <el-tooltip :content="passwordTooltipContent" placement="bottom-start" :visible="showPasswordTooltip" :effect="'light'">
          <el-input 
            v-model="registerForm.password" 
            type="password" 
            placeholder="请输入密码" 
            show-password 
            @focus="showPasswordTooltip = true"
            @blur="showPasswordTooltip = registerForm.password !== ''"
            @input="showPasswordTooltip = true"
          />
        </el-tooltip>
      </el-form-item>
      <div class="form-actions">
        <el-button type="primary" @click="register" :loading="loading">注册</el-button>
        <el-button @click="navigateToLogin">登录</el-button>
      </div>
    </el-form>
  </el-card>
</template>

<script>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import axios from 'axios'

export default {
  name: 'Register',
  setup() {
    const router = useRouter()
    const registerFormRef = ref(null)
    const loading = ref(false)
    const registerForm = ref({
      name: '',
      phone: '',
      password: ''
    })

    const showPasswordTooltip = ref(false)
    const passwordTooltipContent = '密码规则：8-16位，包含大小写字母、数字和特殊符号(@$!%*?&)'

    const registerRules = {
      name: [
        { required: true, message: '请输入姓名', trigger: 'blur' }
      ],
      phone: [
        { required: true, message: '请输入手机号', trigger: 'blur' },
        { pattern: /^1[3-9]\d{9}$/, message: '请输入正确的手机号格式', trigger: 'blur' }
      ],
      password: [
        { required: true, message: '请输入密码', trigger: 'blur' },
        { min: 8, message: '密码长度不能少于8位', trigger: 'blur' },
        { max: 16, message: '密码长度不能超过16位', trigger: 'blur' },
        { pattern: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,16}$/, message: '密码必须包含大小写字母、数字和特殊符号', trigger: 'blur' }
      ]
    }

    const register = async () => {
      if (!registerFormRef.value) return
      
      await registerFormRef.value.validate(async (valid) => {
        if (valid) {
          loading.value = true
          try {
            const response = await axios.post('/api/register', registerForm.value)
            localStorage.setItem('token', response.data.token)
            localStorage.setItem('user', JSON.stringify(response.data.user))
            router.push('/plans')
          } catch (error) {
            console.error('Registration failed:', error)
            if (error.response && error.response.data && error.response.data.error) {
              const errorMessage = Object.values(error.response.data.error).join('\n')
              ElMessage.error('注册失败:\n' + errorMessage)
            } else {
              ElMessage.error('注册失败，请检查输入信息')
            }
          } finally {
            loading.value = false
          }
        }
      })
    }

    const navigateToLogin = () => {
      router.push('/login')
    }

    return {
      registerForm,
      registerRules,
      registerFormRef,
      loading,
      register,
      navigateToLogin,
      showPasswordTooltip,
      passwordTooltipContent
    }
  }
}
</script>

<style scoped>
.register-card {
  width: 400px;
  margin: 0 auto;
  box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

.card-header {
  text-align: center;
}

.card-header h2 {
  margin: 0;
  color: #409EFF;
}

.el-form-item {
  margin-bottom: 20px;
}

.form-actions {
  display: flex;
  justify-content: center;
  gap: 20px;
  margin-top: 50px;
}

.el-button {
  width: 120px;
}
</style>