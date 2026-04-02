<template>
    <div class="task-detail-page stack">
        <!-- ── Header ──────────────────────────────────────────────────── -->
        <div class="td-header">
            <div class="td-header-left">
                <h1 class="td-title">{{ task.label }}</h1>
                <p class="td-subtitle">
                    <span class="td-task-name">{{ task.name }}</span>
                    <span v-if="task.description" class="td-task-desc">— {{ task.description }}</span>
                </p>
            </div>
            <div class="td-header-actions">
                <a class="button button-secondary" :href="taskIndexUrl">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                    Back
                </a>
                <form v-if="task.allow_manual_run" method="POST" :action="taskRunUrl">
                    <input type="hidden" name="_token" :value="csrfToken">
                    <button class="button" type="submit" :disabled="!task.can_start" :title="startButtonTitle(task)">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        Run task
                    </button>
                </form>
            </div>
        </div>

        <!-- ── Stat Cards Row ──────────────────────────────────────────── -->
        <div class="td-stat-cards">
            <div class="td-stat-card td-stat-card--group">
                <div class="td-stat-body">
                    <div class="td-stat-label">GROUP</div>
                    <div class="td-stat-value">{{ task.group ?? '—' }}</div>
                </div>
                <div class="td-stat-icon td-stat-icon--group">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
            </div>

            <div class="td-stat-card">
                <div class="td-stat-body">
                    <div class="td-stat-label">QUEUE</div>
                    <div class="td-stat-value">{{ task.queue ?? 'default' }}</div>
                </div>
                <div class="td-stat-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="2" width="20" height="8" rx="2"/><rect x="2" y="14" width="20" height="8" rx="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/>
                    </svg>
                </div>
            </div>

            <div class="td-stat-card">
                <div class="td-stat-body">
                    <div class="td-stat-label">TIMEOUT</div>
                    <div class="td-stat-value">{{ task.timeout_minutes ? `${task.timeout_minutes} min` : '—' }}</div>
                </div>
                <div class="td-stat-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                </div>
            </div>

            <div :class="['td-stat-card', task.last_status === 'failed' ? 'td-stat-card--failed' : task.last_status === 'succeeded' ? 'td-stat-card--success' : '']">
                <div class="td-stat-body">
                    <div class="td-stat-label">LAST STATUS</div>
                    <div class="td-stat-value">
                        <span v-if="task.last_status" :class="['status-pill', `status-pill--${task.last_status}`]">
                            <!-- Succeeded Icon -->
                            <svg v-if="task.last_status === 'succeeded'" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                            <!-- Failed Icon -->
                            <svg v-else-if="task.last_status === 'failed'" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                            </svg>
                            <!-- Running Icon -->
                            <svg v-else-if="task.last_status === 'running'" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/><polyline points="10 8 16 12 10 16"/>
                            </svg>
                            <!-- Queued Icon -->
                            <svg v-else-if="task.last_status === 'queued'" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                            </svg>
                            <!-- Default Icon -->
                            <svg v-else width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                            {{ capitalize(task.last_status) }}
                        </span>
                        <span v-else>—</span>
                    </div>
                </div>
                <div :class="['td-stat-icon', task.last_status === 'failed' ? 'td-stat-icon--failed' : task.last_status === 'succeeded' ? 'td-stat-icon--success' : '']">
                    <svg v-if="task.last_status === 'succeeded'" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                    <svg v-else-if="task.last_status === 'failed'" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                    </svg>
                    <svg v-else width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                </div>
            </div>

            <div class="td-stat-card">
                <div class="td-stat-body">
                    <div class="td-stat-label">LAST RUN</div>
                    <div class="td-stat-value td-stat-value--small">{{ task.last_run_at ?? '—' }}</div>
                </div>
                <div class="td-stat-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- ── Info Sections Row ───────────────────────────────────────── -->
        <div class="td-info-row">
            <div class="td-info-card">
                <div class="td-info-header">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
                    </svg>
                    Dependencies
                </div>
                <div class="td-info-body">
                    <div v-if="(task.depends_on || []).length === 0" class="muted">No dependencies</div>
                    <div v-else class="td-dep-list">
                        <span v-for="dep in task.depends_on" :key="dep" class="task-dep-badge">{{ dep }}</span>
                    </div>
                </div>
            </div>

            <div class="td-info-card">
                <div class="td-info-header">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.08"/>
                    </svg>
                    Recent Runs
                </div>
                <div class="td-info-body">
                    <div v-if="recentRuns.length === 0" class="muted">No runs yet</div>
                    <div v-else class="td-run-dots">
                        <a
                            v-for="run in recentRuns"
                            :key="run.id"
                            :href="buildRunUrl(run.id)"
                            :title="buildRunTitle(run)"
                            :class="['run-dot', `run-dot--${run.status}`]"
                        ></a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Tabs Panel ──────────────────────────────────────────────── -->
        <div class="td-tabs-panel">
            <div class="td-tabs-bar">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    :class="['td-tab', { 'td-tab--active': activeTab === tab.key }]"
                    type="button"
                    @click="selectTab(tab.key)"
                >
                    {{ tab.label }}
                </button>
            </div>

            <div class="td-tab-content">
                <!-- Runs tab -->
                <div v-if="activeTab === 'runs'" class="task-tab-pane">
                    <div v-if="tabLoading.runs" class="muted">Loading runs...</div>
                    <div v-else-if="runsTab.data.length === 0" class="empty">No run history found.</div>
                    <div v-else class="table-wrap">
                        <table class="table-compact">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Status</th>
                                <th>Trigger</th>
                                <th>Started</th>
                                <th>Duration</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="run in runsTab.data" :key="run.id">
                                <td class="run-id"><a :href="buildRunUrl(run.id)">{{ String(run.id).slice(0, 6) }}</a></td>
                                <td><span :class="['status-pill', `status-pill--${run.status}`]">
                                    <!-- Succeeded Icon -->
                                    <svg v-if="run.status === 'succeeded'" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                                    </svg>
                                    <!-- Failed Icon -->
                                    <svg v-else-if="run.status === 'failed'" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                                    </svg>
                                    <!-- Running Icon -->
                                    <svg v-else-if="run.status === 'running'" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"/><polyline points="10 8 16 12 10 16"/>
                                    </svg>
                                    <!-- Queued Icon -->
                                    <svg v-else-if="run.status === 'queued'" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                                    </svg>
                                    <!-- Default Icon -->
                                    <svg v-else width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                                    </svg>
                                    {{ capitalize(run.status) }}
                                </span></td>
                                <td>
                                    <span :class="['badge', `badge-trigger-${run.trigger || 'manual'}`]">
                                        <!-- Pipeline icon -->
                                        <svg v-if="run.trigger === 'pipeline'" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                                        <!-- Scheduled icon -->
                                        <svg v-else-if="run.trigger === 'scheduled'" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                        <!-- Manual icon -->
                                        <svg v-else-if="run.trigger === 'manual'" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 9V5a3 3 0 0 0-6 0v4"/><rect x="2" y="9" width="20" height="13" rx="2"/></svg>
                                        <!-- Retry icon -->
                                        <svg v-else-if="run.trigger === 'retry'" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.08"/></svg>
                                        <!-- Default icon -->
                                        <svg v-else width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                        {{ capitalize(run.trigger) }}
                                    </span>
                                </td>
                                <td>{{ run.started_at ?? '—' }}</td>
                                <td>{{ formatDuration(run.duration) }}</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="task-tab-pagination">
                        <button class="button button-small button-secondary" :disabled="runsTab.meta.current_page <= 1" @click="loadRuns(runsTab.meta.current_page - 1)">Prev</button>
                        <span class="muted">Page {{ runsTab.meta.current_page }} / {{ runsTab.meta.last_page }}</span>
                        <button class="button button-small button-secondary" :disabled="runsTab.meta.current_page >= runsTab.meta.last_page" @click="loadRuns(runsTab.meta.current_page + 1)">Next</button>
                    </div>
                </div>

                <!-- Failures tab -->
                <div v-else-if="activeTab === 'failures'" class="task-tab-pane">
                    <div v-if="tabLoading.failures" class="muted">Loading failures...</div>
                    <div v-else-if="failuresTab.data.length === 0" class="empty">No failed runs found.</div>
                    <div v-else class="table-wrap">
                        <table class="table-compact">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Finished</th>
                                <th>Failure</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="run in failuresTab.data" :key="run.id">
                                <td class="run-id"><a :href="buildRunUrl(run.id)">{{ String(run.id).slice(0, 6) }}</a></td>
                                <td>{{ run.finished_at ?? '—' }}</td>
                                <td class="wrap-text">{{ run.failure_message ?? '—' }}</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="task-tab-pagination">
                        <button class="button button-small button-secondary" :disabled="failuresTab.meta.current_page <= 1" @click="loadFailures(failuresTab.meta.current_page - 1)">Prev</button>
                        <span class="muted">Page {{ failuresTab.meta.current_page }} / {{ failuresTab.meta.last_page }}</span>
                        <button class="button button-small button-secondary" :disabled="failuresTab.meta.current_page >= failuresTab.meta.last_page" @click="loadFailures(failuresTab.meta.current_page + 1)">Next</button>
                    </div>
                </div>

                <!-- Logs tab -->
                <div v-else-if="activeTab === 'logs'" class="task-tab-pane">
                    <div v-if="tabLoading.logs" class="muted">Loading logs...</div>
                    <template v-else>
                        <div v-if="logsTab.selected_run_id" class="td-log-run-label">
                            Latest run: <strong>{{ String(logsTab.selected_run_id).slice(0, 6) }}</strong>
                        </div>
                        <div v-if="logsTab.logs.length === 0" class="empty">No logs found for the latest run.</div>
                        <div v-else class="log-list">
                            <div v-for="log in logsTab.logs" :key="log.id" class="log-entry">
                                <div class="log-meta">[{{ log.level }}] {{ log.created_at ?? '' }}</div>
                                <div class="no-truncate">{{ log.message }}</div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Documentation tab -->
                <div v-else-if="activeTab === 'documentation'" class="task-tab-pane">
                    <div v-if="tabLoading.documentation" class="muted">Loading documentation...</div>
                    <template v-else>
                        <div class="td-doc-section">
                            <div class="td-doc-label">Description</div>
                            <div class="wrap-text">{{ docsTab.description ?? '—' }}</div>
                        </div>
                        <div class="td-doc-section">
                            <div class="td-doc-label">Documentation</div>
                            <div class="wrap-text">{{ docsTab.documentation ?? '—' }}</div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue'

