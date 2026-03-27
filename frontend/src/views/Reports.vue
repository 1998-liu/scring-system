<template>
  <div class="reports-container">
    <el-card>
      <template #header>
        <div class="card-header">
          <h2>评估报告管理</h2>
        </div>
      </template>
      <el-table :data="formattedReports" style="width: 100%">
        <el-table-column prop="id" label="ID" width="80" />
        <el-table-column label="被评估者" width="120">
          <template #default="scope">
            {{ scope.row.evaluatee?.name || '-' }}
          </template>
        </el-table-column>
        <el-table-column label="评估计划" width="150">
          <template #default="scope">
            {{ scope.row.plan?.name || '-' }}
          </template>
        </el-table-column>
        <el-table-column prop="total_score" label="总分" width="100" />
        <el-table-column label="生成时间">
          <template #default="scope">
            {{ scope.row.formattedCreatedAt }}
          </template>
        </el-table-column>
        <el-table-column label="操作" width="150">
          <template #default="scope">
            <el-button size="small" @click="viewReport(scope.row)">查看</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <!-- 查看报告对话框 -->
    <el-dialog v-model="reportVisible" title="评估报告" width="80%">
      <div v-if="currentReport" class="report-content">
        <h3>被评估者：{{ currentReport.evaluatee?.name }}</h3>
        <h4>评估计划：{{ currentReport.plan?.name }}</h4>
        <h4>总分：{{ currentReport.total_score }}</h4>
        <h4>生成时间：{{ formatDateTime(currentReport.created_at) }}</h4>
        <div class="report-details">
          <h5>各项维度得分：</h5>
          <el-table :data="reportDimensions" style="width: 100%">
            <el-table-column prop="name" label="维度" />
            <el-table-column prop="average" label="平均分" />
            <el-table-column prop="weight" label="权重" />
          </el-table>
          <div class="report-text">
            <h5>优势：</h5>
            <p>{{ currentReport.strengths }}</p>
            <h5>改进建议：</h5>
            <p>{{ currentReport.improvements }}</p>
          </div>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script>
import { ref, onMounted, computed } from 'vue'
import axios from 'axios'

export default {
  name: 'Reports',
  setup() {
    const reports = ref([])
    const reportVisible = ref(false)
    const currentReport = ref(null)

    // 格式化日期时间为中文显示
    const formatDateTime = (dateString) => {
      if (!dateString) return '-'
      const date = new Date(dateString)
      const year = date.getFullYear()
      const month = String(date.getMonth() + 1).padStart(2, '0')
      const day = String(date.getDate()).padStart(2, '0')
      const hours = String(date.getHours()).padStart(2, '0')
      const minutes = String(date.getMinutes()).padStart(2, '0')
      const seconds = String(date.getSeconds()).padStart(2, '0')
      return `${year}年${month}月${day}日 ${hours}:${minutes}:${seconds}`
    }

    // 计算属性：格式化后的报告列表
    const formattedReports = computed(() => {
      return reports.value.map((report) => ({
        ...report,
        formattedCreatedAt: formatDateTime(report.created_at)
      }))
    })

    const reportDimensions = computed(() => {
      if (!currentReport.value || !currentReport.value.scores) return []
      try {
        const scores = JSON.parse(currentReport.value.scores)
        return Object.values(scores)
      } catch (error) {
        return []
      }
    })

    const loadReports = async () => {
      try {
        const response = await axios.get('/api/reports', {
          headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`
          }
        })
        reports.value = response.data
      } catch (error) {
        console.error('Failed to load reports:', error)
      }
    }

    const viewReport = (report) => {
      currentReport.value = report
      reportVisible.value = true
    }

    onMounted(() => {
      loadReports()
    })

    return {
      reports,
      formattedReports,
      reportVisible,
      currentReport,
      reportDimensions,
      formatDateTime,
      loadReports,
      viewReport
    }
  }
}
</script>

<style scoped>
.reports-container {
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

.report-content {
  padding: 20px;
}

.report-content h3 {
  margin-top: 0;
  color: #409EFF;
}

.report-details {
  margin-top: 20px;
}

.report-text {
  margin-top: 20px;
}

.report-text h5 {
  margin-bottom: 5px;
  color: #606266;
}

.report-text p {
  margin-top: 0;
  line-height: 1.5;
}
</style>