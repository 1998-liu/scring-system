<template>
  <div class="permissions-container">
    <el-card>
      <template #header>
        <div class="card-header">
          <h2>权限管理</h2>
          <el-select v-model="selectedRoleId" placeholder="选择角色" @change="loadRolePermissions">
            <el-option v-for="role in roles" :key="role.id" :label="role.name" :value="role.id" />
          </el-select>
        </div>
      </template>
      <div class="permissions-content">
        <el-tree
          ref="tree"
          :data="permissionTree"
          show-checkbox
          node-key="id"
          @check-change="handleCheckChange"
          :props="treeProps"
        />
      </div>
      <div class="button-group">
        <el-button @click="selectAll">全选</el-button>
        <el-button @click="deselectAll">取消全选</el-button>
        <el-button type="primary" @click="savePermissions" :loading="loading">保存权限</el-button>
      </div>
    </el-card>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import axios from 'axios'

export default {
  name: 'Permissions',
  setup() {
    const roles = ref([])
    const permissions = ref([])
    const permissionTree = ref([])
    const selectedRoleId = ref('')
    const selectedPermissions = ref([])
    const loading = ref(false)
    const tree = ref(null)
    
    const treeProps = {
      children: 'children',
      label: 'label'
    }

    const loadRoles = async () => {
      try {
        const response = await axios.get('/api/roles', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        roles.value = response.data
        if (roles.value.length > 0) {
          selectedRoleId.value = roles.value[0].id
          await loadRolePermissions()
        }
      } catch (error) {
        console.error('Failed to load roles:', error)
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
        // 构建权限树
        buildPermissionTree()
      } catch (error) {
        console.error('Failed to load permissions:', error)
      }
    }

    const buildPermissionTree = () => {
      // 权限分组
      const permissionGroups = {
        '用户管理': ['view_users', 'create_users', 'edit_users', 'delete_users'],
        '角色管理': ['view_roles', 'create_roles', 'edit_roles', 'delete_roles'],
        '部门管理': ['view_departments', 'create_departments', 'edit_departments', 'delete_departments'],
        '评估计划': ['view_plans', 'create_plans', 'edit_plans', 'delete_plans'],
        '评估任务': ['view_tasks', 'create_tasks', 'edit_tasks', 'delete_tasks'],
        '评估维度': ['view_dimensions', 'create_dimensions', 'edit_dimensions', 'delete_dimensions'],
        '评估问题': ['view_questions', 'create_questions', 'edit_questions', 'delete_questions'],
        '评估报告': ['view_reports', 'generate_reports']
      }
      
      const treeData = []
      
      // 遍历权限分组
      Object.entries(permissionGroups).forEach(([groupName, permissionNames]) => {
        const groupNode = {
          id: groupName,
          label: groupName,
          children: []
        }
        
        // 遍历权限名称
        permissionNames.forEach(permissionName => {
          const permission = permissions.value.find(p => p.name === permissionName)
          if (permission) {
            groupNode.children.push({
              id: permission.id,
              label: permission.display_name
            })
          }
        })
        
        treeData.push(groupNode)
      })
      
      permissionTree.value = treeData
    }

    const loadRolePermissions = async () => {
      if (!selectedRoleId.value) return
      
      try {
        // 先清空选中的权限
        selectedPermissions.value = []
        const response = await axios.get(`/api/roles/${selectedRoleId.value}/permissions`, {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        selectedPermissions.value = response.data.map(permission => permission.id)
        // 使用 setCheckedKeys 方法更新树的选中状态
        if (tree.value) {
          tree.value.setCheckedKeys(selectedPermissions.value)
        }
      } catch (error) {
        console.error('Failed to load role permissions:', error)
      }
    }

    const handleCheckChange = (data, checked, indeterminate) => {
      // 当点击父节点时，更新所有子节点的选中状态
      if (data.children) {
        data.children.forEach(child => {
          const index = selectedPermissions.value.indexOf(child.id)
          if (checked && index === -1) {
            selectedPermissions.value.push(child.id)
          } else if (!checked && index !== -1) {
            selectedPermissions.value.splice(index, 1)
          }
        })
      } else {
        // 处理叶子节点（实际的权限）
        const index = selectedPermissions.value.indexOf(data.id)
        if (checked && index === -1) {
          selectedPermissions.value.push(data.id)
        } else if (!checked && index !== -1) {
          selectedPermissions.value.splice(index, 1)
        }
      }
    }

    const savePermissions = async () => {
      if (!selectedRoleId.value) {
        ElMessage.warning('请选择角色')
        return
      }
      
      loading.value = true
      try {
        console.log('Saving permissions:', selectedPermissions.value)
        await axios.post(`/api/roles/${selectedRoleId.value}/permissions`, {
          permissions: selectedPermissions.value
        })
        // 重新加载角色权限，确保界面显示最新状态
        await loadRolePermissions()
        ElMessage.success('权限保存成功')
      } catch (error) {
        console.error('Failed to save permissions:', error)
        console.error('Error response:', error.response)
        ElMessage.error('权限保存失败，请重试')
      } finally {
        loading.value = false
      }
    }

    const selectAll = () => {
      // 获取所有权限的ID
      const allPermissionIds = []
      permissionTree.value.forEach(group => {
        group.children.forEach(permission => {
          allPermissionIds.push(permission.id)
        })
      })
      selectedPermissions.value = allPermissionIds
      // 使用 setCheckedKeys 方法更新树的选中状态
      if (tree.value) {
        tree.value.setCheckedKeys(selectedPermissions.value)
      }
    }

    const deselectAll = () => {
      selectedPermissions.value = []
      // 使用 setCheckedKeys 方法更新树的选中状态
      if (tree.value) {
        tree.value.setCheckedKeys(selectedPermissions.value)
      }
    }

    onMounted(async () => {
      // 先加载权限，确保权限树在加载角色权限之前构建完成
      await loadPermissions()
      await loadRoles()
    })

    return {
      roles,
      permissions,
      permissionTree,
      selectedRoleId,
      selectedPermissions,
      loading,
      treeProps,
      tree,
      loadRolePermissions,
      handleCheckChange,
      savePermissions,
      selectAll,
      deselectAll
    }
  }
}
</script>

<style scoped>
.permissions-container {
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

.permissions-content {
  margin: 20px 0;
  padding: 20px;
  border: 1px solid #e4e7ed;
  border-radius: 4px;
  background-color: #f5f7fa;
}

.el-checkbox {
  display: block;
  margin-bottom: 10px;
}

.button-group {
  display: flex;
  justify-content: flex-end;
  margin-top: 20px;
}
</style>