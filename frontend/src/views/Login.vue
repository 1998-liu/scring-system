<template>
  <div class="login-page">
    <div class="login-container">
      <div class="login-left">
        <div class="welcome-content">
          <h1 class="welcome-title">360评分系统</h1>
          <p class="welcome-subtitle">全方位人才评分解决方案</p>
          <div class="features">
            <div class="feature-item">
              <el-icon class="feature-icon"><DataAnalysis /></el-icon>
              <div class="feature-text">
                <h3>多维度评估</h3>
                <p>支持自定义评估维度和指标</p>
              </div>
            </div>
            <div class="feature-item">
              <el-icon class="feature-icon"><User /></el-icon>
              <div class="feature-text">
                <h3>360反馈</h3>
                <p>收集来自多方的评估意见</p>
              </div>
            </div>
            <div class="feature-item">
              <el-icon class="feature-icon"><Document /></el-icon>
              <div class="feature-text">
                <h3>智能报告</h3>
                <p>自动生成专业评估报告</p>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="login-right">
        <div class="login-card">
          <div class="card-header">
            <div class="logo-wrapper">
              <el-icon class="logo-icon"><DataAnalysis /></el-icon>
            </div>
            <h2 class="card-title">欢迎登录</h2>
            <p class="card-subtitle">请输入您的账号信息</p>
          </div>
          
          <el-form
            :model="loginForm"
            :rules="loginRules"
            ref="loginFormRef"
            class="login-form"
            @submit.prevent
          >
            <el-form-item prop="phone">
              <el-input
                v-model="loginForm.phone"
                placeholder="请输入手机号"
                size="large"
                :prefix-icon="Phone"
                @keyup.enter="login"
              />
            </el-form-item>
            
            <el-form-item prop="password">
              <el-input
                v-model="loginForm.password"
                type="password"
                placeholder="请输入密码"
                size="large"
                :prefix-icon="Lock"
                show-password
                @keyup.enter="login"
              />
            </el-form-item>
            
            <el-form-item>
              <el-button
                type="primary"
                size="large"
                class="login-btn"
                native-type="button"
                :loading="loading"
                @click="login"
              >
                登录
              </el-button>
            </el-form-item>
            
            <div class="form-footer">
              <span class="footer-text">还没有账号？</span>
              <el-button type="primary" link @click="navigateToRegister">
                立即注册
              </el-button>
            </div>
          </el-form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref } from "vue";
import { useRouter } from "vue-router";
import { Phone, Lock, DataAnalysis, User, Document } from "@element-plus/icons-vue";
import { ElMessage } from "element-plus";
import axios from "axios";

export default {
  name: "Login",
  components: {
    DataAnalysis,
    User,
    Document,
  },
  setup() {
    const router = useRouter();
    const loginFormRef = ref(null);
    const loading = ref(false);
    const loginForm = ref({
      phone: "",
      password: "",
    });

    const loginRules = {
      phone: [
        { required: true, message: "请输入手机号", trigger: "blur" },
        {
          pattern: /^1[3-9]\d{9}$/,
          message: "请输入正确的手机号格式",
          trigger: "blur",
        },
      ],
      password: [
        { required: true, message: "请输入密码", trigger: "blur" },
        { min: 8, message: "密码长度不能少于8位", trigger: "blur" },
        { max: 16, message: "密码长度不能超过16位", trigger: "blur" },
        {
          pattern:
            /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,16}$/,
          message: "密码必须包含大小写字母、数字和特殊符号",
          trigger: "blur",
        },
      ],
    };

    const login = async () => {
      if (!loginFormRef.value) return;

      loginFormRef.value.validate((valid) => {
        if (valid) {
          loading.value = true;
          axios.post('/api/login', loginForm.value)
            .then(response => {
              localStorage.setItem("token", response.data.token);
              localStorage.setItem("user", JSON.stringify(response.data.user));
              
              // 检查是否有重定向路径
              const redirectPath = localStorage.getItem('redirectPath');
              if (redirectPath) {
                localStorage.removeItem('redirectPath');
                router.push(redirectPath);
              } else {
                router.push("/plans");
              }
            })
            .catch(error => {
              console.error("Login failed:", error);
              const errorMessage = error.response?.data?.error || 
                                   error.response?.data?.message || 
                                   "登录失败，请检查手机号和密码";
              ElMessage.error(errorMessage);
            })
            .finally(() => {
              loading.value = false;
            });
        }
      });
    };

    const navigateToRegister = () => {
      router.push("/register");
    };

    return {
      loginForm,
      loginRules,
      loginFormRef,
      loading,
      login,
      navigateToRegister,
      Phone,
      Lock,
    };
  },
};
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: var(--spacing-lg);
}

