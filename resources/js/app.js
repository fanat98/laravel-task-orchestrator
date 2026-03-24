import { createApp } from 'vue'
import '../css/app.css'
import TaskOrchestratorDashboardApp from './components/dashboard/TaskOrchestratorDashboardApp.vue'
import TaskOrchestratorRunDetailApp from './components/runs/TaskOrchestratorRunDetailApp.vue'

const dashboardElement = document.getElementById('task-orchestrator-dashboard-app')

if (dashboardElement) {
    createApp(TaskOrchestratorDashboardApp, {
        dashboardApiUrl: dashboardElement.dataset.dashboardApiUrl,
        runBaseUrl: dashboardElement.dataset.runBaseUrl,
        taskRunBaseUrl: dashboardElement.dataset.taskRunBaseUrl,
        csrfToken: dashboardElement.dataset.csrfToken,
        initialSummary: JSON.parse(dashboardElement.dataset.initialSummary || '{}'),
        initialHealth: JSON.parse(dashboardElement.dataset.initialHealth || '{}'),
        initialLatestRuns: JSON.parse(dashboardElement.dataset.initialLatestRuns || '[]'),
        initialLatestFailedRuns: JSON.parse(dashboardElement.dataset.initialLatestFailedRuns || '[]'),
        initialTaskGroups: JSON.parse(dashboardElement.dataset.initialTaskGroups || '[]'),
        pollInterval: Number(dashboardElement.dataset.pollInterval || 5000),
    }).mount(dashboardElement)
}

const runDetailElement = document.getElementById('task-orchestrator-run-detail-app')

if (runDetailElement) {
    createApp(TaskOrchestratorRunDetailApp, {
        runStatusUrl: runDetailElement.dataset.runStatusUrl,
        runLogsUrl: runDetailElement.dataset.runLogsUrl,
        runsIndexUrl: runDetailElement.dataset.runsIndexUrl,
        retryUrl: runDetailElement.dataset.retryUrl,
        csrfToken: runDetailElement.dataset.csrfToken,
        initialRun: JSON.parse(runDetailElement.dataset.initialRun || '{}'),
        initialLogs: JSON.parse(runDetailElement.dataset.initialLogs || '[]'),
        pollInterval: Number(runDetailElement.dataset.pollInterval || 3000),
    }).mount(runDetailElement)
}
