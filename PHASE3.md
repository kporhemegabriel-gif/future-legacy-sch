# Phase 3 — Assessment & Results Management

Builds on Phase 1 + Phase 2 without rebuilding either. No GPA/CGPA/credit-hour
concept anywhere — see the "Basic School" note at the end.

## 1. Files created

**Migrations** (7, all additive):
`2024_03_01_create_terms_table`, `..._02_create_assessment_types_table`,
`..._03_create_grade_bands_table`, `..._04_create_assessments_table`,
`..._05_create_scores_table`, `..._06_create_score_audits_table`,
`..._07_create_term_results_table`

**Models:** `Term`, `AssessmentType`, `GradeBand`, `Assessment`, `Score`,
`ScoreAudit`, `TermResult`

**Service:** `app/Services/ResultCalculationService.php`

**Form Requests:** `Store/UpdateAssessmentRequest`, `UpdateScoresRequest`,
`Store/UpdateTermRequest`, `Store/UpdateAssessmentTypeRequest`,
`Store/UpdateGradeBandRequest`

**Policies:** `AssessmentPolicy`, `TermResultPolicy`, `TermPolicy`,
`AssessmentTypePolicy`, `GradeBandPolicy`

**Controllers:** `Admin/{TermController,AssessmentTypeController,
GradeBandController,AssessmentController,ScoreController,
TermResultController}`, `Student/ResultController`, `Parent/ResultController`

**Factories:** `TermFactory`, `AssessmentTypeFactory`, `GradeBandFactory`,
`AssessmentFactory`, `ScoreFactory`

**Views:** `admin/terms/*`, `admin/assessment-types/*`, `admin/grade-bands/*`,
`admin/assessments/*` (including the score-entry grid), `admin/term-results/*`,
`student/results/{index,show}`, `parent/children/results-{index,show}`, and
one shared partial `resources/views/results/_breakdown.blade.php` reused by
all three of the admin/student/parent result pages (one source of truth for
how a result renders).

**Tests:** `Admin/AssessmentManagementTest`, `Admin/ScoreManagementTest`,
`Admin/GradingConfigTest`, `TermResultTest` (34 tests total — see §8).

## 2. Files modified (additive only)

- `app/Models/Enrollment.php`, `Student.php`, `SchoolClass.php`,
  `Subject.php`, `AcademicYear.php` — new relation methods only
  (`scores()`, `assessments()`, `termResults()`, `terms()`), nothing removed
  or changed in existing methods.
- `app/Http/Controllers/Admin/EnrollmentController.php` — added a guard in
  `destroy()`: `scores.enrollment_id` is `restrictOnDelete`, so an
  enrollment with recorded scores can no longer be silently un-enrolled.
- `app/Providers/AppServiceProvider.php` — registered the 5 new Policies.
- `routes/web.php` — added Phase 3 routes (see §4); nothing existing changed.
- `resources/views/layouts/app.blade.php` — added nav links (Assessments,
  Results, Grading Setup for admin; My Results for student).
- `resources/views/student/profile.blade.php`,
  `resources/views/parent/children/show.blade.php` — replaced the
  "coming in a future phase" placeholder line with a real link.
- `database/seeders/DatabaseSeeder.php` — added sample terms, assessment
  types, a grading scale, one CA+Exam assessment pair, sample scores, and a
  computed (not published) term result for Grade 9A's First Term.

## 3. Database structure

```
terms              academic_year_id, name, sequence, is_current
assessment_types   name, status
grade_bands        min_score, max_score, grade, remark, status
assessments        assessment_type_id, academic_year_id, term_id, class_id,
                   subject_id, max_score, status, assessment_date
scores             assessment_id, enrollment_id, student_id, subject_id,
                   class_id, academic_year_id, term_id, score, recorded_by
                   — unique(assessment_id, student_id)
score_audits       score_id, student_id, assessment_id, changed_by,
                   old_score, new_score, changed_at   (append-only)
term_results       student_id, class_id, academic_year_id, term_id, status,
                   total_subjects, average_percentage, position,
                   published_at, published_by  — unique(student_id, term_id)
```

Per-subject breakdown is **not** stored anywhere — it's computed live from
`assessments`/`scores` every time by `ResultCalculationService`, so there is
exactly one source of truth for subject-level numbers. `term_results` only
persists what genuinely needs to survive between requests: publish status
and the term-level aggregates that require the whole class to be computed
together (average, position).

