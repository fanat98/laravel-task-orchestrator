# Changelog

All notable changes to this project will be documented in this file.

---
## [Unreleased]

### Added
- Centralized task start blocking evaluator (`TaskStartBlockingEvaluator`) reused by UI state and backend guard logic.
- Dependency-aware startability metadata in task payloads:
    - `is_blocked_by_dependencies`
    - `blocked_by_task_names`
    - `start_block_reason`
    - `can_start`

### Changed
- `StartTaskAction` now enforces dependency-based blocking through the centralized backend guard path.
- Stale-run recovery scheduler registration now runs without hardcoded `--minutes` override to respect task-specific timeouts.

### Fixed
- Prevented duplicate task starts across trigger paths when dependencies are active or not in a succeeded state.
- Disabled run buttons consistently when backend startability state is false in task list and dashboard views.
- Removed duplicate or unclear run-state helper actions in the UI and simplified button states.
- Replaced mixed pagination rendering with a single package-styled pagination component.

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
