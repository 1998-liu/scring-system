<template>
  <div id="app">
    <el-container v-if="isLoggedIn" class="app-container">
      <!-- 侧边栏 -->
      <el-aside :width="sidebarWidth" class="app-sidebar">
        <div class="sidebar-header">
          <div class="logo">
            <el-icon class="logo-icon"><DataAnalysis /></el-icon>
            <span v-show="!isCollapsed" class="logo-text">360评估系统</span>
          </div>
          <el-button
            class="collapse-btn"
            :icon="isCollapsed ? Expand : Fold"
            @click="toggleSidebar"
            text
          />
        </div>
        
        <el-menu
          :default-active="activeMenu"
          class="sidebar-menu"
          :collapse="isCollapsed"
          :collapse-transition="true"
          :openeds="openKeys"
          :unique-opened="false"
          :menu-trigger="'click'"
          @select="handleMenuSelect"
          @open-change="handleOpenChange"
        >
          <!-- 我的评估菜单 - 所有用户可见 -->
          <el-menu-item index="my-evaluations">
            <el-icon><Finished /></el-icon>
            <span>我的评估</span>
          </el-menu-item>

          <!-- 评估管理菜单 -->
          <el-sub-menu v-if="hasPermission('evaluation')" index="evaluation">
            <template #title>
              <el-icon><Setting /></el-icon>
              <span>评估管理</span>
            </template>
            <el-menu-item index="plans">
              <el-icon><DocumentCopy /></el-icon>
              <span>评估计划</span>
            </el-menu-item>
            <el-menu-item index="tasks">
              <el-icon><List /></el-icon>
              <span>评估任务</span>
            </el-menu-item>
            <el-menu-item index="dimensions">
              <el-icon><Grid /></el-icon>
              <span>评估维度</span>
            </el-menu-item>
            <el-menu-item index="questions">
              <el-icon><QuestionFilled /></el-icon>
              <span>评估问题</span>
            </el-menu-item>
            <el-menu-item index="scoring-rules">
              <el-icon><TrendCharts /></el-icon>
              <span>评分规则</span>
            </el-menu-item>
            <el-menu-item index="scoring-levels">
              <el-icon><Medal /></el-icon>
              <span>评分等级</span>
            </el-menu-item>
            <el-menu-item v-if="hasPermission('reports')" index="reports">
              <el-icon><Document /></el-icon>
              <span>评估报告</span>
            </el-menu-item>
          </el-sub-menu>

          <!-- 评估任务菜单 - 非管理员可见 -->
          <el-menu-item v-else index="tasks">
            <el-icon><List /></el-icon>
            <span>评估任务</span>
          </el-menu-item>

          <!-- 系统管理菜单 -->
          <el-sub-menu v-if="hasPermission('system')" index="system">
            <template #title>
              <el-icon><Management /></el-icon>
              <span>系统管理</span>
            </template>
            <el-menu-item v-if="hasPermission('view_users') || hasPermission('create_users') || hasPermission('edit_users') || hasPermission('delete_users')" index="users">
              <el-icon><User /></el-icon>
              <span>用户管理</span>
            </el-menu-item>
            <el-menu-item v-if="hasPermission('view_departments') || hasPermission('create_departments') || hasPermission('edit_departments') || hasPermission('delete_departments')" index="departments">
              <el-icon><OfficeBuilding /></el-icon>
              <span>部门管理</span>
            </el-menu-item>
            <el-menu-item v-if="hasPermission('view_roles') || hasPermission('create_roles') || hasPermission('edit_roles') || hasPermission('delete_roles')" index="roles">
              <el-icon><Avatar /></el-icon>
              <span>角色管理</span>
            </el-menu-item>
            <el-menu-item v-if="hasPermission('view_permissions') || hasPermission('create_permissions') || hasPermission('edit_permissions') || hasPermission('delete_permissions')" index="permissions">
              <el-icon><Key /></el-icon>
              <span>权限管理</span>
            </el-menu-item>
          </el-sub-menu>

          <!-- 个人中心菜单 -->
          <el-menu-item index="profile">
            <el-icon><UserFilled /></el-icon>
            <span>个人中心</span>
          </el-menu-item>
        </el-menu>
      </el-aside>

      <!-- 主内容区 -->
      <el-container class="main-container">
        <!-- 顶部导航栏 -->
        <el-header class="app-header">
          <div class="header-left">
            <el-breadcrumb separator="/">
              <el-breadcrumb-item :to="{ path: '/' + activeMenu }">
                {{ currentPageTitle }}
              </el-breadcrumb-item>
            </el-breadcrumb>
          </div>
          <div class="header-right">
            <el-dropdown trigger="click" @command="handleCommand">
              <div class="user-dropdown">
                <el-avatar :size="36" class="user-avatar">
                  {{ user.name ? user.name.charAt(0).toUpperCase() : 'U' }}
                </el-avatar>
                <div class="user-info">
                  <span class="user-name">{{ user.name }}</span>
                  <span class="user-role">{{ userRoleName }}</span>
                </div>
                <el-icon class="dropdown-icon"><ArrowDown /></el-icon>
              </div>
              <template #dropdown>
                <el-dropdown-menu>
                  <el-dropdown-item command="profile">
                    <el-icon><User /></el-icon>
                    个人中心
                  </el-dropdown-item>
                  <el-dropdown-item divided command="logout">
                    <el-icon><SwitchButton /></el-icon>
                    退出登录
                  </el-dropdown-item>
                </el-dropdown-menu>
              </template>
            </el-dropdown>
          </div>
        </el-header>

        <!-- 内容区域 -->
        <el-main class="app-main">
          <transition name="fade" mode="out-in">
            <router-view />
          </transition>
        </el-main>
      </el-container>
    </el-container>

    <!-- 登录页面 -->
    <div v-else class="login-wrapper">
      <router-view />
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import {
  Setting,
  Document,
  User,
  ArrowDown,
  Management,
  DataAnalysis,
  Expand,
  Fold,
  DocumentCopy,
  List,
  Grid,
  QuestionFilled,
  OfficeBuilding,
  Avatar,
  Key,
  UserFilled,
  SwitchButton,
  TrendCharts,
  Medal,
  Finished,
} from "@element-plus/icons-vue";
import axios from "axios";

