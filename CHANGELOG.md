# Changelog

All notable changes to this project will be documented in this file.


---
## [1.7.1] - 2026-05-17

### Added
- Comprehensive PHPUnit test coverage across HTTP controllers, integration flows, console commands, queued jobs, support services, domain objects, registry behavior, and property-based invariants.
- GitHub Actions workflow for running the package PHPUnit test suite.
- Operations documentation covering scheduler setup, queue workers, health monitoring, stale run recovery, and common troubleshooting.

### Changed
- Reworked the README for public package usage with clearer positioning, installation steps, quick-start discovery configuration, generic dependency pipeline examples, dashboard access, health checks, stale run recovery, screenshots, support status, and troubleshooting.
- Improved `composer.json` package metadata with a stronger description, Packagist keywords, homepage, and support links.
- Updated installation and authorization documentation to match the current package configuration and scheduler behavior.
- Updated README badges for Packagist version, downloads, license, PHP version, Laravel support, and test workflow status.

---
## [1.7.0] - 2026-05-15

### Added
- **Task mail notifications**: Configurable email notifications on task failure and recovery (success after previous failure).
- Global notification configuration via `task-orchestrator.notifications` (enabled, recipients).
- Per-task notification configuration via discovery metadata `notifications` key, with precedence over global settings.
- `NotificationEvaluationAction` orchestrates notification dispatch after terminal status transitions.
- `NotificationConfigResolver` resolves effective enabled state and recipient list with global/per-task precedence.
- `RecoveryDetector` determines if a successful run represents recovery from a previous failure.
- `TaskFailedMailable` and `TaskRecoveredMailable` queued mailables with Blade email templates.
- `NotificationPayload` value object for resolved notification data (validated emails, max 50 recipients).
- `TaskDefinition::notifications()` fluent method for immutable per-task notification config.
- `DiscoveredTaskDefinitionFactory` maps `notifications` key from discovery metadata.
- Cache-based idempotence to prevent duplicate notifications per run/status.
- Graceful degradation: notifications skip silently when `illuminate/mail` is not registered.
- RFC 5322 email validation with invalid address filtering and warning logs.
- Published Blade views via existing `task-orchestrator-views` publish tag for template customization.
- Property-based tests (innmind/black-box) covering config resolution, email filtering, recovery detection, idempotence, and mail exception resilience.
- Integration tests for failure notification flow, recovery notification flow, and service provider conditional registration.
- Unit tests for `TaskFailedMailable` and `TaskRecoveredMailable`.

### Changed
- `ExecuteTaskRunAction` now accepts an optional `NotificationEvaluationAction` dependency and triggers notification evaluation after terminal status updates.
- `ExecuteTaskRunJob::failed()` now triggers notification evaluation for timeout/exception failures.
- `TaskOrchestratorServiceProvider` conditionally registers notification services when `mail.manager` is bound.

---
## [1.6.1] - 2026-05-04

### Fixed
- **Queue flood prevention**: `QueueHeartbeatJob` now implements `ShouldBeUnique` with a 120-second lock to prevent thousands of duplicate jobs accumulating when the queue worker is down.
- **Scheduled task guard**: `RunScheduledTaskCommand` now checks queue worker heartbeat health before dispatching. When the queue worker is down, scheduled tasks are skipped with a warning instead of creating orphaned task run records.
- **Run history ordering**: Replaced `latest('started_at'), latest('created_at')` with `COALESCE(finished_at, started_at, created_at) DESC` across all run queries. Runs with `NULL started_at` (stale queued runs marked as failed) now sort correctly by their most recent activity timestamp.

### Changed
- `DashboardController`, `DashboardStatusController`, `TaskRunIndexController`, `FailedTaskRunIndexController`, `TaskDetailDataProvider`, and `TaskStartBlockingEvaluator` all use consistent `COALESCE`-based ordering for task run queries.

---
## [1.6.0] - 2026-04-02

### 🎨 Enhanced
- **UI/UX Consistency Overhaul**: Complete visual redesign for consistent user experience across all views
- **Status Badges**: Added thematic icons to all status badges (succeeded ✅, failed ❌, running ▶️, queued ⏰, cancelled ⛔)
- **Trigger Badges**: Enhanced trigger badges with contextual icons (pipeline 🔗, scheduled ⏰, manual 🔒, retry 🔄)
- **Group Icons**: Intelligent thematic icons for task groups based on naming patterns (ETL 📥, Users 👥, Security 🔒, etc.)
- **Duration Formatting**: Smart duration display (45s, 22m 15s, 2h 15m) replacing raw seconds for better readability
- **UUID Optimization**: Shortened UUIDs to 6-8 characters across all tables and cards for cleaner interface
- **Health Animation**: Added subtle breathing animation to dashboard health icon for enhanced visual feedback
- **Button Consistency**: Unified button styling with proper icons across Run Detail and Task Detail views