const props = defineProps({
    runBaseUrl: { type: String, required: true },
    taskIndexUrl: { type: String, required: true },
    taskRunUrl: { type: String, required: true },
    csrfToken: { type: String, required: true },
    taskRunsUrl: { type: String, required: true },
    taskFailuresUrl: { type: String, required: true },
    taskLogsUrl: { type: String, required: true },
    taskDocumentationUrl: { type: String, required: true },
    initialTask: { type: Object, required: true },
    initialRecentRuns: { type: Array, required: true },
})

const tabs = [
    { key: 'runs', label: 'Runs' },
    { key: 'failures', label: 'Failures' },
    { key: 'logs', label: 'Logs' },
    { key: 'documentation', label: 'Documentation' },
]

const task = ref({ ...props.initialTask })
const recentRuns = ref([...props.initialRecentRuns])
const activeTab = ref('runs')

const tabLoaded = ref({
    runs: false,
    failures: false,
    logs: false,
    documentation: false,
})

const tabLoading = ref({
    runs: false,
    failures: false,
    logs: false,
    documentation: false,
})

const runsTab = ref({
    data: [],
    meta: { current_page: 1, last_page: 1 },
})

const failuresTab = ref({
    data: [],
    meta: { current_page: 1, last_page: 1 },
})

const logsTab = ref({
    selected_run_id: null,
    logs: [],
})

