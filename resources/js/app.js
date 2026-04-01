import { createApp } from 'vue'
import '../css/app.css'
import TaskOrchestratorDashboardApp from './components/dashboard/TaskOrchestratorDashboardApp.vue'
import TaskOrchestratorRunDetailApp from './components/runs/TaskOrchestratorRunDetailApp.vue'
import TaskDetailPage from './components/tasks/TaskDetailPage.vue'

const dashboardElement = document.getElementById('task-orchestrator-dashboard-app')

if (dashboardElement) {
    createApp(TaskOrchestratorDashboardApp, {
        dashboardApiUrl: dashboardElement.dataset.dashboardApiUrl,
        runBaseUrl: dashboardElement.dataset.runBaseUrl,
        runsIndexUrl: dashboardElement.dataset.runsIndexUrl,
        failedRunsUrl: dashboardElement.dataset.failedRunsUrl,
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

const taskDetailElement = document.getElementById('task-orchestrator-task-detail-app')

if (taskDetailElement) {
    createApp(TaskDetailPage, {
        runBaseUrl: taskDetailElement.dataset.runBaseUrl,
        taskIndexUrl: taskDetailElement.dataset.taskIndexUrl,
        taskRunUrl: taskDetailElement.dataset.taskRunUrl,
        csrfToken: taskDetailElement.dataset.csrfToken,
        taskRunsUrl: taskDetailElement.dataset.taskRunsUrl,
        taskFailuresUrl: taskDetailElement.dataset.taskFailuresUrl,
        taskLogsUrl: taskDetailElement.dataset.taskLogsUrl,
        taskDocumentationUrl: taskDetailElement.dataset.taskDocumentationUrl,
        initialTask: JSON.parse(taskDetailElement.dataset.initialTask || '{}'),
        initialRecentRuns: JSON.parse(taskDetailElement.dataset.initialRecentRuns || '[]'),
    }).mount(taskDetailElement)
}


(function () {
    const KEY = 'task-orchestrator-theme';

    const root = document.documentElement;
    const toggle = document.getElementById('theme-toggle');

    function applyTheme(theme) {
        root.setAttribute('data-theme', theme);

        if (toggle) {
            const moon = toggle.querySelector('.icon-moon');
            const sun = toggle.querySelector('.icon-sun');

            if (moon) moon.style.display = theme === 'dark' ? 'none' : '';
            if (sun) sun.style.display = theme === 'dark' ? '' : 'none';
        }
    }

    // Determine initial theme
    const saved = localStorage.getItem(KEY);
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const initial = saved ?? (prefersDark ? 'dark' : 'light');

    applyTheme(initial);

    if (!toggle) return;

    toggle.addEventListener('click', () => {
        const current = root.getAttribute('data-theme');
        const next = current === 'dark' ? 'light' : 'dark';

        applyTheme(next);
        localStorage.setItem(KEY, next);
    });
})();