## 4. Routes added

```
# Admin (role:admin)
resource  admin/terms              (except show)
resource  admin/assessment-types   (except show)
resource  admin/grade-bands        (except show)
resource  admin/assessments
GET/PUT   admin/assessments/{assessment}/scores
GET       admin/term-results
POST      admin/term-results/compute
GET       admin/term-results/{termResult}
PATCH     admin/term-results/{termResult}/publish
PATCH     admin/term-results/{termResult}/unpublish

# Student (role:student)
GET       student/results
GET       student/results/{termResult}

# Parent (role:parent)
GET       parent/children/{student}/results
GET       parent/children/{student}/results/{termResult}
```

## 5. Authorization rules

| Action | Admin | Student | Parent |
|---|---|---|---|
| Manage terms/types/grading scale | ✅ | ❌ | ❌ |
| Create/edit/delete assessments | ✅ | ❌ | ❌ |
| Enter/update scores | ✅ | ❌ | ❌ |
| Compute/publish/unpublish results | ✅ | ❌ | ❌ |
| View a term result | ✅ (any, incl. draft) | ✅ own only, **published only** | ✅ linked child only, **published only** |

`TermResultPolicy::view()` checks `isPublished()` **before** ownership even
matters for anyone who isn't an admin — an unpublished result is invisible
to its own student, full stop, regardless of URL. This is enforced in the
Policy (server-side), not by hiding a button in Blade.

## 6. Result calculation logic (no GPA anywhere)

For one student, one subject, one term:
1. Find every **active** `Assessment` for that subject/class/term/year.
2. `total_max_score` = sum of those assessments' `max_score`.
3. `total_score` = sum of the student's `Score` rows for those assessments
   — an assessment the student wasn't scored on counts as **0**, not as
   excluded (the normal school convention for a missed test).
