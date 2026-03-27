<template>
  <div class="plans-container">
    <el-card>
      <template #header>
        <div class="card-header">
          <h2>评估计划管理</h2>
          <el-button type="primary" @click="dialogVisible = true">
            <el-icon><Plus /></el-icon>
            创建计划
          </el-button>
        </div>
      </template>
      <el-table :data="formattedPlans" style="width: 100%">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column prop="name" label="计划名称" />
        <el-table-column label="开始日期" width="200">
          <template #default="scope">{{
            scope.row.formattedStartDate
          }}</template>
        </el-table-column>
        <el-table-column label="结束日期" width="200">
          <template #default="scope">{{ scope.row.formattedEndDate }}</template>
        </el-table-column>
        <el-table-column label="状态">
          <template #default="scope">{{ scope.row.formattedStatus }}</template>
        </el-table-column>
        <el-table-column label="操作" width="300">
          <template #default="scope">
            <el-button size="small" @click="editPlan(scope.row)"
              >编辑</el-button
            >
            <el-button
              size="small"
              type="primary"
              @click="updatePlanStatus(scope.row.id, 'ongoing')"
              :disabled="scope.row.status !== 'pending'"
              >开启</el-button
            >
            <el-button
              size="small"
              type="warning"
              @click="updatePlanStatus(scope.row.id, 'completed')"
              :disabled="scope.row.status !== 'ongoing'"
              >结束</el-button
            >
            <el-button
              size="small"
              type="danger"
              @click="confirmDeletePlan(scope.row.id)"
              :disabled="scope.row.status === 'ongoing'"
              >删除</el-button
            >
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <!-- 创建/编辑计划对话框 -->
    <el-dialog v-model="dialogVisible" :title="dialogTitle">
      <el-form
        :model="planForm"
        :rules="planRules"
        ref="planFormRef"
        label-width="100px"
      >
        <el-form-item label="计划名称" prop="name">
          <el-input v-model="planForm.name" placeholder="请输入计划名称" />
        </el-form-item>
        <el-form-item label="开始日期" prop="start_date">
          <el-date-picker
            v-model="planForm.start_date"
            type="datetime"
            placeholder="选择开始日期"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="结束日期" prop="end_date">
          <el-date-picker
            v-model="planForm.end_date"
            type="datetime"
            placeholder="选择结束日期"
            style="width: 100%"
          />
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-select v-model="planForm.status" placeholder="选择状态">
            <el-option label="待开始" value="pending" />
            <el-option label="进行中" value="ongoing" />
            <el-option label="已结束" value="completed" />
          </el-select>
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="dialogVisible = false">取消</el-button>
          <el-button type="primary" @click="savePlan">保存</el-button>
        </span>
      </template>
    </el-dialog>

    <!-- 确认删除对话框 -->
    <CustomConfirmDialog
      v-model:visible="confirmDialogVisible"
      message="确定要删除这个计划吗？"
      @confirm="deletePlan"
    />
  </div>
</template>

<script>
import { ref, onMounted, computed } from "vue";
import { ElMessage } from "element-plus";
import { Plus } from "@element-plus/icons-vue";
import axios from "axios";
import CustomConfirmDialog from "../components/CustomConfirmDialog.vue";