export default {
  name: "App",
  components: {
    Setting,
    Document,
    User,
    ArrowDown,
    Management,
    DataAnalysis,
    Expand,
    Fold,
    DocumentCopy,
    List,
    Grid,
    QuestionFilled,
    OfficeBuilding,
    Avatar,
    Key,
    UserFilled,
    SwitchButton,
    TrendCharts,
    Medal,
    Finished,
  },
  setup() {
    const router = useRouter();
    const user = ref({});
    const activeMenu = ref("plans");
    const isCollapsed = ref(false);
    const isLoggedIn = ref(!!localStorage.getItem("token"));
    const openKeys = ref([]);

    const sidebarWidth = computed(() => 
      isCollapsed.value ? '64px' : '240px'
    );

    const userRoleName = computed(() => {
      if (user.value.roles && user.value.roles.length > 0) {
        return user.value.roles[0].display_name || user.value.roles[0].name;
      }
      return '普通用户';
    });

    const currentPageTitle = computed(() => {
      const titles = {
        plans: '评估计划',
        tasks: '评估任务',
        dimensions: '评估维度',
        questions: '评估问题',
        reports: '评估报告',
        users: '用户管理',
        departments: '部门管理',
        roles: '角色管理',
        permissions: '权限管理',
        profile: '个人中心',
        'scoring-rules': '评分规则',
        'scoring-levels': '评分等级',
        'my-evaluations': '我的评估',
      };
      return titles[activeMenu.value] || '首页';
    });

    if (isLoggedIn.value && localStorage.getItem("token") === "") {
      isLoggedIn.value = false;
    }

    const updateActiveMenu = () => {
      const path = router.currentRoute.value.path;
      const menuKey = path.substring(1);
      if (menuKey) {
        activeMenu.value = menuKey;
      }
    };

    const toggleSidebar = () => {
      isCollapsed.value = !isCollapsed.value;
    };

    // 处理菜单展开/折叠
    const handleOpenChange = (keys) => {
      openKeys.value = keys;
      // 保存展开状态到localStorage
      localStorage.setItem('menuOpenKeys', JSON.stringify(keys));
    };

    // 加载保存的菜单展开状态
    const loadMenuOpenKeys = () => {
      const savedKeys = localStorage.getItem('menuOpenKeys');
      if (savedKeys) {
        try {
          openKeys.value = JSON.parse(savedKeys);
        } catch (error) {
          console.error('Failed to parse menu open keys:', error);
        }
      }
    };

    const updateLoginStatus = () => {
      isLoggedIn.value = !!localStorage.getItem("token");
      if (isLoggedIn.value && localStorage.getItem("token") === "") {
        isLoggedIn.value = false;
      }
    };

    window.addEventListener("storage", updateLoginStatus);

    router.beforeEach((to, from, next) => {
      isLoggedIn.value = !!localStorage.getItem("token");
      if (isLoggedIn.value && localStorage.getItem("token") === "") {
        isLoggedIn.value = false;
      }
      if (isLoggedIn.value) {
        loadUserInfo();
      }
      next();
    });

    router.afterEach((to) => {
      if (isLoggedIn.value) {
        updateActiveMenu();
      }
    });

    const logout = () => {
      localStorage.removeItem("token");
      localStorage.removeItem("user");
      localStorage.removeItem("menuOpenKeys");
      user.value = {};
      isLoggedIn.value = false;
      openKeys.value = [];
      setTimeout(() => {
        router.push("/login");
      }, 100);
    };

    const handleMenuSelect = (key) => {
      activeMenu.value = key;
      router.push(`/${key}`);
    };

    const handleCommand = (command) => {
      if (command === 'profile') {
        activeMenu.value = "profile";
        router.push("/profile");
      } else if (command === 'logout') {
        logout();
      }
    };

    const hasPermission = (permission) => {
      if (user.value.roles && user.value.roles.length > 0) {
        for (const role of user.value.roles) {
          if (role.permissions && role.permissions.length > 0) {
            for (const perm of role.permissions) {
              if (permission === 'evaluation' && (perm.name.includes('plans') || perm.name.includes('tasks') || perm.name.includes('dimensions') || perm.name.includes('questions'))) {
                return true;
              }
              if (permission === 'reports' && perm.name.includes('reports')) {
                return true;
              }
              if (permission === 'system' && (perm.name.includes('users') || perm.name.includes('roles') || perm.name.includes('departments') || perm.name.includes('permissions'))) {
                return true;
              }
              if (perm.name === permission) {
                return true;
              }
            }
          }
        }
      }
      return false;
    };

    const loadUserInfo = async () => {
      if (isLoggedIn.value) {
        try {
          const response = await axios.get("/api/me", {
            headers: {
              Authorization: `Bearer ${localStorage.getItem("token")}`,
            },
          });
          user.value = response.data;
          localStorage.setItem("user", JSON.stringify(response.data));
        } catch (error) {
          console.error("Failed to load user info:", error);
          logout();
        }
      }
    };

    onMounted(() => {
      isLoggedIn.value = !!localStorage.getItem("token");
      if (isLoggedIn.value && localStorage.getItem("token") === "") {
        isLoggedIn.value = false;
      }
      if (isLoggedIn.value) {
        const storedUser = localStorage.getItem("user");
        if (storedUser) {
          user.value = JSON.parse(storedUser);
        }
        loadUserInfo();
        updateActiveMenu();
        loadMenuOpenKeys();
      }
    });

    return {
      user,
      isLoggedIn,
      activeMenu,
      isCollapsed,
      sidebarWidth,
      userRoleName,
      currentPageTitle,
      openKeys,
      Expand,
      Fold,
      logout,
      toggleSidebar,
      handleMenuSelect,
      handleOpenChange,
      handleCommand,
      hasPermission,
    };
  },
};
</script>

