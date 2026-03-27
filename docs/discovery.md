# Task Discovery

The Task Orchestrator uses a discovery file to define tasks, their metadata, dependencies, and scheduling.

---

## 📍 Location

```id="p7d8k2"
app/TaskOrchestrator/discovery.php
```

---

## 🧩 Basic Structure

```php id="g4v8md"
<?php

return [
    'commands' => [
        'artisan:command' => [
            'name' => 'unique-name',
            'label' => 'Human readable label',
        ],
    ],
];
```

---

## 🧠 Full Example

```php id="h9n2wq"
<?php

return [
    'commands' => [
        'import:control-requirements' => [
            'name' => 'control-requirements',
            'label' => 'Import Control Requirements',
            'group' => 'ETL Imports',
            'group_order' => 10,
            'order' => 10,
            'connection' => 'database',
            'queue' => 'imports',
            'schedule' => [
                'expression' => '* * * * *',
                'human' => 'Every minute',
            ],
            'timeout_minutes' => 30,
        ],

        'import:resources' => [
            'name' => 'import-resources',
            'label' => 'Import Resources',
            'group' => 'ETL Imports',
            'group_order' => 10,
            'order' => 20,
            'depends_on' => ['control-requirements'],
            'timeout_minutes' => 30,
        ],

        'import:services' => [
            'name' => 'import-services',
            'label' => 'Import Services',
            'group' => 'ETL Imports',
            'group_order' => 10,
            'order' => 30,
            'depends_on' => ['import-resources'],
            'timeout_minutes' => 30,
        ],
    ],
];
```

---

## ⚙️ Available Options

### name (required)

Unique internal identifier:

```php id="3g4q1l"
'name' => 'import-services'
```

Used for:

* dependencies
* pipelines
* tracking

---

### label (required)

Displayed in UI:

```php id="s1m4n0"
'label' => 'Import Services'
```

---

### group

Logical grouping in dashboard:

```php id="7x9p2k"
'group' => 'ETL Imports'
```

---

### group_order

Controls group order:

```php id="x5v8q1"
'group_order' => 10
```

Lower = higher priority

---

### order

Order inside a group:

```php id="u8w3b6"
'order' => 20
```

---

### depends_on

Defines dependencies:

```php id="e4n2w9"
'depends_on' => ['control-requirements']
```

---

## 🔗 Dependency Rules

* Task only runs if all dependencies **succeeded**
* Dependencies must exist in discovery file
* Cycles are not allowed

---

### Example Flow

```id="t6x4k2"
A → B → C
```

```php id="d5o9p1"
B depends_on A  
C depends_on B
```

---

### schedule

Optional cron schedule:

```php id="v1j7z2"
'schedule' => [
    'expression' => '* * * * *',
    'human' => 'Every minute',
]
```

---

### timeout_minutes

Optional per-task timeout:

```php id="y3r8f6"
'timeout_minutes' => 30
```

Used for stale run detection.

---

### queue

Optional target queue for this task run:

```php id="q7u2m9"
'queue' => 'imports'
```

---

### connection

Optional queue connection for this task run:

```php id="c3k9b1"
'connection' => 'database'
```

---

## 🚫 Common Mistakes

### Duplicate names

```php id="d9f2m3"
// ❌ wrong
'name' => 'import'
```

Names must be unique.

---

### Missing dependency

```php id="q8k4t7"
// ❌ dependency not defined
'depends_on' => ['unknown-task']
```

---

### Circular dependencies

```id="l2h7x1"
A → B → A ❌
```

---

## 💡 Best Practices

* Keep names short and unique
* Use clear labels for UI
* Group related tasks
* Keep dependency chains simple
* Use `timeout_minutes` for long-running tasks

---

## Next

→ Pipelines
