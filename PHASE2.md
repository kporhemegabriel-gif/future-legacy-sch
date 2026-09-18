# Phase 2 — Student & Parent Management + Academic Structure

Builds directly on the Phase 1 Laravel codebase. No existing file's
behavior was changed except the eight listed below, and every schema
change is an *additive* migration — nothing in Phase 1's own migrations
was edited.

## 1. Files created / changed

**Created**
- Controllers: `Admin/{StudentController,ParentController,SchoolClassController,SubjectController,EnrollmentController}.php`, `Student/ProfileController.php`, `Parent/ChildController.php`
- Form Requests: `Admin/{Store,Update}StudentRequest`, `{Store,Update}ParentRequest`, `{Store,Update}SchoolClassRequest`, `{Store,Update}SubjectRequest`, `AssignClassSubjectsRequest`, `StoreEnrollmentRequest`
- Models: `Subject.php`, `Enrollment.php`
- Policies: `StudentPolicy`, `ParentPolicy`, `SchoolClassPolicy`, `SubjectPolicy`, `EnrollmentPolicy`
- Migrations: see §2
- Factories: `AcademicYearFactory`, `SchoolClassFactory`, `SubjectFactory`, `EnrollmentFactory`
- Views: full `resources/views/admin/{students,parents,classes,subjects}/*`, `resources/views/parent/children/{index,show}.blade.php`, `resources/views/student/profile.blade.php`
- Tests: `tests/Feature/Admin/{StudentManagementTest,ParentManagementTest,ClassAndSubjectManagementTest,EnrollmentTest}.php`

**Changed** (additive edits only — see inline comments in each file for why)
- `app/Models/Student.php` — `status` fillable/relations (`enrollments`, `subjects`), `guardians()` pivot gained `is_primary`
- `app/Models/SchoolClass.php` — `section`/`status` fillable, `subjects()` + `enrollments()` relations, `displayName()`
- `app/Models/ParentGuardian.php` — `students()` pivot gained `is_primary`, added `syncStudent()`
- `app/Models/AcademicYear.php` — added `HasFactory` (needed for the new factory)
- `app/Providers/AppServiceProvider.php` — registered the 5 new Policies via `Gate::policy()`
- `database/seeders/DatabaseSeeder.php` — seeds sample subjects, class-subject assignments, and student enrollments; one parent now demonstrates multi-child linking
- `routes/web.php` — added all Phase 2 routes (existing route names/paths for dashboards untouched)
- `resources/views/layouts/app.blade.php` — added reusable button/form/badge/alert CSS and a role-aware subnav; Phase 1's look (navy/gold/cream, no framework) is unchanged

## 2. Database migrations

| Migration | What it does |
|---|---|
| `2024_02_01_..._add_status_to_students_table` | Adds `status` enum (`active/inactive/graduated/withdrawn`) to `students`, indexed |
| `2024_02_02_..._add_section_and_status_to_school_classes_table` | Adds `section` (nullable) and `status` enum to `school_classes` |
| `2024_02_03_..._add_is_primary_to_parent_student_table` | Adds `is_primary` boolean to the `parent_student` pivot |
| `2024_02_04_..._create_subjects_table` | `subjects`: code (unique), name, status |
| `2024_02_05_..._create_class_subjects_table` | Pivot: class ↔ subject ↔ academic year, unique on the triple |
| `2024_02_06_..._create_enrollments_table` | `enrollments`: student, subject, class, academic year, status; unique on (student, subject, year) |

All new foreign keys use `cascadeOnDelete` or `restrictOnDelete` as appropriate (see inline comments in each migration for the reasoning). Note: Phase 1's own `students.class_id` FK is `nullOnDelete`, not `restrict` — Phase 2 respects that and instead guards class deletion at the application layer (see `SchoolClassController::destroy()`).

## 3. Routes (all under existing `role:` middleware groups)

