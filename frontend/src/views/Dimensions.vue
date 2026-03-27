<template>
  <div class="dimensions-container">
    <div class="page-header">
      <h2 class="page-title">评估维度管理</h2>
    </div>

    <div class="dimensions-layout" v-if="manageableRoles.length > 0">
      <div class="roles-sidebar">
        <div class="sidebar-header">
          <span class="sidebar-title">角色列表</span>
        </div>
        <div class="roles-list">
          <div
            v-for="role in manageableRoles"
            :key="role.id"
            :class="['role-item', { active: selectedRole?.id === role.id }]"
            @click="selectRole(role)"
          >
            <span class="role-name">{{ role.name }}</span>
            <span class="role-desc">{{ role.description || "暂无描述" }}</span>
          </div>
        </div>
      </div>

      <div class="dimensions-content">
        <div class="content-header">
          <span class="content-title">{{ selectedRole?.name }} - 评估维度</span>
          <div class="header-buttons">
            <el-button type="primary" @click="openCreateDialog" :disabled="isAddDimensionDisabled">
              <el-icon><Plus /></el-icon>
              添加维度
            </el-button>
            <el-button type="primary" @click="openCopyDimensionDialog" :disabled="isCopyDimensionDisabled">
              <el-icon><CopyDocument /></el-icon>
              复制维度
            </el-button>
          </div>
        </div>

        <div class="dimensions-list" v-if="currentDimensions.length > 0">
          <div
            class="dimension-item"
            v-for="dimension in currentDimensions"
            :key="dimension.id"
          >
            <div class="dimension-info">
              <div class="dimension-name">{{ dimension.name }}</div>
              <div class="dimension-desc">
                {{ dimension.description || "暂无描述" }}
              </div>
            </div>
            <div class="dimension-weight">
              <span class="weight-label">权重</span>
              <span class="weight-value">{{ dimension.weight }}%</span>
            </div>
            <div class="dimension-actions">
              <el-button
                type="primary"
                size="small"
                @click="editDimension(dimension)"
              >
                <el-icon><Edit /></el-icon>
                编辑
              </el-button>
              <el-button
                type="danger"
                size="small"
                @click="deleteDimension(dimension.id)"
              >
                <el-icon><Delete /></el-icon>
                删除
              </el-button>
            </div>
          </div>

          <div class="weight-summary" :class="weightSummaryClass">
            <span class="summary-label">权重总和：</span>
            <span class="summary-value">{{ totalWeight }}%</span>
            <span class="summary-status" v-if="!isWeightValid"
              >（不符合要求）</span
            >
          </div>
        </div>

        <el-empty v-else description="暂未配置该角色的评估维度">
          <el-button type="primary" @click="openCreateDialog" :disabled="isAddDimensionDisabled"
            >添加评估维度</el-button
          >
        </el-empty>
      </div>
    </div>

    <el-empty
      v-else
      description="当前暂未创建角色，请先创建角色后再配置评估维度"
    >
      <el-button type="primary" @click="goToRoles">去创建角色</el-button>
    </el-empty>

    <el-dialog v-model="dialogVisible" :title="dialogTitle" width="500px">
      <el-form
        :model="dimensionForm"
        :rules="dimensionRules"
        ref="dimensionFormRef"
        label-width="100px"
      >
        <el-form-item label="维度名称" prop="name">
          <el-input v-model="dimensionForm.name" placeholder="请输入维度名称" />
        </el-form-item>
        <el-form-item label="描述" prop="description">
          <el-input
            v-model="dimensionForm.description"
            type="textarea"
            placeholder="请输入描述"
            :rows="3"
          />
        </el-form-item>
        <el-form-item label="权重" prop="weight">
          <el-input-number
            v-model="dimensionForm.weight"
            :min="0"
            :max="100"
            :precision="0"
            style="width: 100%"
          />
          <span class="weight-tip">权重范围：0-{{ remainingWeight }}%</span>
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="dialogVisible = false">取消</el-button>
          <el-button type="primary" @click="saveDimension">保存</el-button>
        </span>
      </template>
    </el-dialog>

    <CustomConfirmDialog
      v-model:visible="confirmDialogVisible"
      message="确定要删除这个维度吗？"
      @confirm="confirmDeleteDimension"
    />

    <!-- 选择维度弹窗 -->
    <el-dialog v-model="selectDimensionsDialogVisible" title="选择要复制的维度" width="500px">
      <div class="select-dimensions-content">
        <el-checkbox-group v-model="selectedDimensionsToCopy">
          <div v-for="dimension in currentDimensions" :key="dimension.id" class="dimension-checkbox-item">
            <el-checkbox :label="dimension.id">
              {{ dimension.name }} (权重: {{ dimension.weight }}%)
            </el-checkbox>
          </div>
        </el-checkbox-group>
        <el-empty v-if="currentDimensions.length === 0" description="当前角色暂无评估维度" />
      </div>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="selectDimensionsDialogVisible = false">取消</el-button>
          <el-button type="primary" @click="confirmSelectDimensions" :disabled="selectedDimensionsToCopy.length === 0">确认</el-button>
        </span>
      </template>
    </el-dialog>

    <!-- 选择目标角色弹窗 -->
    <el-dialog v-model="selectTargetRoleDialogVisible" title="选择目标角色" width="500px">
      <div class="select-role-content">
        <el-radio-group v-model="selectedTargetRoleId">
          <div v-for="role in manageableRoles" :key="role.id" class="role-radio-item">
            <el-radio :label="role.id" :disabled="role.id === selectedRole?.id">
              {{ role.name }}
              <span v-if="role.id === selectedRole?.id" class="current-role-tag">(当前角色)</span>
            </el-radio>
          </div>
        </el-radio-group>
      </div>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="selectTargetRoleDialogVisible = false">取消</el-button>
          <el-button type="primary" @click="confirmCopyDimensions" :disabled="!selectedTargetRoleId">确认</el-button>
        </span>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import { ElMessage, ElMessageBox } from "element-plus";
