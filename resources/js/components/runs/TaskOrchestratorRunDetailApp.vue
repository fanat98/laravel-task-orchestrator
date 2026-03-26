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
                <a class="button button-secondary" :href="runsIndexUrl">Back to runs</a>

                <form method="POST" :action="retryUrl">
                    <input type="hidden" name="_token" :value="csrfToken">
                    <button class="button" type="submit">Run again</button>
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
                        <span :class="['status-badge', `status-${run.status}`]">
                            {{ capitalize(run.status) }}
                        </span>
                    </div>
                    <div class="detail-label">Trigger Type</div>
                    <div>
                        <span :class="['badge', triggerBadgeClass(run.trigger_type)]">
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