export default {
  name: "Plans",
  components: {
    CustomConfirmDialog,
    Plus
  },
  setup() {
    const plans = ref([]);
    const dialogVisible = ref(false);
    const dialogTitle = ref("创建评估计划");
    const planFormRef = ref(null);
    const planForm = ref({
      id: null,
      name: "",
      start_date: "",
      end_date: "",
      status: "pending",
    });
    const confirmDialogVisible = ref(false);
    const planIdToDelete = ref(null);

    // 格式化日期时间为中文显示
    const formatDateTime = (dateString) => {
      if (!dateString) return "";
      const date = new Date(dateString);
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, "0");
      const day = String(date.getDate()).padStart(2, "0");
      const hours = String(date.getHours()).padStart(2, "0");
      const minutes = String(date.getMinutes()).padStart(2, "0");
      const seconds = String(date.getSeconds()).padStart(2, "0");
      return `${year}年${month}月${day}日 ${hours}:${minutes}:${seconds}`;
    };

    // 状态中文映射
    const statusMap = {
      pending: "待开始",
      ongoing: "进行中",
      completed: "已结束",
    };

    // 计算属性：格式化后的计划列表
    const formattedPlans = computed(() => {
      return plans.value.map((plan) => ({
        ...plan,
        formattedStartDate: formatDateTime(plan.start_date),
        formattedEndDate: formatDateTime(plan.end_date),
        formattedStatus: statusMap[plan.status] || plan.status,
      }));
    });

    const planRules = {
      name: [{ required: true, message: "请输入计划名称", trigger: "blur" }],
      start_date: [
        { required: true, message: "请选择开始日期", trigger: "change" },
      ],
      end_date: [
        { required: true, message: "请选择结束日期", trigger: "change" },
      ],
      status: [{ required: true, message: "请选择状态", trigger: "change" }],
    };

    const loadPlans = async () => {
      console.log("开始加载计划列表");
      try {
        const token = localStorage.getItem("token");
        console.log("使用token:", token);
        const response = await axios.get("/api/plans", {
          headers: {
            Authorization: `Bearer ${token}`,
          },
        });
        console.log("计划列表加载成功:", response.data);
        plans.value = response.data;
      } catch (error) {
        console.error("Failed to load plans:", error);
        console.error("Error response:", error.response);
      }
    };

    const savePlan = async () => {
      if (!planFormRef.value) return;

      await planFormRef.value.validate(async (valid) => {
        if (valid) {
          try {
            if (planForm.value.id) {
              // 编辑计划
              await axios.put(
                `/api/plans/${planForm.value.id}`,
                planForm.value,
                {
                  headers: {
                    Authorization: `Bearer ${localStorage.getItem("token")}`,
                  },
                }
              );
            } else {
              // 创建计划
              await axios.post(
                "/api/plans",
                planForm.value,
                {
                  headers: {
                    Authorization: `Bearer ${localStorage.getItem("token")}`,
                  },
                }
              );
            }
            dialogVisible.value = false;
            loadPlans();
            resetForm();
          } catch (error) {
            console.error("Failed to save plan:", error);
            ElMessage.error("保存失败，请重试");
          }
        }
      });
    };

    const editPlan = (plan) => {
      // 检查计划状态，如果是进行中则不允许编辑
      if (plan.status === 'ongoing') {
        ElMessage.warning('进行中的评估计划不允许编辑');
        return;
      }
      
      planForm.value = { ...plan };
      dialogTitle.value = "编辑评估计划";
      dialogVisible.value = true;
    };

    const deletePlan = async () => {
      try {
        await axios.delete(`/api/plans/${planIdToDelete.value}`, {
          headers: {
            Authorization: `Bearer ${localStorage.getItem("token")}`,
          },
        });
        loadPlans();
        confirmDialogVisible.value = false;
      } catch (error) {
        console.error("Failed to delete plan:", error);
        ElMessage.error("删除失败，请重试");
        confirmDialogVisible.value = false;
      }
    };

    const confirmDeletePlan = (id) => {
      planIdToDelete.value = id;
      confirmDialogVisible.value = true;
    };

    const updatePlanStatus = async (id, status) => {
      // 当要结束计划时，检查是否有关联的进行中任务
      if (status === 'completed') {
        try {
          const response = await axios.get('/api/tasks', {
            headers: {
              Authorization: `Bearer ${localStorage.getItem("token")}`,
            },
          });
          
          const planTasks = response.data.filter(task => task.plan_id === id);
          const ongoingTasks = planTasks.filter(task => task.status === 'ongoing');
          
          if (ongoingTasks.length > 0) {
            ElMessage.warning('评估计划关联的任务中有进行中的任务，不能结束评估计划');
            return;
          }
        } catch (error) {
          console.error('Failed to check tasks:', error);
          ElMessage.error('检查任务状态失败，请重试');
          return;
        }
      }
      
      try {
        await axios.put(
          `/api/plans/${id}`,
          { status },
          {
            headers: {
              Authorization: `Bearer ${localStorage.getItem("token")}`,
            },
          }
        );
        loadPlans();
      } catch (error) {
        console.error("Failed to update plan status:", error);
        const errorMessage = error.response?.data?.error || "状态更新失败，请重试";
        ElMessage.error(errorMessage);
      }
    };

    const resetForm = () => {
      planForm.value = {
        id: null,
        name: "",
        start_date: "",
        end_date: "",
        status: "pending",
      };
      dialogTitle.value = "创建评估计划";
    };

    onMounted(() => {
      loadPlans();
    });

    return {
      plans,
      formattedPlans,
      dialogVisible,
      dialogTitle,
      planForm,
      planRules,
      planFormRef,
      confirmDialogVisible,
      loadPlans,
      savePlan,
      editPlan,
      deletePlan,
      confirmDeletePlan,
      updatePlanStatus,
    };
  },
};
</script>

<style scoped>
.plans-container {
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

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

/* 禁用按钮样式 - 确保禁用状态自动显示灰色 */
:deep(.el-button.is-disabled),
:deep(.el-button.is-disabled:hover),
:deep(.el-button.is-disabled:focus) {
  cursor: not-allowed;
  opacity: 0.6;
}

:deep(.el-button--primary.is-disabled),
:deep(.el-button--primary.is-disabled:hover),
:deep(.el-button--primary.is-disabled:focus) {
  background-color: #a0cfff !important;
  border-color: #a0cfff !important;
  color: #ffffff !important;
}

:deep(.el-button--warning.is-disabled),
:deep(.el-button--warning.is-disabled:hover),
:deep(.el-button--warning.is-disabled:focus) {
  background-color: #f3d19e !important;
  border-color: #f3d19e !important;
  color: #ffffff !important;
}

:deep(.el-button--danger.is-disabled),
:deep(.el-button--danger.is-disabled:hover),
:deep(.el-button--danger.is-disabled:focus) {
  background-color: #fab6b6 !important;
  border-color: #fab6b6 !important;
  color: #ffffff !important;
}
</style>