import { Plus, Edit, Delete, CopyDocument } from "@element-plus/icons-vue";
import CustomConfirmDialog from "../components/CustomConfirmDialog.vue";
import axios from "axios";

export default {
  name: "Dimensions",
  components: {
    Plus,
    Edit,
    Delete,
    CopyDocument,
    CustomConfirmDialog,
  },
  setup() {
    const router = useRouter();
    const manageableRoles = ref([]);
    const selectedRole = ref(null);
    const dimensions = ref([]);
    const dialogVisible = ref(false);
    const dialogTitle = ref("创建评估维度");
    const dimensionFormRef = ref(null);
    const confirmDialogVisible = ref(false);
    const dimensionToDelete = ref(null);
    
    // 复制维度相关状态
    const selectDimensionsDialogVisible = ref(false);
    const selectTargetRoleDialogVisible = ref(false);
    const selectedDimensionsToCopy = ref([]);
    const selectedTargetRoleId = ref(null);
    
    const dimensionForm = ref({
      id: null,
      name: "",
      description: "",
      weight: 0,
      target_role: "",
      target_role_id: null,
    });

    const dimensionRules = {
      name: [{ required: true, message: "请输入维度名称", trigger: "blur" }],
      weight: [{ required: true, message: "请输入权重", trigger: "blur" }],
    };

    const currentDimensions = computed(() => {
      if (!selectedRole.value) return [];
      return dimensions.value.filter(
        (d) =>
          String(d.target_role_id) === String(selectedRole.value.id) ||
          d.target_role === selectedRole.value.name
      );
    });

    const totalWeight = computed(() => {
      return currentDimensions.value.reduce(
        (sum, d) => sum + (Number(d.weight) || 0),
        0
      );
    });

    const isWeightValid = computed(() => {
      return totalWeight.value === 100;
    });

    const isAddDimensionDisabled = computed(() => {
      return totalWeight.value >= 100;
    });

    const isCopyDimensionDisabled = computed(() => {
      return currentDimensions.value.length === 0;
    });

    const remainingWeight = computed(() => {
      return Math.max(0, 100 - totalWeight.value);
    });

    const weightSummaryClass = computed(() => {
      return isWeightValid.value ? "weight-valid" : "weight-invalid";
    });

    const loadManageableRoles = async () => {
      try {
        const response = await axios.get("/api/dimensions/manageable-roles", {
          headers: {
            Authorization: `Bearer ${localStorage.getItem("token")}`,
          },
        });
        manageableRoles.value = response.data;
        if (manageableRoles.value.length > 0) {
          selectRole(manageableRoles.value[0]);
        }
      } catch (error) {
        console.error("Failed to load manageable roles:", error);
      }
    };

    const loadDimensions = async () => {
      try {
        const response = await axios.get("/api/dimensions", {
          headers: {
            Authorization: `Bearer ${localStorage.getItem("token")}`,
          },
        });
        dimensions.value = response.data;
      } catch (error) {
        console.error("Failed to load dimensions:", error);
      }
    };

    const selectRole = (role) => {
      selectedRole.value = role;
    };

    const goToRoles = () => {
      router.push("/roles");
    };

    const openCreateDialog = () => {
      if (!selectedRole.value) {
        ElMessage.warning("请先选择角色");
        return;
      }
      dimensionForm.value = {
        id: null,
        name: "",
        description: "",
        weight: 0,
        target_role: selectedRole.value.name,
        target_role_id: selectedRole.value.id,
      };
      dialogTitle.value = "创建评估维度";
      dialogVisible.value = true;
    };

    const editDimension = (dimension) => {
      dimensionForm.value = { ...dimension };
      dialogTitle.value = "编辑评估维度";
      dialogVisible.value = true;
    };

    const saveDimension = async () => {
      if (!dimensionFormRef.value) return;

      await dimensionFormRef.value.validate(async (valid) => {
        if (valid) {
          try {
            if (dimensionForm.value.id) {
              await axios.put(
                `/api/dimensions/${dimensionForm.value.id}`,
                dimensionForm.value,
                {
                  headers: {
                    Authorization: `Bearer ${localStorage.getItem("token")}`,
                  },
                }
              );
            } else {
              await axios.post("/api/dimensions", dimensionForm.value, {
                headers: {
                  Authorization: `Bearer ${localStorage.getItem("token")}`,
                },
              });
            }
            dialogVisible.value = false;
            loadDimensions();
            ElMessage.success("保存成功");
          } catch (error) {
            console.error("Failed to save dimension:", error);
            const errorMessage = error.response?.data?.error || "保存失败，请重试";
            ElMessage.error(errorMessage);
          }
        }
      });
    };

    const deleteDimension = async (id) => {
      dimensionToDelete.value = id;
      confirmDialogVisible.value = true;
    };

    const confirmDeleteDimension = async () => {
      if (!dimensionToDelete.value) return;
      
      try {
        await axios.delete(`/api/dimensions/${dimensionToDelete.value}`, {
          headers: {
            Authorization: `Bearer ${localStorage.getItem("token")}`,
          },
        });
        loadDimensions();
        ElMessage.success("删除成功");
      } catch (error) {
        console.error("Failed to delete dimension:", error);
        const errorMessage = error.response?.data?.error || "删除失败，请重试";
        ElMessage.error(errorMessage);
      } finally {
        dimensionToDelete.value = null;
      }
    };

    // 复制维度相关方法
    const openCopyDimensionDialog = () => {
      if (!selectedRole.value) {
        ElMessage.warning("请先选择角色");
        return;
      }
      selectedDimensionsToCopy.value = [];
      selectDimensionsDialogVisible.value = true;
    };

    const confirmSelectDimensions = () => {
      if (selectedDimensionsToCopy.value.length === 0) {
        ElMessage.warning("请至少选择一个维度");
        return;
      }
      selectDimensionsDialogVisible.value = false;
      selectedTargetRoleId.value = null;
      selectTargetRoleDialogVisible.value = true;
    };

    const confirmCopyDimensions = async () => {
      if (!selectedTargetRoleId.value) {
        ElMessage.warning("请选择目标角色");
        return;
      }

      // 检查目标角色是否已有维度
      const targetRoleDimensions = dimensions.value.filter(
        (d) =>
          String(d.target_role_id) === String(selectedTargetRoleId.value) ||
          d.target_role === manageableRoles.value.find(r => r.id === selectedTargetRoleId.value)?.name
      );

      if (targetRoleDimensions.length > 0) {
        // 目标角色已有维度，弹出确认框
        try {
          await ElMessageBox.confirm(
            "目标角色已存在评估维度，复制会覆盖原有配置，是否确认继续？",
            "确认覆盖",
            {
              confirmButtonText: "确认",
              cancelButtonText: "取消",
              type: "warning",
            }
          );
          // 用户确认，执行复制
          await executeCopyDimensions();
        } catch {
          // 用户取消
          return;
        }
      } else {
        // 目标角色无维度，直接复制
        await executeCopyDimensions();
      }
    };

    const executeCopyDimensions = async () => {
      try {
        const response = await axios.post(
          "/api/dimensions/copy",
          {
            dimension_ids: selectedDimensionsToCopy.value,
            target_role_id: selectedTargetRoleId.value,
          },
          {
            headers: {
              Authorization: `Bearer ${localStorage.getItem("token")}`,
            },
          }
        );
        
        selectTargetRoleDialogVisible.value = false;
        loadDimensions();
        ElMessage.success("复制成功");
      } catch (error) {
        console.error("Failed to copy dimensions:", error);
        const errorMessage = error.response?.data?.error || "复制失败，请重试";
        ElMessage.error(errorMessage);
      }
    };

    onMounted(() => {
      loadManageableRoles();
      loadDimensions();
    });

    return {
      manageableRoles,
      selectedRole,
      dimensions,
      currentDimensions,
      totalWeight,
      isWeightValid,
      isAddDimensionDisabled,
      isCopyDimensionDisabled,
      remainingWeight,
      weightSummaryClass,
      dialogVisible,
      dialogTitle,
      dimensionForm,
      dimensionRules,
      dimensionFormRef,
      confirmDialogVisible,
      selectDimensionsDialogVisible,
      selectTargetRoleDialogVisible,
      selectedDimensionsToCopy,
      selectedTargetRoleId,
      selectRole,
      goToRoles,
      openCreateDialog,
      editDimension,
      saveDimension,
      deleteDimension,
      confirmDeleteDimension,
      openCopyDimensionDialog,
      confirmSelectDimensions,
      confirmCopyDimensions,
    };
  },
};
</script>