const docsTab = ref({ description: null, documentation: null })

function capitalize(value) {
    if (!value) return ''
    return String(value).charAt(0).toUpperCase() + String(value).slice(1)
}

function formatDuration(seconds) {
    if (seconds === null || seconds === undefined) return '—'
    
    const sec = parseInt(seconds)
    if (sec < 60) {
        return `${sec}s`
    } else if (sec < 3600) {
        const minutes = Math.floor(sec / 60)
        const remainingSeconds = sec % 60
        return remainingSeconds > 0 ? `${minutes}m ${remainingSeconds}s` : `${minutes}m`
    } else {
        const hours = Math.floor(sec / 3600)
        const minutes = Math.floor((sec % 3600) / 60)
        return minutes > 0 ? `${hours}h ${minutes}m` : `${hours}h`
    }
}

function buildRunUrl(runId) {
    return `${props.runBaseUrl}/${runId}`
}

function buildRunTitle(run) {
    const status = capitalize(run.status)
    const trigger = capitalize(run.trigger || 'manual')
    return `${status} • ${trigger} • ${run.started_at ?? '—'}`
}

function startButtonTitle(currentTask) {
    if (currentTask.can_start) {
        return 'Run task'
    }

    if (currentTask.is_running) {
        return 'Task is already running'
    }

    if (currentTask.is_queued) {
        return 'Task is already queued'
    }

    if (currentTask.is_blocked_by_dependencies) {
        const blocked = (currentTask.blocked_by_task_names || []).join(', ')
        return blocked ? `Blocked by dependencies: ${blocked}` : 'Blocked by dependencies'
    }

    if (!currentTask.allow_manual_run) {
        return 'Manual run is disabled for this task'
    }

    return 'Task cannot be started'
}

