# Phase 1 Review — before starting Phase 2

## Fixed

1. **Login throttle didn't match its own comment.** The route used
   Laravel's default `throttle:5,1` (IP-only), while the controller
   comment claimed a `throttle:login` email+IP limiter that didn't
   actually exist. On a shared network — a whole school building behind
   one public IP — an IP-only limiter means one person mistyping their
   password repeatedly locks *everyone* in the building out of logging
   in. Added a real named limiter (`app/Providers/AppServiceProvider.php`,
   keyed by `email|ip`) and pointed the route at it.

2. **Forced logout left the session only half torn down.** Both
   `EnsureUserHasRole` (deactivating a user mid-session) and
   `LoginController::login` (login attempt against an inactive account)
   called `Auth::logout()` but never `session()->invalidate()` /
   `regenerateToken()`, unlike the real `logout()` action. Low severity —
   nothing was exploitable — but inconsistent: the session ID and any
   stale session data outlived the auth check that just failed. Both
   paths now match the real logout flow exactly.

## Flagged for Phase 2, not changed now

3. **`role` and `status` are mass-assignable on `User`.** Nothing in
   Phase 1 maps request input to these fields, so this isn't exploitable
   today — but Phase 2 is exactly "Student & Parent Management," which
   means admin-facing forms that create `User` rows. If a future
   controller ever does something like `User::create($request->validated())`
   with a stray `role` field on the form, that's a privilege-escalation
   bug. Rather than restructure `$fillable` now (which would also break
   the seeder/factories without a broader refactor), the requirement for
   Phase 2 is: **student/parent creation forms never expose a `role` or
   `status` field** — role is implied by which endpoint is used
   (`admin/students` always creates `role=student`), and `status`
   defaults to `active` unless an admin explicitly deactivates via a
   separate action, not the create form.

4. **No Policies yet.** Phase 1's authorization is coarse (route-level
   `role:` middleware) because there was nothing to own yet. Phase 2
   introduces per-record ownership (a parent editing *their* student, not
   any student) — recommend introducing Laravel Policies
   (`StudentPolicy`, `ParentPolicy`) at that point rather than ad hoc
   `if` checks scattered across controllers, so the "who can touch this
   record" logic lives in one place per model, the same way `role:`
   centralizes role checks now.

## Reviewed, no change needed

- Generic "credentials do not match" error message on both wrong-email
  and wrong-password cases — no user-enumeration leak.
- CSRF protection present on all state-changing forms (`@csrf` +
  Laravel's global `VerifyCsrfToken`).
- `parent_student` / `students.user_id` / `parents.user_id` foreign key
  behavior (cascade vs. null-on-delete) is correct for the intended
  semantics.
- Parent/student dashboards already derive visible data from the
  authenticated user's own relations (`$request->user()->student()`,
  `$parentProfile->students`), never from a route parameter — this is
  the pattern Phase 2's CRUD needs to keep for object-level
  authorization.

Updated files: `routes/web.php`, `app/Http/Controllers/Auth/LoginController.php`,
`app/Http/Middleware/EnsureUserHasRole.php`, plus new
`app/Providers/AppServiceProvider.php` (register this in
`bootstrap/providers.php` if merging into a fresh Laravel install — it's
included there by default in `laravel new`, so this file replaces the
generated one).
