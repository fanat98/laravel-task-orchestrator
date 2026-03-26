# Authorization

The Task Orchestrator provides flexible access control for the dashboard.

---

## Overview

Two authorization modes are supported:

* `user_field` (simple)
* `gate` (advanced)

---

## 🔹 Option 1 – User Field (Recommended)

Uses a boolean field on your User model.

### Config

```php
'authorization' => [
    'mode' => 'user_field',
    'field' => 'is_admin',
],
```

---

### Example User Model

```php
class User extends Authenticatable
{
    public bool $is_admin;
}
```

---

### Behavior

* `true` → access granted
* `false` → access denied

---

## 🔹 Option 2 – Gate

Uses Laravel Gates for full control.

### Config

```php
'authorization' => [
    'mode' => 'gate',
    'ability' => 'viewTaskOrchestrator',
],
```

---

### Define Gate

```php
use Illuminate\Support\Facades\Gate;

Gate::define('viewTaskOrchestrator', function ($user) {
    return $user->is_admin;
});
```

---

## 🔒 Behavior

If access is denied:

* user receives **403 Forbidden**
* dashboard is not accessible

---

## 💡 Best Practices

* Use `user_field` for internal/admin tools
* Use `gate` if you need:

    * role-based access
    * multi-tenant logic
    * advanced permissions

---

## Next

→ Release & Versioning
