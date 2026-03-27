<template>
  <el-dialog
    v-model="dialogVisible"
    :title="title"
    width="30%"
    :close-on-click-modal="false"
    :close-on-press-escape="false"
  >
    <div class="confirm-content">
      <el-icon class="warning-icon"><Warning /></el-icon>
      <p>{{ message }}</p>
    </div>
    <template #footer>
      <span class="dialog-footer">
        <el-button @click="handleCancel">取消</el-button>
        <el-button type="primary" @click="handleConfirm">确定</el-button>
      </span>
    </template>
  </el-dialog>
</template>

<script>
import { computed } from 'vue'
import { Warning } from '@element-plus/icons-vue'

export default {
  name: 'CustomConfirmDialog',
  components: {
    Warning
  },
  props: {
    visible: {
      type: Boolean,
      default: false
    },
    title: {
      type: String,
      default: '确认操作'
    },
    message: {
      type: String,
      required: true
    }
  },
  emits: ['update:visible', 'confirm', 'cancel'],
  setup(props, { emit }) {
    const dialogVisible = computed({
      get: () => props.visible,
      set: (value) => emit('update:visible', value)
    })

    const handleConfirm = () => {
      emit('confirm')
      emit('update:visible', false)
    }

    const handleCancel = () => {
      emit('cancel')
      emit('update:visible', false)
    }

    return {
      dialogVisible,
      handleConfirm,
      handleCancel
    }
  }
}
</script>

<style scoped>
.confirm-content {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  margin-bottom: 20px;
}

.warning-icon {
  font-size: 24px;
  color: #E6A23C;
  margin-top: 2px;
  flex-shrink: 0;
}

.confirm-content p {
  margin: 0;
  font-size: 14px;
  line-height: 1.5;
  color: var(--text-primary);
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

@media (max-width: 768px) {
  :deep(.el-dialog) {
    width: 80% !important;
  }
}
</style>