<style scoped>
.dimensions-container {
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

.dimensions-layout {
  display: flex;
  gap: var(--spacing-lg);
  min-height: calc(100vh - 200px);
}

.roles-sidebar {
  width: 280px;
  flex-shrink: 0;
  background: var(--bg-color);
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-color);
  overflow: hidden;
}

.sidebar-header {
  padding: var(--spacing-md);
  border-bottom: 1px solid var(--border-color);
  background: var(--bg-color);
}

.sidebar-title {
  font-weight: var(--font-weight-medium);
  color: var(--text-primary);
}

.roles-list {
  max-height: calc(100vh - 300px);
  overflow-y: auto;
}

.role-item {
  padding: var(--spacing-md);
  border-bottom: 1px solid var(--border-color);
  cursor: pointer;
  transition: all var(--transition-fast);
}

.role-item:hover {
  background: rgba(79, 70, 229, 0.04);
}

.role-item.active {
  background: rgba(79, 70, 229, 0.08);
  border-left: 3px solid var(--el-color-primary);
}

.role-name {
  display: block;
  font-weight: var(--font-weight-medium);
  color: var(--text-primary);
  margin-bottom: var(--spacing-xs);
}

.role-desc {
  display: block;
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
}

.dimensions-content {
  flex: 1;
  background: var(--bg-color);
  border-radius: var(--radius-lg);
  border: 1px solid var(--border-color);
  padding: var(--spacing-lg);
}

