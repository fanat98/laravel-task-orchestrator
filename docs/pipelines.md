# Pipelines

Pipelines are automatically created based on task dependencies.

---

## 🔗 What is a Pipeline?

A pipeline is a chain of tasks connected via dependencies:

```id="c8j4k1"
control-requirements → resources → services → notify
```

---

## ⚙️ How it Works

1. A task starts
2. It completes successfully
3. All dependent tasks are triggered
4. All runs share a pipeline context

---

## 🚀 Example

### Discovery

```php id="w4j2k9"
'import:resources' => [
    'name' => 'import-resources',
    'depends_on' => ['control-requirements'],
],
```

---

### Execution Flow

```id="r9d1m6"
control-requirements
    ↓
import-resources
    ↓
import-services
```

---

## 🧠 Trigger Types

Each run has a trigger:

* `manual`
* `scheduled`
* `pipeline`
* `retry`

---

## 🔄 Pipeline Execution Rules

* downstream tasks only run if parent **succeeded**
* failed task stops the pipeline
* manual run starts a new pipeline
* scheduled run starts a new pipeline

---

## 📊 Pipeline View

The UI shows:

* task flow
* execution order
* status per step
* timestamps
* links to runs

---

## 🧩 Pipeline Behavior

### Success

```id="k5x9p3"
A ✅ → B → C
```

---

### Failure

```id="b2n8r4"
A ❌ → B (not executed)
```

---

## 🔁 Retry Behavior

Retrying a task:

* creates a new run
* trigger = `retry`
* can restart downstream flow

---

## 💡 Best Practices

* keep pipelines simple and linear
* avoid deep dependency chains
* monitor failed tasks early
* use timeouts for long tasks

---

## Next

→ Authorization
