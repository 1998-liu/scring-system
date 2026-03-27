<template>
  <div class="roles-container">
    <div class="page-header">
      <h2 class="page-title">角色管理</h2>
      <el-button type="primary" @click="addRole">
        <el-icon><Plus /></el-icon>
        新增角色
      </el-button>
    </div>
    
    <el-card class="content-card">
      <div class="roles-content">
        <div class="role-list">
          <div class="list-header">
            <h3 class="list-title">角色列表</h3>
            <el-input
              v-model="searchQuery"
              placeholder="搜索角色名称或描述"
              class="search-input"
              @input="handleSearch"
              clearable
            >
              <template #prefix>
                <el-icon><Search /></el-icon>
              </template>
            </el-input>
          </div>
          <el-table
            :data="roleHierarchy"
            class="role-table"
            row-key="id"
            :tree-props="{ children: 'children', hasChildren: 'hasChildren' }"
            default-expand-all
          >
            <el-table-column prop="id" label="ID" width="80" />
            <el-table-column prop="name" label="角色名称" />
            <el-table-column prop="description" label="角色描述" />
            <el-table-column label="操作" width="350">
              <template #default="scope">
                <div class="action-buttons">
                  <el-button type="primary" size="small" @click="editRole(scope.row)">
                    <el-icon><Edit /></el-icon>
                    编辑
                  </el-button>
                  <el-button type="warning" size="small" @click="assignPermissions(scope.row)" :disabled="scope.row.id === currentUserRoleId">
                    <el-icon><Key /></el-icon>
                    分配权限
                  </el-button>
                  <el-button type="info" size="small" @click="viewPermissionDifferences(scope.row)" :disabled="!scope.row.parent_id">
                    <el-icon><Document /></el-icon>
                    权限差异
                  </el-button>
                  <el-button type="danger" size="small" @click="confirmDeleteRole(scope.row.id)" :disabled="scope.row.id === currentUserRoleId">
                    <el-icon><Delete /></el-icon>
                    删除
                  </el-button>
                </div>
              </template>
            </el-table-column>
          </el-table>
        </div>
      </div>
    </el-card>

    <!-- 新增/编辑角色对话框 -->
    <el-dialog 
      v-model="addRoleVisible" 
      :title="isEdit ? '编辑角色' : '新增角色'"
      width="500px"
      destroy-on-close
    >
      <el-form :model="roleForm" :rules="roleRules" ref="roleFormRef" label-width="100px">
        <el-form-item label="角色名称" prop="name">
          <el-input v-model="roleForm.name" placeholder="请输入角色名称" />
        </el-form-item>
        <el-form-item label="角色描述" prop="description">
          <el-input 
            v-model="roleForm.description" 
            type="textarea" 
            placeholder="请输入角色描述"
            :rows="3"
          />
        </el-form-item>
        <el-form-item label="父角色">
          <el-input
            v-model="parentRoleSearchQuery"
            placeholder="搜索父角色"
            style="margin-bottom: 10px"
            clearable
          >
            <template #prefix>
              <el-icon><Search /></el-icon>
            </template>
          </el-input>
          <el-tree
            ref="parentRoleTree"
            :data="filteredRoleHierarchy"
            node-key="id"
            :props="{ label: 'name', children: 'children' }"
            default-expand-all
            :expand-on-click-node="false"
            :filter-node-method="filterParentRole"
            @node-click="handleParentRoleClick"
            :current-node-key="roleForm.parent_id"
            highlight-current
            class="parent-role-tree"
          >
            <template #default="{ node }">
              <span class="role-node">
                {{ node.label }}
              </span>
            </template>
          </el-tree>
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="addRoleVisible = false">取消</el-button>
          <el-button type="primary" @click="saveRole">保存</el-button>
        </span>
      </template>
    </el-dialog>

    <!-- 分配权限对话框 -->
    <el-dialog 
      v-model="assignPermissionsVisible" 
      :title="`分配权限 - ${currentRole.name}`"
      width="500px"
      destroy-on-close
    >
      <el-tree
        :data="permissionTree"
        show-checkbox
        node-key="id"
        :default-expanded-keys="expandedKeys"
        :default-checked-keys="checkedKeys"
        @check-change="handleCheckChange"
        :disabled="currentRole.id === currentUserRoleId"
        class="permission-tree"
      >
        <template #default="{ node }">
          <span class="permission-node">
            <span v-if="currentRole.id === currentUserRoleId" class="disabled-badge">当前角色</span>
            {{ node.label }}
          </span>
        </template>
      </el-tree>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="assignPermissionsVisible = false">取消</el-button>
          <el-button type="primary" @click="savePermissions" :disabled="currentRole.id === currentUserRoleId">保存</el-button>
        </span>
      </template>
    </el-dialog>

    <!-- 权限差异对话框 -->
    <el-dialog v-model="permissionDifferencesVisible" :title="`权限差异 - ${currentRole.name}`">
      <div v-if="permissionDifferences">
        <el-card class="permission-section" v-if="permissionDifferences.inherited.length > 0">
          <template #header>
            <div class="card-header">
              <h4>继承的权限</h4>
            </div>
          </template>
          <el-tag v-for="permission in permissionDifferences.inherited" :key="permission.id" size="small" style="margin: 5px;">{{ permission.display_name }}</el-tag>
        </el-card>
        <el-card class="permission-section" v-if="permissionDifferences.additional.length > 0">
          <template #header>
            <div class="card-header">
              <h4>额外的权限</h4>
            </div>
          </template>
          <el-tag type="success" v-for="permission in permissionDifferences.additional" :key="permission.id" size="small" style="margin: 5px;">{{ permission.display_name }}</el-tag>
        </el-card>
        <el-card class="permission-section" v-if="permissionDifferences.removed.length > 0">
          <template #header>
            <div class="card-header">
              <h4>移除的权限</h4>
            </div>
          </template>
          <el-tag type="danger" v-for="permission in permissionDifferences.removed" :key="permission.id" size="small" style="margin: 5px;">{{ permission.display_name }}</el-tag>
        </el-card>
      </div>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="permissionDifferencesVisible = false">关闭</el-button>
        </span>
      </template>
    </el-dialog>

    <!-- 确认删除对话框 -->
    <CustomConfirmDialog
      v-model:visible="confirmDialogVisible"
      message="确定要删除这个角色吗？"
      @confirm="deleteRole"
    />
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue'
import { ElMessage } from 'element-plus'
import CustomConfirmDialog from '../components/CustomConfirmDialog.vue'
import axios from 'axios'
import { Search, Plus, Edit, Key, Document, Delete } from '@element-plus/icons-vue'