```
# Admin (role:admin)
resource  admin/students            (+ PATCH  students/{student}/toggle-account)
                                     (+ POST   students/{student}/enrollments)
                                     (+ DELETE students/{student}/enrollments/{enrollment})
resource  admin/parents             (+ PATCH  parents/{parent}/toggle-account)
resource  admin/classes             (+ GET/PUT classes/{class}/subjects)
resource  admin/subjects

# Student (role:student)
GET  student/profile

# Parent (role:parent)
GET  parent/children
GET  parent/children/{student}
```

## 4. Models and relationships

- `Student` — `belongsTo` User/SchoolClass/AcademicYear; `belongsToMany` ParentGuardian (via `parent_student`, pivot: relationship, is_primary); `hasMany` Enrollment; `belongsToMany` Subject (via `enrollments`)
- `ParentGuardian` — `belongsTo` User; `belongsToMany` Student (same pivot); `syncStudent()` enforces one primary guardian per student
- `SchoolClass` — `belongsTo` AcademicYear; `hasMany` Student, Enrollment; `belongsToMany` Subject (via `class_subjects`)
- `Subject` — `belongsToMany` SchoolClass, Student (via `enrollments`); `hasMany` Enrollment
- `Enrollment` — `belongsTo` Student, Subject, SchoolClass, AcademicYear

## 5. Authorization rules

| Action | Admin | Student | Parent |
|---|---|---|---|
| List/create/edit/delete students, parents, classes, subjects | ✅ | ❌ | ❌ |
| View a student record | ✅ (any) | ✅ (own only) | ✅ (own linked children only) |
| View a parent record | ✅ (any) | — | ✅ (own only) |
| View subject catalog | ✅ | ✅ | ✅ |
| Enroll/unenroll a student in a subject | ✅ | ❌ | ❌ |

Every "own only" check is re-derived server-side from the authenticated user's own relation (`$user->student`, `$user->parentProfile->studentIds()`) — never trusted from a route parameter. Account status (login access) and enrollment status (active/inactive/graduated/withdrawn) are two separate fields with two separate controls; neither a create nor edit form can set the User's `role` or account `status` directly.

## 6. Tests

`tests/Feature/Admin/`:
- `StudentManagementTest` — create/update, role/status injection is blocked, duplicate admission number rejected, account-status toggle doesn't touch enrollment status, student/parent visibility scoping
- `ParentManagementTest` — multi-child linking, one-primary-guardian-per-student enforcement, parent child-selection scoping
- `ClassAndSubjectManagementTest` — class/subject CRUD, duplicate subject code rejected, class deletion blocked while students are assigned, subject assignment to a class
- `EnrollmentTest` — enroll, duplicate-enrollment rejection, removal, non-admin blocked

Plus all of Phase 1's `AuthTest` and `RoleAccessTest` should still pass unmodified.

**Not run in this session** — this sandbox has no PHP runtime, so the suite was reviewed line-by-line but not executed. Run it locally per §7 before treating Phase 2 as verified.

## 7. Setup / migration commands

```bash
composer install
cp .env.example .env   # if not already done in Phase 1
php artisan key:generate
php artisan migrate            # applies the 6 new Phase 2 migrations on top of Phase 1's
php artisan db:seed            # optional — refreshes sample data with Phase 2 subjects/enrollments
php artisan test                # run the full suite, Phase 1 + Phase 2
```

If your Phase 1 database already has data you want to keep, `php artisan migrate` (without `:fresh`) is enough — all six Phase 2 migrations are additive.

## 8. Manual testing checklist

- [ ] Log in as admin → Students → create a student → confirm login works with the new account and role is "student"
- [ ] Edit that student, change enrollment status to "graduated" → confirm the account can still log in (account status is separate)
- [ ] Toggle the student's account status to inactive → confirm they can no longer log in
- [ ] Create a parent, link two different students in one form submission → confirm both show under "Linked children"
- [ ] Mark a second guardian "primary" for a student who already has a primary guardian → confirm the first guardian's primary flag is cleared
- [ ] Log in as that parent → "My Children" → click into each child → confirm only linked children are visible, and visiting another student's URL directly is forbidden
- [ ] Log in as the student → "My Profile" → confirm only their own data appears
- [ ] Create a class, assign 2–3 subjects to it via "Manage subjects"
- [ ] From a student's profile, enroll them in a subject/class/year combination → try the same combination again → confirm a friendly duplicate error, not a crash
- [ ] Try deleting a class that still has students assigned → confirm a friendly error, not a crash or silent orphaning
- [ ] Try deleting a subject with enrollment history → confirm a friendly error
- [ ] As a parent or student account, try hitting any `/admin/...` URL directly → confirm 403