### 🔧 Improved
- **Badge Styling**: Consistent flexbox layout, unified heights, and proper icon-text alignment
- **CSS Architecture**: Enhanced badge and pill classes with better symmetry and spacing
- **Navigation Flow**: Improved button placement and icon consistency in detail views
- **Visual Hierarchy**: Better contrast and spacing for improved readability

### 🎯 Technical
- **Icon System**: Comprehensive SVG icon library with consistent sizing (10px-14px) and stroke weights
- **CSS Classes**: Migrated from `status-badge` to `status-pill` for better consistency
- **Component Harmony**: Unified design patterns across Vue components and Blade templates
- **Responsive Design**: Maintained mobile-friendly layouts while enhancing desktop experience

---
## [1.5.1] - 2026-04-01

### Added
- Persistent per-run timeout tracking on `task_runs` via `timeout_seconds` and `timeout_at` so stale-run handling no longer depends solely on the current task definition.
- Regression coverage for task dispatch timeout persistence, stale-run recovery, and preserving original run deadlines across repeated processing attempts.

### Changed
- `StartTaskAction` now stores the effective timeout on the task run record when dispatching `ExecuteTaskRunJob`.
- `ExecuteTaskRunAction` now preserves an existing run's original `started_at` and `timeout_at` values instead of resetting them on a later execution attempt.
- `RecoverStaleTaskRunsCommand` now resolves stale runs primarily from persisted per-run timeout data, while still supporting explicit global timeout overrides.

### Fixed
- Fixed stale task runs remaining stuck in `running` after their configured timeout when worker timeout failure hooks were not reached reliably.
- Fixed recovery behavior for long-running tasks whose timeout definition changed after the run had already been queued or started.

## [1.5.0] - 2026-04-01

### Added
- Topbar redesign: logo icon, sticky positioning, active-state navigation indicators, user avatar with initials, and dedicated theme toggle with sun/moon SVG icons.
- Dashboard stat cards with accent-colored left borders, icons, and hover elevation effects.
- Health monitoring row: large status indicator with icon, status pills for queue/scheduler/worker, and metric cards with animated progress bars.
- Task group panels with color-coded group icons, inline run-history dots, dependency badges, schedule/trigger tags, and live progress bars for running tasks.
- Recent runs and failed runs panels with structured table layout and failure detail display.
- Task detail page: stat cards row (group, queue, timeout, last status, last run), info cards for dependencies and run history, and polished tab bar with pagination.
- Pagination component with styled page links, ellipsis, and navigation buttons.

### Changed
- Full CSS rewrite from basic utility styles to a comprehensive design-system approach with design tokens, consistent spacing, shadows, and border-radius variables.
- Dashboard layout restructured from simple card grid to a rich multi-section dashboard (stat cards → health row → grouped tasks → recent/failed runs).
- Theme toggle improved: respects `prefers-color-scheme` on first visit, persists choice in `localStorage`, and toggles sun/moon icons instead of emoji.
- Topbar navigation now highlights the active route with an accent-colored underline indicator.
- Task detail page tabs bar: increased gap between tabs and added bottom padding so tabs no longer sit on the border.
- Task detail page tab pagination: buttons now have proper spacing (`1rem` gap) with a subtle top border separator.

### Fixed
- Tabs in `.td-tabs-bar` had no bottom spacing causing them to overlap the border line.
- Pagination buttons in `.task-tab-pagination` were missing styles entirely, causing them to render without any gap.

## [1.4.1] - 2026-03-31

### Fixed
- Removed overlap protection from the scheduled `task-orchestrator:record-scheduler-heartbeat` command so scheduler liveness heartbeats are not blocked by stale scheduler mutexes in Cloud Foundry / distributed environments.

## [1.4.0] - 2026-03-30

### Added
- Queue worker liveness monitoring in dashboard health payload:
    - `queue_worker.status` (`running`, `down`)
    - `queue_worker.last_heartbeat_at`
