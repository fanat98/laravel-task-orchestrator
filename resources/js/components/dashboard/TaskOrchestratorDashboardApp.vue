<template>
    <div class="dashboard">

        <!-- ── Stat Cards ───────────────────────────────────────────────── -->
        <div class="stat-cards">
            <div class="stat-card stat-card--tasks">
                <div class="stat-card-body">
                    <div class="stat-card-label">REGISTERED TASKS</div>
                    <div class="stat-card-value">{{ summary.registered_tasks }}</div>
                </div>
                <div class="stat-card-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/>
                    </svg>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-body">
                    <div class="stat-card-label">TOTAL RUNS</div>
                    <div class="stat-card-value">{{ summary.total_runs }}</div>
                </div>
                <div class="stat-card-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.08"/>
                    </svg>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-card-body">
                    <div class="stat-card-label">RUNNING</div>
                    <div class="stat-card-value">{{ summary.running_runs }}</div>
                </div>
                <div class="stat-card-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                </div>
            </div>

            <div class="stat-card stat-card--failed">
                <div class="stat-card-body">
                    <div class="stat-card-label">FAILED</div>
                    <div class="stat-card-value stat-card-value--failed">{{ summary.failed_runs }}</div>
                </div>
                <div class="stat-card-icon stat-card-icon--failed">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- ── Health Row ────────────────────────────────────────────────── -->
        <div class="health-row">
            <div :class="['health-main', `health-main--${health.status ?? 'healthy'}`]">
                <div class="health-main-icon">
                    <template v-if="health.status === 'healthy' || !health.status">
                        <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                    </template>
                    <template v-else-if="health.status === 'warning'">
                        <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                        </svg>
                    </template>
                    <template v-else>
                        <svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>
                        </svg>
                    </template>
                </div>
                <div class="health-main-title">{{ capitalize(health.status ?? 'Healthy') }}</div>
                <div class="health-main-msg">{{ health.message }}</div>
                <div class="health-main-badges">
                    <div class="health-status-pill">
                        <div class="health-status-pill-label">QUEUE STATUS</div>
                        <div class="health-status-pill-value">{{ capitalize(health.queue?.status) || '—' }}</div>
                    </div>
                    <div class="health-status-pill">
                        <div class="health-status-pill-label">SCHEDULER</div>
                        <div class="health-status-pill-value">{{ capitalize(health.scheduler?.status) || '—' }}</div>
                    </div>
                </div>
            </div>

            <div class="health-metric-card">
                <div class="health-metric-header">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="2" width="20" height="8" rx="2"/><rect x="2" y="14" width="20" height="8" rx="2"/><line x1="6" y1="6" x2="6.01" y2="6"/><line x1="6" y1="18" x2="6.01" y2="18"/>
                    </svg>
                    QUEUE WORKER
                </div>
                <div class="health-metric-value">{{ capitalize(health.queue_worker?.status) || '—' }}</div>
                <div class="health-metric-bar">
                    <div :class="['health-metric-bar-fill', `bar-fill--${health.queue_worker?.status ?? 'inactive'}`]"></div>
                </div>
            </div>

            <div class="health-metric-card">
                <div class="health-metric-header">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    PENDING JOBS
                </div>
                <div class="health-metric-value">{{ health.queue?.pending_jobs ?? health.pending_jobs ?? 0 }}</div>
                <div class="health-metric-caption">Within latency targets</div>
            </div>

            <div class="health-metric-card">
                <div class="health-metric-header">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
                    </svg>
                    OLDEST JOB AGE
                </div>
                <div class="health-metric-value">{{ formatAge(health.queue?.oldest_pending_job_age_seconds ?? health.oldest_pending_job_age_seconds) }}</div>
                <div class="health-metric-caption">Nominal threshold</div>
            </div>
        </div>

        <!-- ── Task Groups ────────────────────────────────────────────────── -->
        <div v-if="taskGroups.length > 0" class="groups-grid">
            <div v-for="(group, groupIdx) in taskGroups" :key="group.name" class="group-panel">
                <div class="group-panel-header">
                    <div class="group-panel-title">
                        <div :class="['group-panel-icon', `group-panel-icon--${groupIdx % 5}`]">
                            <!-- ETL Imports -->
                            <template v-if="group.name.toLowerCase().includes('etl') || group.name.toLowerCase().includes('import')">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                                </svg>
                            </template>
                            <!-- User Management -->
                            <template v-else-if="group.name.toLowerCase().includes('user') || group.name.toLowerCase().includes('workforce') || group.name.toLowerCase().includes('identity')">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                </svg>
                            </template>
                            <!-- Control Verification / Compliance -->
                            <template v-else-if="group.name.toLowerCase().includes('control') || group.name.toLowerCase().includes('verification') || group.name.toLowerCase().includes('compliance')">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/>
                                </svg>
                            </template>
                            <!-- Monitoring / Health -->
                            <template v-else-if="group.name.toLowerCase().includes('monitor') || group.name.toLowerCase().includes('health') || group.name.toLowerCase().includes('watch')">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
                                </svg>
                            </template>
                            <!-- Notifications / Mail -->
                            <template v-else-if="group.name.toLowerCase().includes('notification') || group.name.toLowerCase().includes('mail') || group.name.toLowerCase().includes('alert')">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                                </svg>
                            </template>
                            <!-- Database / Storage -->
                            <template v-else-if="group.name.toLowerCase().includes('database') || group.name.toLowerCase().includes('storage') || group.name.toLowerCase().includes('backup')">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>
                                </svg>
                            </template>
                            <!-- API / Integration -->
                            <template v-else-if="group.name.toLowerCase().includes('api') || group.name.toLowerCase().includes('integration') || group.name.toLowerCase().includes('sync')">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 16l3-8 3 8-6-2-6 2z"/><path d="M2 16l3-8 3 8-6-2-6 2z"/><path d="M7 21h10"/><path d="M12 3v18"/>
                                </svg>
                            </template>
                            <!-- Reports / Analytics -->
                            <template v-else-if="group.name.toLowerCase().includes('report') || group.name.toLowerCase().includes('analytics') || group.name.toLowerCase().includes('stats')">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                                </svg>
                            </template>
                            <!-- Security -->
                            <template v-else-if="group.name.toLowerCase().includes('security') || group.name.toLowerCase().includes('auth') || group.name.toLowerCase().includes('permission')">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><circle cx="12" cy="16" r="1"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                </svg>
                            </template>
                            <!-- Cleanup / Maintenance -->
                            <template v-else-if="group.name.toLowerCase().includes('cleanup') || group.name.toLowerCase().includes('maintenance') || group.name.toLowerCase().includes('delete')">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/>
                                </svg>
                            </template>
                            <!-- Default fallback -->
                            <template v-else>
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                </svg>
                            </template>
                        </div>
                        {{ group.name }}
                    </div>
                    <span class="group-panel-count">{{ group.tasks.length }} TASK{{ group.tasks.length !== 1 ? 'S' : '' }}</span>
                </div>

                <div class="group-tasks">
                    <div
                        v-for="task in group.tasks"
                        :key="task.name"
                        :class="['task-item', task.is_running ? 'task-item--running' : '']"
                    >
                        <div class="task-item-main">
                            <div class="task-item-top">
                                <div class="task-item-title">
                                    <a :href="buildTaskUrl(task.name)">{{ task.label }}</a>
                                </div>
                                <div class="task-item-badges">
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
                                        <!-- Cancelled Icon -->
                                        <svg v-else-if="task.last_status === 'cancelled'" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
                                        </svg>
                                        <!-- Pending Icon -->
                                        <svg v-else-if="task.last_status === 'pending'" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                                        </svg>
                                        <!-- Default Icon -->
                                        <svg v-else width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                                        </svg>
                                        {{ taskStatusLabel(task) }}
                                    </span>
                                </div>
                            </div>

                            <div v-if="task.description" class="task-item-desc">{{ task.description }}</div>

                            <!-- Schedule + Trigger badges -->
                            <div class="task-item-tag-row">
                                <span v-if="task.schedule?.human || task.schedule?.expression" class="task-tag task-tag--schedule">
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    {{ task.schedule?.human ?? task.schedule?.expression }}
                                </span>
                                <span :class="['task-tag', `task-tag--trigger-${task.last_trigger_type ?? 'default'}`]">
                                    <!-- Pipeline icon -->
                                    <svg v-if="task.last_trigger_type === 'pipeline'" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                                    <!-- Scheduled icon -->
                                    <svg v-else-if="task.last_trigger_type === 'scheduled'" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    <!-- Manual icon -->
                                    <svg v-else-if="task.last_trigger_type === 'manual'" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 9V5a3 3 0 0 0-6 0v4"/><rect x="2" y="9" width="20" height="13" rx="2"/></svg>
                                    <!-- Retry icon -->
                                    <svg v-else-if="task.last_trigger_type === 'retry'" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.08"/></svg>
                                    <!-- Default icon -->
                                    <svg v-else width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                    {{ triggerLabel(task.last_trigger_type) }}
                                </span>
                            </div>

                            <!-- Dependencies -->
                            <div v-if="task.depends_on?.length" class="task-item-deps">
                                <span class="task-item-deps-label">Depends on:</span>
                                <span v-for="dep in task.depends_on" :key="dep" class="task-dep-badge">{{ dep }}</span>
                            </div>

                            <div v-if="task.recent_runs?.length" class="task-item-dots">
                                <a
                                    v-for="run in task.recent_runs"
                                    :key="run.id"
                                    :href="buildRunUrl(run.id)"
                                    :title="buildRunHistoryTitle(run)"
                                    :class="['run-dot', `run-dot--${run.status}`]"
                                ></a>
                            </div>

                            <div v-if="task.is_running" class="task-item-progress">
                                <div class="task-item-progress-bar"></div>
                            </div>

                            <div class="task-item-meta">
                                <span>
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                    Next: <strong>{{ task.next_run ?? '—' }}</strong>
                                </span>
                                <span>
                                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                    Last: <strong>{{ task.last_run ?? '—' }}</strong>
                                </span>
                            </div>
                        </div>

                        <div class="task-item-actions">
                            <form
                                v-if="task.allow_manual_run"
                                method="POST"
                                :action="buildTaskRunUrl(task.name)"
                            >
                                <input type="hidden" name="_token" :value="csrfToken">
                                <button
                                    class="run-btn"
                                    type="submit"
                                    :disabled="!task.can_start"
                                    :title="startButtonTitle(task)"
                                >
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="currentColor">
                                        <polygon points="5 3 19 12 5 21 5 3"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="empty-groups">No task groups configured.</div>

        <!-- ── Bottom Row ────────────────────────────────────────────────── -->
        <div class="runs-grid">
            <div class="runs-panel">
                <div class="runs-panel-header">
                    <div class="runs-panel-title">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-3.08"/>
                        </svg>
                        Latest Runs
                    </div>
                    <a :href="runsIndexUrl" class="panel-view-link">VIEW ALL</a>
                </div>

                <div v-if="latestRuns.length === 0" class="empty">No task runs yet.</div>
                <table v-else class="runs-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>TASK</th>
                            <th>STATUS</th>
                            <th>TRIGGER</th>
                            <th>TIMESTAMP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="run in latestRuns" :key="run.id">
                            <td class="run-id"><a :href="buildRunUrl(run.id)">{{ String(run.id).slice(0, 6) }}</a></td>
                            <td class="run-task">{{ run.task_label }}</td>
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
                                <!-- Cancelled Icon -->
                                <svg v-else-if="run.status === 'cancelled'" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/>
                                </svg>
                                <!-- Default Icon -->
                                <svg v-else width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                                </svg>
                                {{ capitalize(run.status) }}
                            </span></td>
                            <td class="run-trigger">{{ capitalize(run.trigger_type) }}</td>
                            <td class="run-ts">{{ timeAgo(run.started_at) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="runs-panel">
                <div class="runs-panel-header">
                    <div class="runs-panel-title runs-panel-title--failed">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                        </svg>
                        Latest Failed Runs
                    </div>
                    <a :href="failedRunsUrl" class="panel-view-link panel-view-link--failed">VIEW ERRORS</a>
                </div>

                <div v-if="latestFailedRuns.length === 0" class="empty">No failed runs.</div>
                <table v-else class="runs-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>TASK</th>
                            <th>FAILURE</th>
                            <th>TRIGGER</th>
                            <th>TIMESTAMP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="run in latestFailedRuns" :key="run.id">
                            <td class="run-id"><a :href="buildRunUrl(run.id)">{{ String(run.id).slice(0, 6) }}</a></td>
                            <td class="run-task">{{ run.task_label }}</td>
                            <td class="run-failure">
                                <div v-if="parseFailureCode(run.failure_message)" class="failure-code">{{ parseFailureCode(run.failure_message) }}</div>
                                <div class="failure-detail">{{ truncateFailure(run.failure_message) }}</div>
                            </td>
                            <td class="run-trigger">{{ capitalize(run.trigger_type) }}</td>
                            <td class="run-ts">{{ timeAgo(run.finished_at) }}</td>
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
    runsIndexUrl: { type: String, default: '' },
    failedRunsUrl: { type: String, default: '' },
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

function capitalize(value) {
    if (!value) return ''
    return value.charAt(0).toUpperCase() + value.slice(1)
}

function formatAge(seconds) {
    if (seconds === null || seconds === undefined) return '—'
    if (seconds === 0) return '0s'
    if (seconds < 60) return `${seconds}s`
    if (seconds < 3600) return `${(seconds / 60).toFixed(1)}m`
    return `${(seconds / 3600).toFixed(1)}h`
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

function timeAgo(dateString) {
    if (!dateString) return '—'
    const diff = Math.floor((Date.now() - new Date(dateString).getTime()) / 1000)
    if (diff < 5) return 'just now'
    if (diff < 60) return `${diff}s ago`
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`
    return `${Math.floor(diff / 86400)}d ago`
}

function taskStatusLabel(task) {
    if (task.is_running) return 'Active'
    if (!task.last_status) return '—'
    return capitalize(task.last_status)
}

function parseFailureCode(message) {
    if (!message) return null
    const match = message.match(/^([A-Z][A-Z0-9_]{2,}):?/)
    return match ? match[1] : null
}

function truncateFailure(message) {
    if (!message) return '—'
    const code = parseFailureCode(message)
    const detail = code ? message.replace(/^[A-Z][A-Z0-9_]{2,}:?\s*/, '') : message
    return detail.length > 36 ? detail.slice(0, 36) + '…' : detail
}

function buildRunHistoryTitle(run) {
    const status = capitalize(run.status)
    const trigger = capitalize(run.trigger_type ?? 'manual')
    const startedAt = run.started_at ?? '—'
    return `${status} • ${trigger} • ${startedAt}`
}

function triggerLabel(value) {
    if (!value) return 'Unknown'
    return capitalize(value)
}

function startButtonTitle(task) {
    if (task.can_start) return 'Run task'
    if (task.is_running) return 'Task is already running'
    if (task.is_queued) return 'Task is already queued'
    if (task.is_blocked_by_dependencies) {
        const blocked = (task.blocked_by_task_names || []).join(', ')
        return blocked ? `Blocked by dependencies: ${blocked}` : 'Blocked by dependencies'
    }
    return 'Task cannot be started'
}

function buildRunUrl(runId) {
    return `${props.runBaseUrl}/${runId}`
}

function buildTaskRunUrl(taskName) {
    return `${props.taskRunBaseUrl}/${taskName}/run`
}

function buildTaskUrl(taskName) {
    return `${props.taskRunBaseUrl}/${taskName}`
}

async function refreshDashboard() {
    try {
        const response = await fetch(props.dashboardApiUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
        })

        if (!response.ok) return

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
    if (poller) window.clearInterval(poller)
})
</script>
