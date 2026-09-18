# Future Legacy School — SMS (Phase 1: Foundation)

Phase 1 delivers: project skeleton, MySQL connection, migrations, seeders,
session-based authentication, role-based authorization (admin / student /
parent), and one working dashboard per role. No academics, attendance,
fees, or Firebase writes yet — those are later phases per the README.

## 1. Scaffold the base Laravel app

This bundle contains only the Phase‑1‑specific files (they were written by
hand in a sandbox with no network access, so `composer install` couldn't be
run here). Create the base install first, then drop these files in:

```bash
composer create-project laravel/laravel sms-app "^11.0"
cd sms-app
```

Copy every file from this bundle into `sms-app/`, preserving paths
(`app/`, `database/`, `routes/web.php`, `resources/views/`, `config/`,
`bootstrap/app.php`, `tests/`). Merge `composer.json`'s `require` /
`require-dev` blocks into the one Laravel generated (don't overwrite it —
the generated one has more packages than shown here).

## 2. Install dependencies

```bash
composer install
composer require kreait/firebase-php dompdf/dompdf
```

## 3. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and set `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` to match a
local MySQL instance. Leave the `FIREBASE_*` values as placeholders — they
aren't read anywhere yet.

Create the database itself:

```sql
CREATE DATABASE school_management CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## 4. Migrate and seed

```bash
php artisan migrate
php artisan db:seed
```

## 5. Run it

```bash
php artisan serve
```

Visit `http://localhost:8000`. Log in with any seeded account (all seeded
passwords are `ChangeMe123!` — change these before using real data):

| Role    | Email                                          |
|---------|-------------------------------------------------|
| Admin   | admin@futurelegacyschool.edu                    |
| Student | ama.owusu@students.futurelegacyschool.edu       |
| Parent  | yaw.owusu@example.com                           |

Each role lands on its own dashboard and is blocked (`403`) from the other
two — try visiting `/admin/dashboard` while logged in as a student to see
the RBAC middleware in action.

## 6. Run the tests

```bash
php artisan test
```

`AuthTest` covers login/logout/inactive-account handling. `RoleAccessTest`
covers cross-role access denial and confirms a parent only ever sees their
own linked children, never another family's data.

## Architectural decisions made in this phase

- **`parents` / `ParentGuardian`, not `Parent`** — avoids ambiguity with
  Eloquent's own relationship vocabulary.
- **`school_classes`, not `classes`** — sidesteps any collision with PHP's
  `class` keyword in tooling that scans table names.
- **Role lives on `users.role`**, not a separate roles/permissions table.
  Three fixed roles don't yet justify a many-to-many permissions system;
  the README's future-roles list (teacher, accountant, ...) can be added
  as new enum values first, with a real permissions table introduced only
  if per-user overrides are actually needed later.
- **No Breeze/Jetstream** — auth is ~60 lines of hand-rolled controller so
  every line is visible and auditable, matching the README's security
  emphasis.
- **Firebase dependency is installed but unused** — `config/firebase.php`
  exists so Phase 7 has a reviewed home to write into, without letting any
  Phase 1 code accidentally depend on Firebase being reachable.

## Not yet built (by design — later phases)

Attendance, assessments/results, report-card PDFs, fees/payments,
announcements, notifications, audit logs, and all Firebase real-time
events. Say the word when you want Phase 2 (Student & Parent management
CRUD).