.content-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: var(--spacing-lg);
  padding-bottom: var(--spacing-md);
  border-bottom: 1px solid var(--border-color);
}

.header-buttons {
  display: flex;
  gap: var(--spacing-sm);
}

.content-title {
  font-size: var(--font-size-lg);
  font-weight: var(--font-weight-medium);
  color: var(--text-primary);
}

.content-header .el-button {
  border-radius: var(--radius-md);
  transition: all var(--transition-fast);
  min-height: 32px;
  padding: 8px 15px;
  font-weight: var(--font-weight-medium);
}

.content-header .el-button:hover {
  transform: translateY(-1px);
}

.content-header .el-button--primary {
  background-color: var(--el-color-primary);
  border-color: var(--el-color-primary);
  color: #ffffff;
}

.content-header .el-button--primary:hover {
  background-color: var(--el-color-primary-light-3);
  border-color: var(--el-color-primary-light-3);
}

.content-header .el-button.is-disabled,
.content-header .el-button.is-disabled:hover,
.content-header .el-button.is-disabled:focus {
  cursor: not-allowed;
  opacity: 0.6;
}

.content-header .el-button--primary.is-disabled,
.content-header .el-button--primary.is-disabled:hover,
.content-header .el-button--primary.is-disabled:focus {
  background-color: #a0cfff !important;
  border-color: #a0cfff !important;
  color: #ffffff !important;
}

