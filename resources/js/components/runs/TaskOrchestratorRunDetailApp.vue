<template>
    <div>
        <div class="page-header">
            <div>
                <h1 class="page-title">Task Run Details</h1>
                <p class="page-subtitle">Detailed view of one execution, including logs and progress.</p>

                <div v-if="isLive" style="margin-top: 0.85rem;">
                    <span class="live-indicator">
                        <span class="live-dot"></span>
                        Live
                    </span>
                </div>
            </div>

            <div class="nav-actions">
                <a class="button button-secondary" :href="runsIndexUrl">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                    Back to runs
                </a>

                <form method="POST" :action="retryUrl">
                    <input type="hidden" name="_token" :value="csrfToken">
                    <button class="button" type="submit">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                        Run again
                    </button>
                </form>
            </div>
        </div>

        <div class="cards">
            <div class="card summary-card">
                <div class="summary-label">Status</div>
                <div class="summary-value">{{ capitalize(run.status) }}</div>
            </div>

            <div class="card summary-card">
                <div class="summary-label">Task</div>
                <div class="summary-value">{{ run.task_name }}</div>
            </div>

            <div class="card summary-card">
                <div class="summary-label">Command</div>
                <div class="summary-value">{{ run.command }}</div>
            </div>
        </div>

        <div class="stack">
            <div class="panel">
                <div class="panel-header">Run Metadata</div>

                <div class="detail-grid">
                    <div class="detail-label">ID</div>
                    <div>{{ run.id }}</div>

                    <div class="detail-label">Pipeline</div>
                    <div>
                        <span v-if="run.pipeline_id" class="badge badge-trigger-pipeline">
                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                            Pipeline run
                        </span>
                        <span v-else>—</span>
                        <div v-if="run.pipeline_id" class="muted" style="margin-top: 0.35rem;">
                            {{ run.pipeline_id }}
                        </div>
                    </div>

                    <div class="detail-label">Task Name</div>
                    <div>{{ run.task_name }}</div>

                    <div class="detail-label">Label</div>
                    <div>{{ run.task_label }}</div>

                    <div class="detail-label">Command</div>
                    <div>{{ run.command }}</div>

                    <div class="detail-label">Status</div>
                    <div>
                        <span :class="['status-pill', `status-pill--${run.status}`]">
                            <!-- Succeeded Icon -->
                            <svg v-if="run.status === 'succeeded'" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                            <!-- Failed Icon -->
                            <svg v-else-if="run.status === 'failed'" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                            </svg>
                            <!-- Running Icon -->
                            <svg v-else-if="run.status === 'running'" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/><polyline points="10 8 16 12 10 16"/>
                            </svg>
                            <!-- Queued Icon -->
                            <svg v-else-if="run.status === 'queued'" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                            </svg>
                            <!-- Default Icon -->
                            <svg v-else width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                            {{ capitalize(run.status) }}
                        </span>
                    </div>
                    <div class="detail-label">Trigger Type</div>
                    <div>
                        <span :class="['badge', triggerBadgeClass(run.trigger_type)]">
                            <!-- Pipeline icon -->
                            <svg v-if="run.trigger_type === 'pipeline'" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                            <!-- Scheduled icon -->
                            <svg v-else-if="run.trigger_type === 'scheduled'" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <!-- Manual icon -->
                            <svg v-else-if="run.trigger_type === 'manual'" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 9V5a3 3 0 0 0-6 0v4"/><rect x="2" y="9" width="20" height="13" rx="2"/></svg>
                            <!-- Retry icon -->
                            <svg v-else-if="run.trigger_type === 'retry'" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.08"/></svg>
                            <!-- Default icon -->
                            <svg v-else width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            {{ triggerLabel(run.trigger_type) }}
                        </span>
                    </div>

                    <div class="detail-label">Started At</div>
                    <div>{{ run.started_at ?? '—' }}</div>

                    <div class="detail-label">Finished At</div>
                    <div>{{ run.finished_at ?? '—' }}</div>

                    <div class="detail-label">Progress</div>
                    <div v-html="renderProgress(run)"></div>

                    <div class="detail-label">Failure Message</div>
                    <div class="no-truncate">{{ run.failure_message ?? '—' }}</div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">Execution Logs</div>

                <div class="logs-toolbar">
                    <div class="muted">Latest log output from the running command.</div>

                    <label>
                        <input v-model="autoScrollLogs" type="checkbox">
                        Auto-scroll
                    </label>
                </div>

                <div id="run-logs-container">
                    <div v-if="logs.length === 0" class="empty">
                        No logs found for this run.
                    </div>

                    <div v-else ref="logListRef" class="log-list">
                        <div v-for="log in logs" :key="log.id" class="log-entry">
                            <div class="log-meta">
                                [{{ log.level }}] {{ log.created_at ?? '' }}
                            </div>
                            <div class="no-truncate">{{ log.message }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'

const props = defineProps({
    runStatusUrl: {
        type: String,
        required: true,
    },
    runLogsUrl: {
        type: String,
        required: true,
    },
    runsIndexUrl: {
        type: String,
        required: true,
    },
    retryUrl: {
        type: String,
        required: true,
    },
    csrfToken: {
        type: String,
        required: true,
    },
    initialRun: {
        type: Object,
        required: true,
    },
    initialLogs: {
        type: Array,
        required: true,
    },
    pollInterval: {
        type: Number,
        default: 3000,
    },
})

const run = ref({ ...props.initialRun })
const logs = ref([...props.initialLogs])
const autoScrollLogs = ref(true)
const logListRef = ref(null)

let poller = null

const isLive = computed(() => ['queued', 'running'].includes(run.value.status))

function capitalize(value) {
    if (!value) {
        return ''
    }

    return value.charAt(0).toUpperCase() + value.slice(1)
}

function triggerLabel(value) {
    if (!value) {
        return 'Unknown'
    }

    return capitalize(value)
}

function triggerBadgeClass(value) {
    switch (value) {
        case 'scheduled':
            return 'badge-trigger-scheduled'
        case 'pipeline':
            return 'badge-trigger-pipeline'
        case 'retry':
            return 'badge-trigger-retry'
        case 'manual':
            return 'badge-trigger-manual'
        default:
            return 'badge-trigger-default'
    }
}

function renderProgress(data) {
    if (data.progress_current === null || data.progress_current === undefined) {
        return '—'
    }

    let ratioText = String(data.progress_current)
    let percentage = 0

    if (data.progress_total !== null && data.progress_total !== undefined && Number(data.progress_total) > 0) {
        ratioText += ` / ${data.progress_total}`
        percentage = Math.round((Number(data.progress_current) / Number(data.progress_total)) * 100)

        if (percentage < 0) percentage = 0
        if (percentage > 100) percentage = 100
    }

    const message = escapeHtml(data.progress_message || 'Processing...')

    return `
        <div class="progress-stack">
            <div class="progress-meta">
                <span>${ratioText}</span>
                <span>${percentage}%</span>
            </div>

            <div class="progress-track">
                <div class="progress-fill" style="width: ${percentage}%;"></div>
            </div>

            <div class="progress-caption">${message}</div>
        </div>
    `
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;')
}

async function refreshRunStatus() {
    const response = await fetch(props.runStatusUrl, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
        },
    })

    if (!response.ok) {
        return null
    }

    const data = await response.json()
    run.value = data

    return data
}

async function refreshRunLogs() {
    const currentList = logListRef.value
    const wasNearBottom = currentList
        ? (currentList.scrollTop + currentList.clientHeight >= currentList.scrollHeight - 40)
        : true

    const response = await fetch(props.runLogsUrl, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
        },
    })

    if (!response.ok) {
        return
    }

    const data = await response.json()
    logs.value = data.logs

    await nextTick()

    if (logListRef.value && autoScrollLogs.value && wasNearBottom) {
        logListRef.value.scrollTop = logListRef.value.scrollHeight
    }
}

async function refreshRunPage() {
    try {
        const data = await refreshRunStatus()
        await refreshRunLogs()

        if (data && ['succeeded', 'failed', 'cancelled'].includes(data.status) && poller) {
            window.clearInterval(poller)
            poller = null
        }
    } catch (error) {
        console.error('Task run refresh failed:', error)
    }
}

onMounted(() => {
    poller = window.setInterval(refreshRunPage, props.pollInterval)
})

onBeforeUnmount(() => {
    if (poller) {
        window.clearInterval(poller)
    }
})
</script>