export default {
  name: 'Roles',
  components: {
    Search,
    Plus,
    Edit,
    Key,
    Document,
    Delete,
    CustomConfirmDialog
  },
  setup() {
    const roles = ref([])
    const roleList = ref([])
    const roleHierarchy = ref([])
    const permissions = ref([])
    const addRoleVisible = ref(false)
    const assignPermissionsVisible = ref(false)
    const permissionDifferencesVisible = ref(false)
    const confirmDialogVisible = ref(false)
    const roleFormRef = ref(null)
    const isEdit = ref(false)
    const roleForm = ref({
      name: '',
      description: '',
      parent_id: null
    })
    const currentRole = ref({ id: null, name: '' })
    const currentUser = ref({ id: null, role_id: null })
    const currentUserRoleId = ref(null)
    const permissionDifferences = ref(null)
    const searchQuery = ref('')
    const parentRoleSearchQuery = ref('')
    let deleteRoleId = ref(null)
    const pagination = ref({
      page: 1,
      pageSize: 10,
      total: 0,
      sortBy: 'id',
      sortOrder: 'asc'
    })

    // 过滤后的角色层级数据
    const filteredRoleHierarchy = computed(() => {
      console.log('Computing filteredRoleHierarchy...')
      console.log('Role hierarchy:', roleHierarchy.value)
      console.log('Available parent roles:', availableParentRoles.value)
      
      // 首先获取可用的父角色ID列表
      const availableRoleIds = availableParentRoles.value.map(role => role.id)
      console.log('Available role IDs:', availableRoleIds)
      
      // 过滤角色层级，只包含可用的父角色
      const filterHierarchy = (nodes) => {
        if (!nodes || !Array.isArray(nodes)) {
          console.log('Invalid nodes:', nodes)
          return []
        }
        return nodes
          .filter(node => availableRoleIds.includes(node.id))
          .map(node => ({
            ...node,
            children: node.children ? filterHierarchy(node.children) : []
          }))
      }
      
      // 应用过滤
      let filtered = filterHierarchy(roleHierarchy.value)
      console.log('Filtered hierarchy:', filtered)
      
      // 如果有搜索查询，应用搜索过滤
      if (parentRoleSearchQuery.value) {
        const searchFilter = (nodes, query) => {
          if (!nodes || !Array.isArray(nodes)) {
            return []
          }
          return nodes
            .filter(node => {
              const matches = node.name.toLowerCase().includes(query.toLowerCase())
              const hasMatchingChild = node.children && searchFilter(node.children, query).length > 0
              return matches || hasMatchingChild
            })
            .map(node => ({
              ...node,
              children: node.children ? searchFilter(node.children, query) : []
            }))
        }
        filtered = searchFilter(filtered, parentRoleSearchQuery.value)
        console.log('Filtered with search:', filtered)
      }
      
      return filtered
    })

    // 过滤父角色树节点
    const filterParentRole = (value, data) => {
      if (!value) return true
      return data.name.toLowerCase().includes(value.toLowerCase())
    }

    // 处理父角色点击事件
    const handleParentRoleClick = (data) => {
      roleForm.value.parent_id = data.id
    }

    const roleRules = {
      name: [
        { required: true, message: '请输入角色名称', trigger: 'blur' }
      ]
    }

    // 权限树数据
    const permissionTree = ref([])
    const expandedKeys = ref([])
    const checkedKeys = ref([])
    const defaultExpandedKeys = ref([])

    // 可用的父角色（排除当前编辑的角色及其子角色）
    const availableParentRoles = computed(() => {
      if (!isEdit.value) {
        return roles.value
      } else {
        // 排除当前角色及其子角色
        const excludeIds = [roleForm.value.id]
        
        // 递归获取所有子角色ID
        const getChildIds = (roleId) => {
          const role = roles.value.find(r => r.id === roleId)
          if (role) {
            const children = roles.value.filter(r => r.parent_id === roleId)
            children.forEach(child => {
              excludeIds.push(child.id)
              getChildIds(child.id)
            })
          }
        }
        
        getChildIds(roleForm.value.id)
        return roles.value.filter(role => !excludeIds.includes(role.id))
      }
    })

    const loadRoles = async () => {
      try {
        const response = await axios.get('/api/roles', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        roles.value = response.data.items.map(role => ({
          ...role,
          id: Number(role.id),
          parent_id: role.parent_id ? Number(role.parent_id) : null
        }))
      } catch (error) {
        console.error('Failed to load roles:', error)
      }
    }

    const loadRoleList = async () => {
      try {
        const response = await axios.get('/api/roles', {
          params: {
            page: pagination.value.page,
            pageSize: pagination.value.pageSize,
            sortBy: pagination.value.sortBy,
            sortOrder: pagination.value.sortOrder,
            search: searchQuery.value
          },
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        roleList.value = response.data.items
        pagination.value.total = response.data.total
      } catch (error) {
        console.error('Failed to load role list:', error)
      }
    }

    // 递归转换角色层级数据的类型
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

    // 收集所有角色ID用于默认展开
    const collectAllRoleIds = (nodes) => {
      const ids = []
      if (!nodes || !Array.isArray(nodes)) {
        return ids
      }
      nodes.forEach(node => {
        ids.push(node.id)
        if (node.children && node.children.length > 0) {
          ids.push(...collectAllRoleIds(node.children))
        }
      })
      return ids
    }

    const loadRoleHierarchy = async () => {
      try {
        const response = await axios.get('/api/roles/hierarchy', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        roleHierarchy.value = convertRoleHierarchyTypes(response.data)
        console.log('Role hierarchy loaded:', roleHierarchy.value)
        // 收集所有角色ID并设置为默认展开
        defaultExpandedKeys.value = collectAllRoleIds(roleHierarchy.value)
        console.log('Default expanded keys:', defaultExpandedKeys.value)
      } catch (error) {
        console.error('Failed to load role hierarchy:', error)
      }
    }

    const loadPermissions = async () => {
      try {
        const response = await axios.get('/api/permissions', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        permissions.value = response.data
        buildPermissionTree()
      } catch (error) {
        console.error('Failed to load permissions:', error)
      }
    }

    const loadCurrentUser = async () => {
      try {
        const response = await axios.get('/api/me', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        currentUser.value = response.data
        if (response.data.roles && response.data.roles.length > 0) {
          currentUserRoleId.value = Number(response.data.roles[0].id)
        }
      } catch (error) {
        console.error('Failed to load current user:', error)
      }
    }

    // 构建权限树
    const buildPermissionTree = () => {
      const tree = []
      const permissionMap = {}

      // 先创建所有权限节点
      permissions.value.forEach(permission => {
        permissionMap[permission.id] = {
          id: permission.id,
          label: permission.display_name,
          children: []
        }
      })

      // 构建树结构
      permissions.value.forEach(permission => {
        if (permission.parent_id === null) {
          // 根节点
          tree.push(permissionMap[permission.id])
          expandedKeys.value.push(permission.id)
        } else {
          // 子节点
          if (permissionMap[permission.parent_id]) {
            permissionMap[permission.parent_id].children.push(permissionMap[permission.id])
          }
        }
      })

      permissionTree.value = tree
    }

    // 获取父角色名称
    const getParentRoleName = (parentId) => {
      if (!parentId) return '无'
      const parentRole = roles.value.find(role => role.id == parentId)
      return parentRole ? parentRole.name : '未知'
    }

    // 处理搜索
    const handleSearch = () => {
      pagination.value.page = 1
      loadRoleList()
    }

    // 处理排序
    const handleSortChange = (sort) => {
      pagination.value.sortBy = sort.prop
      pagination.value.sortOrder = sort.order
      loadRoleList()
    }

    // 处理页码变化
    const handleCurrentChange = (page) => {
      pagination.value.page = page
      loadRoleList()
    }

    // 处理每页大小变化
    const handleSizeChange = (pageSize) => {
      pagination.value.pageSize = pageSize
      pagination.value.page = 1
      loadRoleList()
    }

    const addRole = () => {
      console.log('addRole function called')
      isEdit.value = false
      roleForm.value = {
        name: '',
        description: '',
        parent_id: null
      }
      addRoleVisible.value = true
      console.log('addRole function executed, addRoleVisible:', addRoleVisible.value)
    }

    const editRole = (role) => {
      console.log('editRole function called', role)
      roleForm.value = {
        ...role,
        id: Number(role.id),
        parent_id: role.parent_id ? Number(role.parent_id) : null
      }
      isEdit.value = true
      addRoleVisible.value = true
      console.log('editRole function executed, addRoleVisible:', addRoleVisible.value)
    }

    const handleRoleClick = (data) => {
      currentRole.value = data
    }

    const assignPermissions = async (role) => {
      currentRole.value = role
      
      try {
        // 获取角色的现有权限
        const response = await axios.get(`/api/roles/${role.id}/permissions`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        const rolePermissions = response.data
        checkedKeys.value = rolePermissions.map(p => p.id)
        assignPermissionsVisible.value = true
      } catch (error) {
        console.error('Failed to load role permissions:', error)
        ElMessage.error('加载权限失败，请重试')
      }
    }

    const viewPermissionDifferences = async (role) => {
      currentRole.value = role
      
      try {
        const response = await axios.get(`/api/roles/${role.id}/permission-differences`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        permissionDifferences.value = response.data
        permissionDifferencesVisible.value = true
      } catch (error) {
        console.error('Failed to load permission differences:', error)
        ElMessage.error('加载权限差异失败，请重试')
      }
    }

    const handleCheckChange = (data, checked, indeterminate) => {
      // 处理权限选择变化
    }

    const saveRole = async () => {
      if (!roleFormRef.value) return
      
      await roleFormRef.value.validate(async (valid) => {
        if (valid) {
          try {
            if (isEdit.value) {
              await axios.put(`/api/roles/${roleForm.value.id}`, roleForm.value, {
                headers: {
                  'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
              })
            } else {
              await axios.post('/api/roles', roleForm.value, {
                headers: {
                  'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
              })
            }
            addRoleVisible.value = false
            loadRoles()
            loadRoleList()
            loadRoleHierarchy()
            ElMessage.success('保存成功')
          } catch (error) {
            console.error('Failed to save role:', error)
            ElMessage.error('保存失败，请重试')
          }
        }
      })
    }

    const savePermissions = async () => {
      if (currentRole.value.id === currentUserRoleId.value) {
        ElMessage.warning('无法编辑当前用户的角色权限')
        return
      }
      
      try {
        await axios.post(`/api/roles/${currentRole.value.id}/permissions`, {
          permissions: checkedKeys.value
        }, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        assignPermissionsVisible.value = false
        ElMessage.success('权限分配成功')
      } catch (error) {
        console.error('Failed to save permissions:', error)
        ElMessage.error('权限分配失败，请重试')
      }
    }

    const confirmDeleteRole = (id) => {
      if (id === currentUserRoleId.value) {
        ElMessage.warning('无法删除当前用户的角色')
        return
      }
      deleteRoleId.value = id
      confirmDialogVisible.value = true
    }

    const deleteRole = async () => {
      try {
        await axios.delete(`/api/roles/${deleteRoleId.value}`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        loadRoles()
        loadRoleList()
        loadRoleHierarchy()
        ElMessage.success('删除成功')
      } catch (error) {
        console.error('Failed to delete role:', error)
        ElMessage.error('删除失败，请重试')
      }
    }



    onMounted(() => {
      loadRoles()
      loadRoleList()
      loadRoleHierarchy()
      loadPermissions()
      loadCurrentUser()
    })

    return {
      roles,
      roleList,
      roleHierarchy,
      addRoleVisible,
      assignPermissionsVisible,
      permissionDifferencesVisible,
      confirmDialogVisible,
      roleForm,
      roleRules,
      roleFormRef,
      isEdit,
      currentRole,
      currentUserRoleId,
      permissionTree,
      expandedKeys,
      checkedKeys,
      permissionDifferences,
      searchQuery,
      parentRoleSearchQuery,
      pagination,
      availableParentRoles,
      filteredRoleHierarchy,
      defaultExpandedKeys,
      loadRoles,
      loadRoleList,
      loadRoleHierarchy,
      loadPermissions,
      addRole,
      editRole,
      handleRoleClick,
      assignPermissions,
      viewPermissionDifferences,
      handleCheckChange,
      saveRole,
      savePermissions,
      deleteRole,
      confirmDeleteRole,
      getParentRoleName,
      handleSearch,
      handleSortChange,
      handleCurrentChange,
      handleSizeChange,
      filterParentRole,
      handleParentRoleClick
    }
  }
}
</script>

<style scoped>
.roles-container {
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

.roles-content {
  margin-top: var(--spacing-lg);
}

.role-list {
  margin-bottom: var(--spacing-2xl);
}

.list-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-md);
}

.list-title {
  margin: 0;
  font-size: var(--font-size-lg);
  font-weight: var(--font-weight-semibold);
  color: var(--text-primary);
}

.search-input {
  width: 300px;
}

.search-input :deep(.el-input__wrapper) {
  border-radius: var(--radius-lg);
}

.role-table {
  border-radius: var(--radius-lg);
  overflow: hidden;
}

.role-table :deep(.el-table__header-wrapper) {
  background: var(--background-color);
}

.role-table :deep(.el-table__row) {
  transition: all var(--transition-fast);
}

.role-table :deep(.el-table__row:hover > td) {
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

.pagination {
  margin-top: var(--spacing-lg);
  display: flex;
  justify-content: flex-end;
}

.permission-node {
  display: flex;
  align-items: center;
  gap: var(--spacing-sm);
}

.disabled-badge {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  color: white;
  font-size: var(--font-size-xs);
  padding: 2px 8px;
  border-radius: var(--radius-md);
  font-weight: var(--font-weight-medium);
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: var(--spacing-sm);
}

.permission-section {
  margin-bottom: var(--spacing-md);
  border-radius: var(--radius-lg);
  overflow: hidden;
}

.permission-section .card-header {
  margin-bottom: var(--spacing-sm);
}

.role-node {
  font-size: var(--font-size-sm);
  padding: var(--spacing-xs) var(--spacing-sm);
  border-radius: var(--radius-md);
  transition: all var(--transition-fast);
}

.role-node:hover {
  background-color: rgba(79, 70, 229, 0.08);
}

.parent-role-tree {
  border: 1px solid var(--border-color);
  border-radius: var(--radius-lg);
  padding: var(--spacing-md);
  max-height: 300px;
  overflow-y: auto;
  background: var(--background-color);
}

.parent-role-tree :deep(.el-tree-node__content) {
  height: 36px;
  border-radius: var(--radius-md);
  margin: 2px 0;
}

.parent-role-tree :deep(.el-tree-node.is-current > .el-tree-node__content) {
  background: var(--primary-gradient);
  color: #fff;
}

.permission-tree {
  border: 1px solid var(--border-color);
  border-radius: var(--radius-lg);
  padding: var(--spacing-md);
  max-height: 400px;
  overflow-y: auto;
}

@media (max-width: 768px) {
  .list-header {
    flex-direction: column;
    align-items: flex-start;
    gap: var(--spacing-md);
  }
  
  .search-input {
    width: 100%;
  }
  
  .parent-role-tree,
  .permission-tree {
    max-height: 200px;
  }
  
  .role-node {
    font-size: var(--font-size-xs);
  }
  
  .action-buttons {
    flex-wrap: wrap;
  }
}
</style>