- Queue worker heartbeat mechanisms:
    - scheduled heartbeat via `QueueHeartbeatJob`
    - execution heartbeat updates in `ExecuteTaskRunJob`
- Task detail page with lazy-loaded tabs and dedicated endpoints:
    - `GET /tasks/{task}`
    - `GET /tasks/{task}/runs`
    - `GET /tasks/{task}/failures`
    - `GET /tasks/{task}/logs`
    - `GET /tasks/{task}/documentation`
- `TaskDetailDataProvider` for task meta, recent runs, startability state, logs, and documentation extraction.
- Task documentation extraction support from command description, `documentation()` method, and `$documentation` property.
- Dashboard task labels now link directly to task detail pages.

### Changed
- Queue worker heartbeat is now hybrid (scheduled + task execution) so liveness detection also works when scheduler is unavailable but workers process tasks.
- Manual task start now starts only the selected task (no upstream chain resolution).
- Task detail page UX polish:
    - structured overview cards/subsections
    - stronger tab styling and spacing
    - default tab is now `Runs` (redundant `Overview` tab removed)
    - logs tab simplified to latest-run logs only
    - task start action integrated into detail header using existing backend startability rules
- Removed redundant dashboard header action buttons (`View tasks`, `View runs`, `View pipelines`) because top navigation already covers these links.

### Docs
- Updated docs with queue worker liveness behavior and `health.queue_worker.heartbeat_max_age_seconds` configuration.
- Updated documentation for task detail page behavior and manual start semantics.

## [1.3.0] - 2026-03-30

### Added
- Centralized task start blocking evaluator (`TaskStartBlockingEvaluator`) reused by UI state and backend guard logic.
- Dependency-aware startability metadata in task payloads:
    - `is_blocked_by_dependencies`
    - `blocked_by_task_names`
    - `start_block_reason`
    - `can_start`
- Dashboard health monitoring for queue and scheduler:
    - queue status (`healthy`, `busy`, `stuck`) based on pending job count and oldest pending job age
    - scheduler status (`running`, `down`) based on scheduler heartbeat timestamp
    - configurable health thresholds and heartbeat settings in `task-orchestrator.health`
- Scheduler heartbeat command and schedule registration:
    - `task-orchestrator:record-scheduler-heartbeat`
    - heartbeat recorded every minute via scheduler registration

### Changed
- `StartTaskAction` now enforces dependency-based blocking through the centralized backend guard path.
- Stale-run recovery scheduler registration now runs without hardcoded `--minutes` override to respect task-specific timeouts.
- Manual task start now starts only the selected task (no upstream chain resolution).
- Dashboard health payload now includes structured `queue` and `scheduler` sections while keeping compatibility top-level metrics.

### Fixed
- Prevented duplicate task starts across trigger paths when dependencies are active or not in a succeeded state.
- Disabled run buttons consistently when backend startability state is false in task list and dashboard views.
- Removed duplicate or unclear run-state helper actions in the UI and simplified button states.
- Replaced mixed pagination rendering with a single package-styled pagination component.
- Prevented manual starts of dependent tasks from automatically dispatching prerequisite tasks.

### Docs
- Updated health monitoring docs to describe queue/scheduler status semantics and configuration.
- Updated pipeline behavior docs to reflect manual start behavior.

## [1.2.0] - 2026-03-27

### Added
- Per-task queue routing via discovery metadata:
    - `queue`
    - `connection`

### Fixed
- `TaskDefinition` immutable builders now preserve `queue` and `connection` across chained calls.
- Retry no longer fails with runtime error when the same task is already queued/running; existing active run is returned instead.

### Docs
- Updated `README.md` and `docs/discovery.md` with queue/connection discovery examples.

## [1.1.0] - 2026-03-26

### 🚀 Added

* Task dependency execution (`depends_on`)
* Automatic pipeline execution (downstream triggering)
* Pipeline view UI
* Trigger types (manual, scheduled, pipeline, retry)
* Dark / Light mode toggle
* Responsive dashboard improvements
* Stale run recovery command
* Per-task timeout configuration (`timeout_minutes`)
* Discovery config inside application (`discovery_path`)

### ✨ Improved

* Dashboard UI redesign (cards, spacing, layout)
* Tables optimized for smaller screens
* Status badges improved
* Buttons and interactions polished
* Pipeline visualization UX


### 🛠 Fixed
 
* Trigger type flickering
* Duplicate task execution edge cases
* Running state inconsistencies
* Missing dependency handling