async function fetchJson(url) {
    const response = await fetch(url, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
        },
    })

    if (!response.ok) {
        return null
    }

    return response.json()
}

async function loadRuns(page = 1) {
    tabLoading.value.runs = true

    try {
        const data = await fetchJson(`${props.taskRunsUrl}?page=${page}`)

        if (!data) return

        runsTab.value = {
            data: data.data || [],
            meta: data.meta || { current_page: 1, last_page: 1 },
        }

        tabLoaded.value.runs = true
    } finally {
        tabLoading.value.runs = false
    }
}

async function loadFailures(page = 1) {
    tabLoading.value.failures = true

    try {
        const data = await fetchJson(`${props.taskFailuresUrl}?page=${page}`)

        if (!data) return

        failuresTab.value = {
            data: data.data || [],
            meta: data.meta || { current_page: 1, last_page: 1 },
        }

        tabLoaded.value.failures = true
    } finally {
        tabLoading.value.failures = false
    }
}

async function loadLogs() {
    tabLoading.value.logs = true

    try {
        const data = await fetchJson(props.taskLogsUrl)

        if (!data) return

        logsTab.value = {
            selected_run_id: data.selected_run_id ?? null,
            selected_run: data.selected_run ?? null,
            logs: data.logs || [],
        }

        tabLoaded.value.logs = true
    } finally {
        tabLoading.value.logs = false
    }
}

async function loadDocumentation() {
    tabLoading.value.documentation = true

    try {
        const data = await fetchJson(props.taskDocumentationUrl)

        if (!data) return

        docsTab.value = {
            description: data.description ?? null,
            documentation: data.documentation ?? null,
        }

        tabLoaded.value.documentation = true
    } finally {
        tabLoading.value.documentation = false
    }
}

async function selectTab(tab) {
    activeTab.value = tab

    if (tab === 'runs' && !tabLoaded.value.runs) {
        await loadRuns(1)
        return
    }

    if (tab === 'failures' && !tabLoaded.value.failures) {
        await loadFailures(1)
        return
    }

    if (tab === 'logs' && !tabLoaded.value.logs) {
        await loadLogs()
        return
    }

    if (tab === 'documentation' && !tabLoaded.value.documentation) {
        await loadDocumentation()
    }
}

onMounted(() => {
    if (!tabLoaded.value.runs) {
        void loadRuns(1)
    }
})
</script>

