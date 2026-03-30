<template>
    <div class="task-detail-page stack">
        <div class="page-header task-detail-header">
            <div>
                <h1 class="page-title">{{ task.label }}</h1>
                <p class="page-subtitle">Task: {{ task.name }}</p>
            </div>

            <div class="nav-actions">
                <a class="button button-secondary" :href="taskIndexUrl">Back to tasks</a>

                <form v-if="task.allow_manual_run" method="POST" :action="taskRunUrl">
                    <input type="hidden" name="_token" :value="csrfToken">
                    <button
                        class="button"
                        type="submit"
                        :disabled="!task.can_start"
                        :title="startButtonTitle(task)"
                    >
                        ▶ Run task
                    </button>
                </form>
            </div>
        </div>

        <div class="panel task-overview-panel">
            <div class="panel-header">Task Overview</div>

            <div class="task-overview-content">
                <div class="task-overview-cards">
                    <div class="task-overview-item">
                        <div class="task-overview-label">Group</div>
                        <div class="task-overview-value">{{ task.group ?? '—' }}</div>
                    </div>
                    <div class="task-overview-item">
                        <div class="task-overview-label">Queue</div>
                        <div class="task-overview-value">{{ task.queue ?? '—' }}</div>
                    </div>
                    <div class="task-overview-item">
                        <div class="task-overview-label">Timeout</div>
                        <div class="task-overview-value">{{ task.timeout_minutes ? `${task.timeout_minutes} min` : '—' }}</div>
                    </div>
                    <div class="task-overview-item">
                        <div class="task-overview-label">Last Status</div>
                        <div class="task-overview-value">
                            <span v-if="task.last_status" :class="['status-badge', `status-${task.last_status}`]">
                                {{ capitalize(task.last_status) }}
                            </span>
                            <span v-else>—</span>
                        </div>
                    </div>
                    <div class="task-overview-item">
                        <div class="task-overview-label">Last Run</div>
                        <div class="task-overview-value">{{ task.last_run_at ?? '—' }}</div>
                    </div>
                </div>

                <div class="task-overview-subsections">
                    <div class="task-overview-section">
                        <div class="task-overview-label">Dependencies</div>
                        <div v-if="(task.depends_on || []).length === 0" class="muted">No dependencies</div>
                        <div v-else class="task-dependencies">
                            <span
                                v-for="dependency in task.depends_on"
                                :key="dependency"
                                class="badge badge-dependency"
                            >
                                {{ dependency }}
                            </span>
                        </div>
                    </div>

                    <div class="task-overview-section">
                        <div class="task-overview-label">Recent Runs</div>
                        <div v-if="recentRuns.length === 0" class="empty">No runs yet.</div>
                        <div v-else class="task-recent-runs">
                            <a
                                v-for="run in recentRuns"
                                :key="run.id"
                                :href="buildRunUrl(run.id)"
                                :title="buildRunTitle(run)"
                                :class="['task-run-dot', `task-run-dot-${run.status}`]"
                            ></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="panel">
            <div class="task-tabs-shell">
                <div class="task-tabs">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        :class="['task-tab-button', { 'is-active': activeTab === tab.key }]"
                        type="button"
                        @click="selectTab(tab.key)"
                    >
                        {{ tab.label }}
                    </button>
                </div>
            </div>

            <div class="task-tab-content">
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
                                <td><a :href="buildRunUrl(run.id)">{{ run.id }}</a></td>
                                <td>
                                    <span :class="['status-badge', `status-${run.status}`]">{{ capitalize(run.status) }}</span>
                                </td>
                                <td>{{ capitalize(run.trigger) }}</td>
                                <td>{{ run.started_at ?? '—' }}</td>
                                <td>{{ run.duration === null ? '—' : `${run.duration}s` }}</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="task-tab-pagination">
                        <button class="button button-small" :disabled="runsTab.meta.current_page <= 1" @click="loadRuns(runsTab.meta.current_page - 1)">Prev</button>
                        <span class="muted">Page {{ runsTab.meta.current_page }} / {{ runsTab.meta.last_page }}</span>
                        <button class="button button-small" :disabled="runsTab.meta.current_page >= runsTab.meta.last_page" @click="loadRuns(runsTab.meta.current_page + 1)">Next</button>
                    </div>
                </div>

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
                                <td><a :href="buildRunUrl(run.id)">{{ run.id }}</a></td>
                                <td>{{ run.finished_at ?? '—' }}</td>
                                <td class="wrap-text">{{ run.failure_message ?? '—' }}</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="task-tab-pagination">
                        <button class="button button-small" :disabled="failuresTab.meta.current_page <= 1" @click="loadFailures(failuresTab.meta.current_page - 1)">Prev</button>
                        <span class="muted">Page {{ failuresTab.meta.current_page }} / {{ failuresTab.meta.last_page }}</span>
                        <button class="button button-small" :disabled="failuresTab.meta.current_page >= failuresTab.meta.last_page" @click="loadFailures(failuresTab.meta.current_page + 1)">Next</button>
                    </div>
                </div>

                <div v-else-if="activeTab === 'logs'" class="task-tab-pane">
                    <div v-if="tabLoading.logs" class="muted">Loading logs...</div>
                    <template v-else>
                        <div v-if="logsTab.selected_run_id" class="muted" style="margin-bottom: 0.6rem;">
                            Latest run: {{ logsTab.selected_run_id }}
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

                <div v-else-if="activeTab === 'documentation'" class="task-tab-pane">
                    <div v-if="tabLoading.documentation" class="muted">Loading documentation...</div>
                    <template v-else>
                        <div class="task-doc-block">
                            <div class="task-overview-label">Description</div>
                            <div class="wrap-text">{{ docsTab.description ?? '—' }}</div>
                        </div>
                        <div class="task-doc-block">
                            <div class="task-overview-label">Documentation</div>
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