## Post-delivery correction — Basic School terminology

The original spec document supplied for this project is written throughout
in university-style language (GPA, CGPA, credit units — 25+ mentions,
including a dedicated "GPA / Results Business Logic" section). Phase 2's
`subjects` table originally included a `credit_unit` column, taken directly
from that spec's schema section.

Per explicit correction: this is a **basic school**, not a tertiary
institution. `credit_unit` has been removed from the `subjects` migration,
model, form requests, factory, seeder, views, and tests — it was never read
anywhere outside the subject-management screens, so removal has no
downstream impact. A handful of Phase 1/Phase 2 placeholder comments and
UI copy that said "GPA" (student dashboard, student profile, parent child
view) were reworded to "grades" / "assessment results".

Phase 3 planning must **not** reference the old spec document's GPA
section — it should instead follow the term-based, configurable-grading
model (Academic Year → Term → Class → Subject → Continuous Assessment +
Examination → Total Score → configurable Grade/Remark) as directed.

## Post-delivery correction — class-subject enrollment rule

**Rule enforced:** a student may only be enrolled in a subject assigned to
their *current* class (`Class → subjects assigned to the class → students
in the class → enrollments`).

**No migration added** — `class_subjects` and `enrollments` already had
every column and FK needed; this was purely a validation/business-logic
gap, not a schema gap.

**Files changed:**
- `app/Http/Requests/Admin/StoreEnrollmentRequest.php` — `class_id` and
  `academic_year_id` are no longer form inputs at all (removed, not just
  hidden). `subject_id` now validates against
  `$student->schoolClass->subjects()` via a closure rule, with `bail` so a
  student-has-no-class failure doesn't also show a confusing second
  message. The duplicate check is scoped to the class's own academic year.
- `app/Http/Controllers/Admin/EnrollmentController.php` — `class_id` and
  `academic_year_id` are derived server-side from `$student->class_id` /
  `$student->schoolClass->academic_year_id`, never read from the request.
  This is what makes the rule unbypassable: there's no field for a
  manually crafted request to override in the first place.
- `app/Http/Controllers/Admin/StudentController.php` (`show()`) — now
  passes `enrollableSubjects` (subjects assigned to the student's class
  only) instead of the full active-subjects/classes/years lists.
- `resources/views/admin/students/show.blade.php` — enroll form reduced
  from three dropdowns (subject/class/year) to one (subject only, filtered
  to the class's subjects), with explicit empty states for "no class
  assigned" and "class has no subjects assigned yet".
- `tests/Feature/Admin/EnrollmentTest.php` — rewritten with 12 tests
  covering: valid enrollment, invalid enrollment (subject not on class,
  student has no class), a direct bypass-attempt test (spoofed
  `class_id`/`academic_year_id` in the POST body), duplicate enrollment,
  class-change behavior (old enrollments untouched, new enrollments
  validated against the new class), removal, and three
  authorization/cross-role tests (student, parent, and a parent enrolling
  their *own* linked child — all correctly blocked, since enrollment
  management is admin-only regardless of relationship to the student).

**Design decision — no subject overrides (as instructed):** the rule is
strict: subject must be on the class's list, full stop. No path exists
(yet) for enrolling a student in a subject outside their class's
assignment. If a real school need for exceptions surfaces, that should be
a deliberate, discussed design addition — not introduced speculatively.

**Design decision — class changes don't touch existing enrollments:**
`enrollments.class_id`/`academic_year_id` are captured at enrollment time
and stay fixed after a student moves classes — they're a historical
record of what was actually studied, not a live pointer. New enrollment
attempts are always validated against the student's *current* class.
