import { createApp } from 'vue'
import App from './App.vue'
import router from './router'
import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'
import zhCn from 'element-plus/dist/locale/zh-cn.mjs'
import axios from 'axios'
import './styles/global.css'

// 配置axios拦截器
axios.interceptors.request.use(
  config => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  error => {
    console.error('Request error:', error)
    return Promise.reject(error)
  }
)

// 响应拦截器 - 处理401错误
axios.interceptors.response.use(
  response => {
    return response
  },
  error => {
    // 处理401错误（token过期或无效）
    if (error.response && error.response.status === 401) {
      console.log('Token expired or invalid, redirecting to login...')
      
      // 获取当前页面路径
      const currentPath = window.location.pathname
      
      // 如果当前已经在登录页或注册页，不执行跳转，直接返回错误让页面自己处理
      if (currentPath === '/login' || currentPath === '/register') {
        return Promise.reject(error)
      }
      
      // 清除本地存储的token和用户信息
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      
      // 保存当前页面URL，以便登录后恢复
      localStorage.setItem('redirectPath', currentPath)
      
      // 跳转到登录页面
      window.location.href = '/login'
    } else {
      console.error('Response error:', error)
    }
    return Promise.reject(error)
  }
)

const app = createApp(App)
app.use(ElementPlus, {
  locale: zhCn
})
app.use(router)
app.mount('#app')