.dimensions-list {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.dimension-item {
  display: flex;
  align-items: center;
  padding: var(--spacing-md);
  background: var(--bg-color);
  border: 1px solid var(--border-color);
  border-radius: var(--radius-md);
  transition: all var(--transition-fast);
}

.dimension-item:hover {
  border-color: var(--el-color-primary-light-5);
  box-shadow: var(--shadow-sm);
}

.dimension-info {
  flex: 1;
}

.dimension-name {
  font-weight: var(--font-weight-medium);
  color: var(--text-primary);
  margin-bottom: var(--spacing-xs);
}

.dimension-desc {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
}

.dimension-weight {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 0 var(--spacing-lg);
  border-left: 1px solid var(--border-color);
  border-right: 1px solid var(--border-color);
  margin: 0 var(--spacing-md);
}

.weight-label {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
  margin-bottom: var(--spacing-xs);
}

.weight-value {
  font-size: var(--font-size-lg);
  font-weight: var(--font-weight-bold);
  color: var(--el-color-primary);
}

.dimension-actions {
  display: flex;
  gap: var(--spacing-xs);
}

.dimension-actions .el-button {
  border-radius: var(--radius-md);
  transition: all var(--transition-fast);
  min-height: 32px;
  padding: 8px 15px;
  font-weight: var(--font-weight-medium);
}

.dimension-actions .el-button:hover {
  transform: translateY(-1px);
}

.dimension-actions .el-button--primary {
  background-color: var(--el-color-primary);
  border-color: var(--el-color-primary);
  color: #ffffff;
}

.dimension-actions .el-button--primary:hover {
  background-color: var(--el-color-primary-light-3);
  border-color: var(--el-color-primary-light-3);
}

.dimension-actions .el-button--danger {
  background-color: var(--el-color-danger);
  border-color: var(--el-color-danger);
  color: #ffffff;
}

.dimension-actions .el-button--danger:hover {
  background-color: var(--el-color-danger-light-3);
  border-color: var(--el-color-danger-light-3);
}

.weight-summary {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  padding: var(--spacing-md);
  margin-top: var(--spacing-md);
  border-radius: var(--radius-md);
  font-size: var(--font-size-base);
}

.weight-valid {
  background: rgba(103, 194, 58, 0.1);
  color: var(--el-color-success);
}

.weight-invalid {
  background: rgba(245, 108, 108, 0.1);
  color: var(--el-color-danger);
}

.summary-label {
  margin-right: var(--spacing-xs);
}

.summary-value {
  font-weight: var(--font-weight-bold);
}

.summary-status {
  margin-left: var(--spacing-xs);
}

.weight-tip {
  font-size: var(--font-size-sm);
  color: var(--text-secondary);
  margin-left: var(--spacing-sm);
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: var(--spacing-sm);
}

.dialog-footer .el-button.is-disabled,
.dialog-footer .el-button.is-disabled:hover,
.dialog-footer .el-button.is-disabled:focus {
  cursor: not-allowed;
  opacity: 0.6;
}

.dialog-footer .el-button--primary.is-disabled,
.dialog-footer .el-button--primary.is-disabled:hover,
.dialog-footer .el-button--primary.is-disabled:focus {
  background-color: #a0cfff !important;
  border-color: #a0cfff !important;
  color: #ffffff !important;
}

.dialog-footer .el-button.is-disabled,
.dialog-footer .el-button.is-disabled:hover,
.dialog-footer .el-button.is-disabled:focus {
  cursor: not-allowed;
  opacity: 0.6;
}

.dialog-footer .el-button--primary.is-disabled,
.dialog-footer .el-button--primary.is-disabled:hover,
.dialog-footer .el-button--primary.is-disabled:focus {
  background-color: #a0cfff !important;
  border-color: #a0cfff !important;
  color: #ffffff !important;
}

@media (max-width: 768px) {
  .dimensions-layout {
    flex-direction: column;
  }

  .roles-sidebar {
    width: 100%;
  }

  .dimension-item {
    flex-direction: column;
    align-items: flex-start;
    gap: var(--spacing-md);
  }

  .dimension-weight {
    flex-direction: row;
    border: none;
    padding: 0;
    margin: 0;
  }

  .dimension-actions {
    width: 100%;
    justify-content: flex-end;
  }
}

.select-dimensions-content,
.select-role-content {
  max-height: 400px;
  overflow-y: auto;
}

.dimension-checkbox-item,
.role-radio-item {
  padding: var(--spacing-sm) 0;
  border-bottom: 1px solid var(--border-color);
}

.dimension-checkbox-item:last-child,
.role-radio-item:last-child {
  border-bottom: none;
}

.current-role-tag {
  color: var(--text-secondary);
  font-size: var(--font-size-sm);
  margin-left: var(--spacing-xs);
}
</style>
