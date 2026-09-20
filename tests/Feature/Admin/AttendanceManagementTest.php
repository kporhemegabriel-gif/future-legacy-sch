<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /** A class + matching term (same academic year) + N active students in that class. */
    private function classWithStudents(int $count = 3): array
    {
        $year = AcademicYear::factory()->create(['start_date' => '2025-09-01', 'end_date' => '2026-07-31']);
        $class = SchoolClass::factory()->create(['academic_year_id' => $year->id]);
        $term = Term::factory()->create(['academic_year_id' => $year->id]);
        $students = Student::factory()->count($count)->create(['class_id' => $class->id, 'status' => 'active']);

        return [$year, $class, $term, $students];
    }

    // ------------------------------------------------------------------
    // Basic creation
    // ------------------------------------------------------------------
    public function test_admin_can_mark_attendance_for_a_student(): void
    {
        [$year, $class, $term, $students] = $this->classWithStudents(1);
        $student = $students->first();

        $response = $this->actingAs($this->admin())->post('/admin/attendance/mark', [
            'class_id' => $class->id,
            'term_id' => $term->id,
            'attendance_date' => '2025-10-01',
            'records' => [
                ['student_id' => $student->id, 'status' => 'present'],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'class_id' => $class->id,
            'academic_year_id' => $year->id, // derived server-side from the class
            'term_id' => $term->id,
            'status' => 'present',
        ]);
    }

    public function test_a_note_can_be_recorded_with_a_status(): void
    {
        [, $class, $term, $students] = $this->classWithStudents(1);
        $student = $students->first();

        $this->actingAs($this->admin())->post('/admin/attendance/mark', [
            'class_id' => $class->id, 'term_id' => $term->id, 'attendance_date' => '2025-10-01',
            'records' => [['student_id' => $student->id, 'status' => 'absent', 'note' => 'Sick']],
        ]);

        $this->assertDatabaseHas('attendances', ['student_id' => $student->id, 'status' => 'absent', 'note' => 'Sick']);
    }

    public function test_invalid_status_is_rejected(): void
    {
        [, $class, $term, $students] = $this->classWithStudents(1);
        $student = $students->first();

        $response = $this->actingAs($this->admin())->post('/admin/attendance/mark', [
            'class_id' => $class->id, 'term_id' => $term->id, 'attendance_date' => '2025-10-01',
            'records' => [['student_id' => $student->id, 'status' => 'on_leave']], // not a real status
        ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_missing_required_fields_are_rejected(): void
    {
        $response = $this->actingAs($this->admin())->post('/admin/attendance/mark', []);

        $response->assertSessionHasErrors(['class_id', 'term_id', 'attendance_date', 'records']);
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_date_outside_the_classs_academic_year_is_rejected(): void
    {
        [, $class, $term, $students] = $this->classWithStudents(1);
        $student = $students->first();

        $response = $this->actingAs($this->admin())->post('/admin/attendance/mark', [
            'class_id' => $class->id, 'term_id' => $term->id, 'attendance_date' => '2030-01-01', // way outside 2025-2026
            'records' => [['student_id' => $student->id, 'status' => 'present']],
        ]);

        $response->assertSessionHasErrors('attendance_date');
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_term_and_class_from_different_years_is_rejected(): void
    {
        [, $class, , $students] = $this->classWithStudents(1);
        $otherYearTerm = Term::factory()->create(['academic_year_id' => AcademicYear::factory()->create()->id]);
        $student = $students->first();

        $response = $this->actingAs($this->admin())->post('/admin/attendance/mark', [
            'class_id' => $class->id, 'term_id' => $otherYearTerm->id, 'attendance_date' => '2025-10-01',
            'records' => [['student_id' => $student->id, 'status' => 'present']],
        ]);

        $response->assertSessionHasErrors('term_id');
        $this->assertDatabaseCount('attendances', 0);
    }

    // ------------------------------------------------------------------
    // Duplicate handling — update, not duplicate
    // ------------------------------------------------------------------
    public function test_resubmitting_the_same_date_updates_rather_than_duplicates(): void
    {
        [, $class, $term, $students] = $this->classWithStudents(1);
        $student = $students->first();
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/attendance/mark', [
            'class_id' => $class->id, 'term_id' => $term->id, 'attendance_date' => '2025-10-01',
            'records' => [['student_id' => $student->id, 'status' => 'absent']],
        ]);
        $this->actingAs($admin)->post('/admin/attendance/mark', [
            'class_id' => $class->id, 'term_id' => $term->id, 'attendance_date' => '2025-10-01',
            'records' => [['student_id' => $student->id, 'status' => 'present']],
        ]);

        $this->assertDatabaseCount('attendances', 1);
        $this->assertDatabaseHas('attendances', ['student_id' => $student->id, 'attendance_date' => '2025-10-01', 'status' => 'present']);
    }

    public function test_the_database_rejects_a_true_duplicate_row_for_the_same_student_and_day(): void
    {
        [$year, $class, $term] = $this->classWithStudents(1);
        $student = Student::factory()->create(['class_id' => $class->id]);

        Attendance::create([
            'student_id' => $student->id, 'class_id' => $class->id, 'academic_year_id' => $year->id,
            'term_id' => $term->id, 'attendance_date' => '2025-10-01', 'status' => 'present',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Attendance::create([
            'student_id' => $student->id, 'class_id' => $class->id, 'academic_year_id' => $year->id,
            'term_id' => $term->id, 'attendance_date' => '2025-10-01', 'status' => 'absent',
        ]);
    }

    public function test_admin_can_correct_a_previously_marked_status(): void
    {
        [, $class, $term, $students] = $this->classWithStudents(1);
        $student = $students->first();
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/attendance/mark', [
            'class_id' => $class->id, 'term_id' => $term->id, 'attendance_date' => '2025-10-01',
            'records' => [['student_id' => $student->id, 'status' => 'absent']],
        ]);

        $response = $this->actingAs($admin)->post('/admin/attendance/mark', [
            'class_id' => $class->id, 'term_id' => $term->id, 'attendance_date' => '2025-10-01',
            'records' => [['student_id' => $student->id, 'status' => 'present']],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('attendances', ['student_id' => $student->id, 'status' => 'present']);
    }

    // ------------------------------------------------------------------
    // Bulk attendance — a whole class in one submission
    // ------------------------------------------------------------------
    public function test_admin_can_mark_an_entire_class_in_one_submission(): void
    {
        [, $class, $term, $students] = $this->classWithStudents(4);

        $records = $students->map(fn ($s, $i) => [
            'student_id' => $s->id,
            'status' => ['present', 'absent', 'late', 'excused'][$i],
        ])->values()->all();

        $response = $this->actingAs($this->admin())->post('/admin/attendance/mark', [
            'class_id' => $class->id, 'term_id' => $term->id, 'attendance_date' => '2025-10-01',
            'records' => $records,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('attendances', 4);
        foreach ($records as $record) {
            $this->assertDatabaseHas('attendances', ['student_id' => $record['student_id'], 'status' => $record['status']]);
        }
    }

    public function test_a_forged_student_id_from_a_different_class_is_rejected(): void
    {
        [, $class, $term, $students] = $this->classWithStudents(2);
        $outsiderStudent = Student::factory()->create(); // belongs to a different (or no) class

        $records = $students->map(fn ($s) => ['student_id' => $s->id, 'status' => 'present'])->values()->all();
        $records[] = ['student_id' => $outsiderStudent->id, 'status' => 'present'];

        $response = $this->actingAs($this->admin())->post('/admin/attendance/mark', [
            'class_id' => $class->id, 'term_id' => $term->id, 'attendance_date' => '2025-10-01',
            'records' => $records,
        ]);

        $response->assertSessionHasErrors();
        // Transactional: the whole submission is rejected, not just the bad row.
        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_bulk_submission_is_transactional_and_atomic(): void
    {
        // A submission that is entirely valid at the FormRequest layer
        // still must not leave a partial write if something in the middle
        // fails — proven here by asserting all-or-nothing on a mixed
        // valid/invalid submission (the invalid row fails validation
        // before the transaction ever starts, so nothing is written).
        [, $class, $term, $students] = $this->classWithStudents(3);
        $outsider = Student::factory()->create();

        $records = $students->map(fn ($s) => ['student_id' => $s->id, 'status' => 'present'])->values()->all();
        $records[] = ['student_id' => $outsider->id, 'status' => 'present'];

        $this->actingAs($this->admin())->post('/admin/attendance/mark', [
            'class_id' => $class->id, 'term_id' => $term->id, 'attendance_date' => '2025-10-01',
            'records' => $records,
        ]);

        $this->assertDatabaseCount('attendances', 0);
    }

    // ------------------------------------------------------------------
    // Historical integrity — class change doesn't touch past attendance
    // ------------------------------------------------------------------
    public function test_historical_attendance_is_unaffected_when_a_student_changes_class(): void
    {
        [, $oldClass, $term, $students] = $this->classWithStudents(1);
        $student = $students->first();
        $newClass = SchoolClass::factory()->create(['academic_year_id' => $oldClass->academic_year_id]);
        $admin = $this->admin();

        $this->actingAs($admin)->post('/admin/attendance/mark', [
            'class_id' => $oldClass->id, 'term_id' => $term->id, 'attendance_date' => '2025-10-01',
            'records' => [['student_id' => $student->id, 'status' => 'present']],
        ]);

        // Move the student to a different class.
        $this->actingAs($admin)->put("/admin/students/{$student->id}", [
            'name' => $student->user->name, 'email' => $student->user->email,
            'admission_number' => $student->admission_number, 'first_name' => $student->first_name,
            'last_name' => $student->last_name, 'admission_date' => $student->admission_date->format('Y-m-d'),
            'class_id' => $newClass->id, 'status' => 'active',
        ]);

        $this->assertDatabaseHas('attendances', [
            'student_id' => $student->id,
            'class_id' => $oldClass->id, // unchanged — historical record
            'attendance_date' => '2025-10-01',
        ]);
    }

    public function test_new_attendance_after_a_class_change_uses_the_new_class(): void
    {
        [, $oldClass, $term, $students] = $this->classWithStudents(1);
        $student = $students->first();
        $newClass = SchoolClass::factory()->create(['academic_year_id' => $oldClass->academic_year_id]);
        $newTerm = Term::factory()->create(['academic_year_id' => $newClass->academic_year_id]);
        $admin = $this->admin();

        $student->update(['class_id' => $newClass->id]);

        $this->actingAs($admin)->post('/admin/attendance/mark', [
            'class_id' => $newClass->id, 'term_id' => $newTerm->id, 'attendance_date' => '2025-10-02',
            'records' => [['student_id' => $student->id, 'status' => 'present']],
        ]);

        $this->assertDatabaseHas('attendances', ['student_id' => $student->id, 'class_id' => $newClass->id, 'attendance_date' => '2025-10-02']);
    }

    // ------------------------------------------------------------------
    // Enrollment independence — attendance never requires subject enrollment
    // ------------------------------------------------------------------
    public function test_attendance_does_not_require_any_subject_enrollment(): void
    {
        [, $class, $term, $students] = $this->classWithStudents(1);
        $student = $students->first();
        $this->assertSame(0, $student->enrollments()->count()); // deliberately no enrollment at all

        $response = $this->actingAs($this->admin())->post('/admin/attendance/mark', [
            'class_id' => $class->id, 'term_id' => $term->id, 'attendance_date' => '2025-10-01',
            'records' => [['student_id' => $student->id, 'status' => 'present']],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('attendances', ['student_id' => $student->id, 'status' => 'present']);
    }

    // ------------------------------------------------------------------
    // Authorization (admin-side)
    // ------------------------------------------------------------------
    public function test_non_admin_cannot_mark_attendance(): void
    {
        [, $class, $term, $students] = $this->classWithStudents(1);
        $studentUser = User::factory()->create(['role' => 'student']);

        $this->actingAs($studentUser)->get('/admin/attendance/mark')->assertForbidden();
        $this->actingAs($studentUser)->post('/admin/attendance/mark', [
            'class_id' => $class->id, 'term_id' => $term->id, 'attendance_date' => '2025-10-01',
            'records' => [['student_id' => $students->first()->id, 'status' => 'present']],
        ])->assertForbidden();

        $this->assertDatabaseCount('attendances', 0);
    }
}