4. `percentage` = `total_score / total_max_score × 100`.
5. `grade`/`remark` = whichever active `GradeBand` row's `[min_score,
   max_score]` contains the percentage (admin-configured, never
   hard-coded — see the seeded 80/70/60/50/0 example, which is just an
   example, not a built-in default).

A school's "CA=40 + Exam=60" split is achieved by creating two assessments
with those max scores — nothing in the code assumes that specific split;
a school with CA+Test+Exam=30/20/50 gets correct results with zero code
changes.

For a whole class+term (`computeForClassTerm`): each student's subject
percentages are averaged into `average_percentage`, then all students in
the class are ranked by that average using **competition ranking**
(ties share a position — e.g. 1, 2, 2, 4). Recomputing updates the numbers
but **never** changes an existing result's publish status, so correcting a
score after publishing updates what the parent sees rather than silently
un-publishing it.

## 7. Score-entry safety (server-side, not just Blade)

- `UpdateScoresRequest` validates every row against the assessment's own
  `max_score` (`max:` rule built from `$assessment->max_score`, not a
  fixed number) and re-checks a live `Enrollment` match
  (student+subject+class+year, status=`enrolled`) per row — a forged
  `student_id` for an unenrolled student is rejected with no score written.
- `ScoreController::update()` re-checks the same enrollment condition
  again inside the DB transaction (belt and suspenders on the one write
  path that touches academic records), and writes a `ScoreAudit` row
  (old value, new value, who, when) on every create/update — skipped only
  when a resubmission changes nothing, to avoid padding history with no-op
  entries.
- `scores` has a DB-level `unique(assessment_id, student_id)` as the
  ultimate backstop behind the update-or-create logic in the controller.

## 8. Tests — 34 total, all statically traced, **none executed**

Same constraint as Phase 2's verification round: **this sandbox has no PHP
runtime and no network access** (`apt-get install php-cli` still returns
403 from the egress proxy, reconfirmed before writing this report). Every
test below was traced by hand against the actual FormRequest/Policy/
Controller/Service code — request → validation → authorization → DB
assertion — not executed. Please run `php artisan test` locally before
treating any of this as verified.

- `AssessmentManagementTest` (9): create with valid class-subject match,
  reject subject-not-on-class, reject term/class year mismatch, reject
  zero/negative max_score, lock class/subject/term once scored, block
  lowering max_score below a recorded score, block deleting a scored
  assessment, non-admin blocked.
- `ScoreManagementTest` (12): valid score, score-equals-max, score-above-max
  rejected, negative score rejected, resubmission updates not duplicates,
  raw DB unique constraint as backstop, audit entry on create, audit entry
  with old value on update, score rejected for a non-enrolled student,
  rejected for a student enrolled in a *different* class, rejected once an
  enrollment is dropped, non-admin blocked.
- `GradingConfigTest` (11): term CRUD, single-current-term enforcement,
  duplicate term name per year rejected / allowed across years, term with
  assessments can't be deleted, assessment-type CRUD + duplicate rejected,
  grade-band CRUD, overlap rejected, non-overlap accepted, min>max
  rejected, non-admin blocked from all three config areas.
- `TermResultTest` (12): subject total = sum of scores vs. sum of
  max-scores, a missed assessment counts as 0, grade/remark from the
  configured scale, no grade when no band matches, class-wide ranking with
  ties, recompute preserves publish status but updates numbers, unpublished
  hidden from both student and parent, published visible to the correct
  student and parent, student blocked from another student's published
  result, parent blocked from a non-linked child's published result, admin
  compute+publish flow, non-admin blocked from compute/publish.

Plus every existing Phase 1 (`AuthTest`, `RoleAccessTest`) and Phase 2 test
should remain unaffected — nothing in Phase 3 touches auth, role
middleware, or any Phase 1/2 controller logic beyond the one additive
`EnrollmentController::destroy()` guard noted above.

## 9. Manual testing checklist

- [ ] Create a Term, mark it current → create a second Term and mark it
      current → confirm the first is no longer current
- [ ] Create 2-3 Assessment Types (e.g. Continuous Assessment, Examination)
- [ ] Configure a grading scale (e.g. the 80/70/60/50/0 example) → try
      adding an overlapping range → confirm a friendly rejection
- [ ] Create a CA assessment (max 40) and an Exam assessment (max 60) for
      the same class+subject+term
- [ ] Enter scores for enrolled students → try entering a score above the
      max → confirm rejection with no partial save
- [ ] Try editing that assessment's class or subject after scoring →
      confirm it's locked
- [ ] Go to Term Results → select the class+term → Compute → confirm
      averages, grades, and position/ranking look right, including a tie
      if you set one up
- [ ] Publish one student's result → log in as that student → confirm it's
      visible; log in as a *different* student → confirm the same
      published result is not reachable by URL
- [ ] Log in as the linked parent → confirm the same visibility rules hold
- [ ] Unpublish the result → confirm the student/parent can no longer see it
- [ ] As a non-admin, try hitting any `/admin/assessments`, `/admin/scores`,
      or `/admin/term-results` URL directly → confirm 403

## 10. Architectural notes / concerns for you to weigh

- **Position/ranking has no on/off toggle.** It's always computed when a
  class+term is computed, and always shown if present. If some school
  years/classes shouldn't be ranked at all, that needs a real setting —
  I didn't build one since the spec said "if enabled" without specifying
  where that toggle should live, and guessing felt worse than surfacing it.
- **No subject overrides for assessments**, mirroring the enrollment
  decision from Phase 2 — an assessment's subject must be on the class's
  assigned list, full stop.
- **Grading scale is a single global list**, not scoped per class/subject/
  year. If Basic 1-3 and Basic 4-6 should someday use different scales,
  that's a real design change, not a config tweak — flagging now rather
  than guessing.
- **`ScoreAudit` is a genuinely new architectural piece** (§13 of your
  spec asked me to flag this if it affects the existing architecture) —
  but it's purely additive: one new append-only table, written from
  exactly one place (`ScoreController::update()`), read from nowhere yet.
  No existing table or relationship was changed to support it.

## Post-approval corrections — configurable ranking + academic-year grading scale

Two architecture adjustments requested after Phase 3 review, before Phase 4.
Both are additive to the Phase 3 build above — nothing rebuilt.

### 1. Position/ranking is now configurable, not permanent

**New:** `settings` table (generic key/value — chosen over a one-off boolean
column so future toggles don't each need their own migration), `Setting`
model with typed `rankingEnabled()`/`setRankingEnabled()` accessors,
`SettingPolicy` (admin-only), `Admin\SettingController` (`GET/PUT
admin/settings`), one view, nav links.

**Default:** enabled — preserves Phase 3's original always-on behavior
until an admin explicitly turns it off. Not stated in the brief, so this
is a judgment call, called out here rather than left implicit.

**Changed:**
- `ResultCalculationService::computeForClassTerm()` — checks
  `Setting::rankingEnabled()` before doing any ranking work at all; when
  disabled, `assignPositions()` is never called (nothing calculated
  unnecessarily) and every computed `TermResult.position` is explicitly
  set to `null` (so a previously-enabled-then-disabled setting can't leave
  a stale rank sitting in the database either).
- `resources/views/results/_breakdown.blade.php`,
  `student/results/index.blade.php`,
  `parent/children/results-index.blade.php` — all check the *live*
  `Setting::rankingEnabled()` value at render time, not just whether
  `position` happens to be non-null. This matters: if ranking is disabled
  without a recompute, a stale stored position must still not display —
  and it doesn't, because display never trusts the stored value alone.

Admin's own `term-results/index.blade.php` list still shows the `position`
column as-is; when disabled it naturally reads "—" for everyone once
positions are nulled out, so no extra gating was needed there.

### 2. Grading scale is now scoped to Academic Year

**Structure:** Academic Year → Grading Scale → Grade Ranges, exactly as
specified. Not made class-specific — kept at the year level per your
explicit instruction.

**Migration:** the `grade_bands` migration (`2024_03_03_...`) was **edited
in place** to add `academic_year_id`, rather than adding a second additive
migration on top of it. Same reasoning as the earlier `credit_unit`
removal: this table has never actually been migrated against a real
database in this project (no PHP runtime has been available at any point),
so there is no live schema to preserve — editing the original migration
avoids a create-then-immediately-alter pair for a feature that was never
deployed. If this table *had* already been migrated somewhere real, the
correct move would have been a proper additive `ALTER TABLE` migration
instead; flagging that distinction explicitly since it's a real judgment
call, not a default I should make silently in general.

**Changed:** `GradeBand` model (+`academic_year_id`, +`academicYear()`),
`AcademicYear` model (+`gradeBands()`), both grade-band Form Requests
(overlap check now scoped `where('academic_year_id', ...)` — bands in
different years may freely overlap, since they're independent scales),
`GradeBandController` (year filter + selector), `GradeBandFactory`,
`DatabaseSeeder`, and the three grade-band views.

**The correctness-critical change:**
`ResultCalculationService::gradeBandFor()` now requires an
`$academicYearId` argument, and every call site passes the *class's own*
`academic_year_id` — never "whichever scale is currently active". A
computed result for 2024/2025 will always grade against 2024/2025's bands,
even after an admin changes 2026/2027's scale. This is proven directly by
`test_changing_a_future_years_grading_scale_does_not_affect_a_past_years_computed_grade`.

## Tests added/updated for these corrections — 15 total, all statically traced, none executed

Same constraint as every prior round, reconfirmed immediately before
writing this update: **no PHP runtime, no network access, in this
sandbox.** Every test below was traced by hand against the actual code;
none were run. Please execute `php artisan test` locally.

- `GradingConfigTest.php` — rewritten: grade-band tests now include
  `academic_year_id` (6 tests: create, overlap-rejected-same-year,
  same-range-allowed-different-year, non-overlap-accepted, min>max
  rejected, **the historical-grading proof above**), plus the
  non-admin-blocked test now also covers `/admin/settings`.
- `TermResultTest.php` — 2 existing tests fixed (grade bands now created
  *after* the class/year exist, tied to that year's `academic_year_id` —
  they were previously created before the year was even known, which
  would have broken once `academic_year_id` became required).
- `RankingSettingTest.php` (new, 9 tests): default-enabled, admin can
  disable/re-enable, non-admin blocked, positions computed when enabled,
  positions NOT computed when disabled, disabling-then-recomputing clears
  a previously-stored position, position hidden from a student's published
  result when disabled (even with a stale non-null stored value), position
  shown when enabled, position hidden from the parent view when disabled.

Every other Phase 1/2/3 test should remain unaffected except the two
`TermResultTest` fixes noted above, which were required by the schema
change, not incidental.