<style>
.app-container {
  min-height: 100vh;
  background-color: var(--background-color);
}

.app-sidebar {
  background: linear-gradient(180deg, #1e1e2d 0%, #151521 100%);
  box-shadow: 2px 0 8px rgba(0, 0, 0, 0.1);
  transition: width var(--transition-normal);
  overflow: hidden;
}

.sidebar-header {
  height: var(--header-height);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 var(--spacing-md);
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.logo {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
}

.logo-icon {
  font-size: 28px;
  color: var(--primary-light);
}

.logo-text {
  font-size: var(--font-size-lg);
  font-weight: var(--font-weight-bold);
  color: #fff;
  white-space: nowrap;
}

.collapse-btn {
  color: rgba(255, 255, 255, 0.7) !important;
}

.collapse-btn:hover {
  color: #fff !important;
  background-color: rgba(255, 255, 255, 0.1) !important;
}

.sidebar-menu {
  border-right: none !important;
  background-color: transparent !important;
  height: calc(100vh - var(--header-height));
}

.sidebar-menu .el-menu-item,
.sidebar-menu .el-sub-menu__title {
  color: rgba(255, 255, 255, 0.7) !important;
  height: 48px;
  line-height: 48px;
  margin: 4px 8px;
  border-radius: var(--radius-md);
  transition: all var(--transition-normal);
}

.sidebar-menu .el-menu-item:hover,
.sidebar-menu .el-sub-menu__title:hover {
  background-color: rgba(255, 255, 255, 0.08) !important;
  color: #fff !important;
}

.sidebar-menu .el-menu-item.is-active {
  background: var(--primary-gradient) !important;
  color: #fff !important;
}

.app-sidebar .sidebar-menu .el-sub-menu .el-menu .el-menu-item {
  padding-left: 50px !important;
  min-width: auto;
  color: rgba(255, 255, 255, 0.9) !important;
  height: 48px;
  line-height: 48px;
  margin: 4px 8px;
  border-radius: var(--radius-md);
  transition: all var(--transition-normal);
  background-color: transparent !important;
  position: relative !important;
  z-index: 2 !important;
}

.app-sidebar .sidebar-menu .el-sub-menu .el-menu .el-menu-item:hover {
  background-color: rgba(255, 255, 255, 0.08) !important;
  color: #fff !important;
}

.app-sidebar .sidebar-menu .el-sub-menu .el-menu .el-menu-item.is-active {
  background: var(--primary-gradient) !important;
  color: #fff !important;
}

.app-sidebar .sidebar-menu .el-sub-menu__content {
  padding: 0 !important;
  background-color: transparent !important;
  position: relative !important;
  z-index: 0 !important;
  margin: 0 !important;
  border: none !important;
  box-shadow: none !important;
}

.app-sidebar .sidebar-menu .el-sub-menu {
  position: relative !important;
}

.app-sidebar .sidebar-menu .el-sub-menu .el-menu {
  background-color: transparent !important;
  position: static !important;
  width: 100% !important;
  margin: 0 !important;
  padding: 0 !important;
  border: none !important;
  box-shadow: none !important;
}

.app-sidebar .sidebar-menu .el-sub-menu__title {
  position: relative !important;
  z-index: 1 !important;
}

.app-sidebar .sidebar-menu .el-menu-item {
  position: relative !important;
  z-index: 1 !important;
  overflow: visible !important;
  white-space: nowrap !important;
  text-overflow: ellipsis !important;
}

.main-container {
  background-color: var(--background-color);
}

.app-header {
  background: var(--card-background);
  box-shadow: var(--shadow-sm);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 var(--spacing-lg);
  height: var(--header-height);
  border-bottom: 1px solid var(--border-color);
}

.header-left {
  display: flex;
  align-items: center;
}

.header-right {
  display: flex;
  align-items: center;
  gap: var(--spacing-lg);
}

.user-dropdown {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
  cursor: pointer;
  padding: var(--spacing-xs) var(--spacing-sm);
  border-radius: var(--radius-md);
  transition: all var(--transition-fast);
}

.user-dropdown:hover {
  background-color: var(--background-color);
}

.user-avatar {
  background: var(--primary-gradient);
  color: #fff;
  font-weight: var(--font-weight-semibold);
}

.user-info {
  display: flex;
  flex-direction: column;
}

.user-name {
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
  color: var(--text-primary);
}

.user-role {
  font-size: var(--font-size-xs);
  color: var(--text-secondary);
}

.dropdown-icon {
  color: var(--text-secondary);
  transition: transform var(--transition-fast);
}

.user-dropdown:hover .dropdown-icon {
  transform: rotate(180deg);
}

.app-main {
  padding: var(--spacing-lg);
  min-height: calc(100vh - var(--header-height));
  overflow-y: auto;
}

.login-wrapper {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

@media (max-width: 768px) {
  .app-sidebar {
    position: fixed;
    left: 0;
    top: 0;
    height: 100vh;
    z-index: 1000;
  }
  
  .user-info {
    display: none;
  }
  
  .app-main {
    padding: var(--spacing-md);
  }
}
</style>
