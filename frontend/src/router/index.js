import { createRouter, createWebHistory } from 'vue-router'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('../views/Login.vue')
  },
  {
    path: '/register',
    name: 'Register',
    component: () => import('../views/Register.vue')
  },
  {
    path: '/plans',
    name: 'Plans',
    component: () => import('../views/Plans.vue')
  },
  {
    path: '/tasks',
    name: 'Tasks',
    component: () => import('../views/Tasks.vue')
  },
  {
    path: '/dimensions',
    name: 'Dimensions',
    component: () => import('../views/Dimensions.vue')
  },
  {
    path: '/questions',
    name: 'Questions',
    component: () => import('../views/Questions.vue')
  },
  {
    path: '/reports',
    name: 'Reports',
    component: () => import('../views/Reports.vue')
  },
  {
    path: '/profile',
    name: 'Profile',
    component: () => import('../views/Profile.vue')
  },
  {
    path: '/users',
    name: 'Users',
    component: () => import('../views/Users.vue')
  },
  {
    path: '/departments',
    name: 'Departments',
    component: () => import('../views/Departments.vue')
  },
  {
    path: '/roles',
    name: 'Roles',
    component: () => import('../views/Roles.vue')
  },
  {
    path: '/permissions',
    name: 'Permissions',
    component: () => import('../views/Permissions.vue')
  },
  {
    path: '/scoring-rules',
    name: 'ScoringRules',
    component: () => import('../views/ScoringRules.vue')
  },
  {
    path: '/scoring-levels',
    name: 'ScoringLevels',
    component: () => import('../views/ScoringLevels.vue')
  },
  {
    path: '/my-evaluations',
    name: 'MyEvaluations',
    component: () => import('../views/MyEvaluations.vue')
  },
  {
    path: '/',
    redirect: '/login'
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

// 路由守卫
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('token')
  const isLoggedIn = !!token && token !== ''
  const publicPages = ['/login', '/register']
  const authRequired = !publicPages.includes(to.path)

  if (authRequired && !isLoggedIn) {
    next('/login')
  } else if (isLoggedIn && publicPages.includes(to.path)) {
    // 当用户已登录且访问登录或注册页面时，重定向到评估计划页面
    next('/plans')
  } else {
    next()
  }
})

export default router