.login-container {
  display: flex;
  width: 100%;
  max-width: 1000px;
  background: var(--card-background);
  border-radius: var(--radius-xl);
  box-shadow: var(--shadow-xl);
  overflow: hidden;
  animation: scaleIn 0.5s ease-out;
}

.login-left {
  flex: 1;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: var(--spacing-2xl);
  display: flex;
  align-items: center;
  justify-content: center;
}

.welcome-content {
  color: #fff;
}

.welcome-title {
  font-size: var(--font-size-3xl);
  font-weight: var(--font-weight-bold);
  margin-bottom: var(--spacing-sm);
  animation: slideInLeft 0.6s ease-out;
}

.welcome-subtitle {
  font-size: var(--font-size-lg);
  opacity: 0.9;
  margin-bottom: var(--spacing-2xl);
  animation: slideInLeft 0.6s ease-out 0.1s backwards;
}

.features {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
}

.feature-item {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
  animation: slideInLeft 0.6s ease-out backwards;
}

.feature-item:nth-child(1) {
  animation-delay: 0.2s;
}

.feature-item:nth-child(2) {
  animation-delay: 0.3s;
}

.feature-item:nth-child(3) {
  animation-delay: 0.4s;
}

.feature-icon {
  font-size: 32px;
  background: rgba(255, 255, 255, 0.2);
  padding: var(--spacing-sm);
  border-radius: var(--radius-md);
}

.feature-text h3 {
  font-size: var(--font-size-base);
  font-weight: var(--font-weight-semibold);
  margin-bottom: var(--spacing-xs);
}

.feature-text p {
  font-size: var(--font-size-sm);
  opacity: 0.8;
}

.login-right {
  flex: 1;
  padding: var(--spacing-2xl);
  display: flex;
  align-items: center;
  justify-content: center;
}

.login-card {
  width: 100%;
  max-width: 360px;
}

.card-header {
  text-align: center;
  margin-bottom: var(--spacing-xl);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--spacing-xs);
}

.logo-wrapper {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 64px;
  height: 64px;
  background: var(--primary-gradient);
  border-radius: var(--radius-lg);
  margin-bottom: var(--spacing-md);
}

.logo-icon {
  font-size: 32px;
  color: #fff;
}

.card-title {
  font-size: var(--font-size-xl);
  font-weight: var(--font-weight-bold);
  color: var(--text-primary);
  margin-bottom: var(--spacing-sm);
  display: block;
  line-height: 1.5;
}

.card-subtitle {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
  display: block;
  line-height: 1.5;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.login-form :deep(.el-input__wrapper) {
  padding: 8px 16px;
}

.login-btn {
  width: 100%;
  height: 48px;
  font-size: var(--font-size-base);
  font-weight: var(--font-weight-semibold);
  border-radius: var(--radius-md);
  margin-top: var(--spacing-sm);
}

.form-footer {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--spacing-xs);
  margin-top: var(--spacing-md);
}

.footer-text {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
}

@media (max-width: 768px) {
  .login-container {
    flex-direction: column;
    max-width: 400px;
  }
  
  .login-left {
    display: none;
  }
  
  .login-right {
    padding: var(--spacing-xl);
  }
}

@media (max-width: 480px) {
  .login-page {
    padding: var(--spacing-md);
  }
  
  .login-right {
    padding: var(--spacing-lg);
  }
}
</style>
