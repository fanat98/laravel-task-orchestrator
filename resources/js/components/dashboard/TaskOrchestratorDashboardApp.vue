<template>
    <div>
        <div class="cards">
            <div class="card summary-card">
                <div class="summary-label">Registered Tasks</div>
                <div class="summary-value">{{ summary.registered_tasks }}</div>
            </div>

            <div class="card summary-card">
                <div class="summary-label">Total Runs</div>
                <div class="summary-value">{{ summary.total_runs }}</div>
            </div>

            <div class="card summary-card">
                <div class="summary-label">Running</div>
                <div class="summary-value">{{ summary.running_runs }}</div>
            </div>

            <div class="card summary-card">
                <div class="summary-label">Failed</div>
                <div class="summary-value">{{ summary.failed_runs }}</div>
            </div>
        </div>

        <div class="panel" style="margin-bottom: 1.5rem;">
            <div class="panel-header">System Status</div>

            <div class="health-card">
                <div :class="['health-badge', `health-${health.status}`]">
                    {{ capitalize(health.status) }}
                </div>

                <div>{{ health.message }}</div>

                <div class="health-meta">
                    <div class="health-meta-item">
                        <div class="health-meta-label">Pending Queue Jobs</div>
                        <div class="health-meta-value">{{ health.pending_jobs ?? 'n/a' }}</div>
                    </div>

                    <div class="health-meta-item">
                        <div class="health-meta-label">Stale Queued Runs</div>
                        <div class="health-meta-value">{{ health.stale_queued_runs }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="stack">
            <div class="panel">
                <div class="panel-header">Task Groups</div>

                <div v-if="taskGroups.length === 0" class="empty">
                    No grouped tasks available.
                </div>

                <div v-else class="group-grid">
                    <div v-for="group in taskGroups" :key="group.name" class="group-card">
                        <div class="group-card-header">
                            <div class="group-card-title">{{ group.name }}</div>
                            <div class="group-card-count">{{ group.tasks.length }} tasks</div>
                        </div>

                        <div class="group-task-list">
                            <div v-for="task in group.tasks" :key="task.name" class="group-task-item">
                                <div class="group-task-main">
                                    <!-- Top Row -->
                                    <div class="group-task-top">
                                        <div class="group-task-label">
                                            {{ task.label }}
                                        </div>

                                        <span
                                            v-if="task.last_status"
                                            :class="['status-badge', `status-${task.last_status}`]"
                                        >
                                            {{ capitalize(task.last_status) }}
                                        </span>
                                    </div>

                                    <!-- Badges -->
                                    <div class="task-badges">
                                        <span v-if="task.schedule?.human || task.schedule?.expression" class="badge badge-schedule">
                                            ⏱ {{ task.schedule?.human ?? task.schedule?.expression }}
                                        </span>

                                        <span :class="['badge', 'badge-trigger', triggerBadgeClass(task.last_trigger_type)]">
                                            ⚡ {{ triggerLabel(task.last_trigger_type) }}
                                        </span>
                                    </div>

                                    <div v-if="task.depends_on?.length" class="task-dependencies">
                                        <span class="task-dependencies-label">Depends on:</span>
                                        <span
                                            v-for="dependency in task.depends_on"
                                            :key="dependency"
                                            class="badge badge-dependency"
                                        >
                                            {{ dependency }}
                                        </span>
                                    </div>

                                    <!-- Meta -->
                                    <div class="group-task-submeta">
                                        <span>⏱ Next: {{ task.next_run ?? '—' }}</span>
                                        <span>✔ Last: {{ task.last_run ?? '—' }}</span>
                                    </div>

                                    <!-- History -->
                                    <div class="task-run-history">
                                        <span class="task-run-history-label">Recent:</span>

                                        <div v-if="task.recent_runs?.length" class="task-run-history-dots">
                                            <a
                                                v-for="run in task.recent_runs"
                                                :key="run.id"
                                                :href="buildRunUrl(run.id)"
                                                :title="buildRunHistoryTitle(run)"
                                                :class="['task-run-dot', `task-run-dot-${run.status}`]"
                                            ></a>
                                        </div>

                                        <span v-else class="muted">No runs yet</span>
                                    </div>
                                </div>

                                <div class="group-task-actions">
                                    <form
                                        v-if="task.allow_manual_run"
                                        method="POST"
                                        :action="buildTaskRunUrl(task.name)"
                                    >
                                        <input type="hidden" name="_token" :value="csrfToken">
                                        <button
                                            class="button button-small button-primary"
                                            type="submit"
                                            title="Run task"
                                        >
                                            ▶
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">Latest Runs</div>

                <div v-if="latestRuns.length === 0" class="empty">
                    No task runs found yet.
                </div>

                <table v-else>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Task</th>
                        <th>Command</th>
                        <th>Status</th>
                        <th>Trigger</th>
                        <th>Started</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="run in latestRuns" :key="run.id">
                        <td>
                            <a :href="buildRunUrl(run.id)">
                                {{ run.id }}
                            </a>
                        </td>
                        <td>{{ run.task_label }}</td>
                        <td>{{ run.command }}</td>
                        <td>
                            <span :class="['status-badge', `status-${run.status}`]">
                                {{ capitalize(run.status) }}
                            </span>
                        </td>
                        <td>
                            <span :class="['badge', triggerBadgeClass(run.trigger_type)]">
                                {{ triggerLabel(run.trigger_type) }}
                            </span>
                        </td>
                        <td>{{ run.started_at ?? '—' }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div class="panel">
                <div class="panel-header">Latest Failed Runs</div>

                <div v-if="latestFailedRuns.length === 0" class="empty">
                    No failed task runs found.
                </div>

                <table v-else>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Task</th>
                        <th>Failure</th>
                        <th>Trigger</th>
                        <th>Finished</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="run in latestFailedRuns" :key="run.id">
                        <td>
                            <a :href="buildRunUrl(run.id)">
                                {{ run.id }}
                            </a>
                        </td>
                        <td>{{ run.task_label }}</td>
                        <td class="truncate">{{ run.failure_message ?? '—' }}</td>
                        <td>
                            <span :class="['badge', triggerBadgeClass(run.trigger_type)]">
                                {{ triggerLabel(run.trigger_type) }}
                            </span>
                        </td>
                        <td>{{ run.finished_at ?? '—' }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue'

const props = defineProps({
    dashboardApiUrl: { type: String, required: true },
    runBaseUrl: { type: String, required: true },
    taskRunBaseUrl: { type: String, required: true },
    csrfToken: { type: String, required: true },
    initialSummary: { type: Object, required: true },
    initialHealth: { type: Object, required: true },
    initialLatestRuns: { type: Array, required: true },
    initialLatestFailedRuns: { type: Array, required: true },
    initialTaskGroups: { type: Array, required: true },
    pollInterval: { type: Number, default: 5000 },
})

const summary = ref({ ...props.initialSummary })
const health = ref({ ...props.initialHealth })
const latestRuns = ref([...props.initialLatestRuns])
const latestFailedRuns = ref([...props.initialLatestFailedRuns])
const taskGroups = ref([...props.initialTaskGroups])

let poller = null

function buildRunHistoryTitle(run) {
    const status = capitalize(run.status)
    const trigger = capitalize(run.trigger_type ?? 'manual')
    const startedAt = run.started_at ?? '—'

    return `${status} • ${trigger} • ${startedAt}`
}

function capitalize(value) {
    if (!value) return ''
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

function buildRunUrl(runId) {
    return `${props.runBaseUrl}/${runId}`
}

function buildTaskRunUrl(taskName) {
    return `${props.taskRunBaseUrl}/${taskName}/run`
}

async function refreshDashboard() {
    try {
        const response = await fetch(props.dashboardApiUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
        })

        if (!response.ok) {
            return
        }

        const data = await response.json()

        summary.value = data.summary
        health.value = data.health
        latestRuns.value = data.latest_runs
        latestFailedRuns.value = data.latest_failed_runs
        taskGroups.value = data.task_groups
    } catch (error) {
        console.error('Dashboard refresh failed:', error)
    }
}

onMounted(() => {
    poller = window.setInterval(refreshDashboard, props.pollInterval)
})

onBeforeUnmount(() => {
    if (poller) {
        window.clearInterval(poller)
    }
})
</script>
