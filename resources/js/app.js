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


(function () {
    const KEY = 'task-orchestrator-theme';

    const root = document.documentElement;
    const toggle = document.getElementById('theme-toggle');

    // Load saved theme
    const saved = localStorage.getItem(KEY);

    if (!saved && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        root.setAttribute('data-theme', 'dark');
    }

    if (saved) {
        root.setAttribute('data-theme', saved);
    }

    if (!toggle) return;

    toggle.addEventListener('click', () => {
        const current = root.getAttribute('data-theme');
        const next = current === 'dark' ? 'light' : 'dark';

        root.setAttribute('data-theme', next);
        localStorage.setItem(KEY, next);

        toggle.textContent = next === 'dark' ? '☀️' : '🌙';
    });

    // Set initial icon
    const current = root.getAttribute('data-theme');
    toggle.textContent = current === 'dark' ? '☀️' : '🌙';
})();


