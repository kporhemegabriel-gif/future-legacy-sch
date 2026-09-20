<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\ParentGuardian;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function studentWithAttendance(): array
    {
        $year = AcademicYear::factory()->create();
        $class = SchoolClass::factory()->create(['academic_year_id' => $year->id]);
        $term = Term::factory()->create(['academic_year_id' => $year->id]);
        $student = Student::factory()->create(['class_id' => $class->id]);

        Attendance::create([
            'student_id' => $student->id, 'class_id' => $class->id, 'academic_year_id' => $year->id,
            'term_id' => $term->id, 'attendance_date' => '2025-10-01', 'status' => 'present',
        ]);

        return [$student, $class, $term];
    }

    // ------------------------------------------------------------------
    // Student
    // ------------------------------------------------------------------
    public function test_student_can_view_their_own_attendance(): void
    {
        [$student] = $this->studentWithAttendance();

        $response = $this->actingAs($student->user)->get('/student/attendance');

        $response->assertOk();
    }

    public function test_a_student_cannot_view_another_students_attendance_via_the_admin_route(): void
    {
        [$studentA] = $this->studentWithAttendance();
        [$studentB] = $this->studentWithAttendance();

        // role:admin middleware blocks a student from this route entirely,
        // regardless of whose attendance they're trying to view.
        $this->actingAs($studentA->user)
            ->get("/admin/students/{$studentB->id}/attendance")
            ->assertForbidden();
    }

    public function test_student_cannot_mark_or_modify_attendance(): void
    {
        [$student, $class, $term] = $this->studentWithAttendance();

        $this->actingAs($student->user)->get('/admin/attendance/mark')->assertForbidden();
        $this->actingAs($student->user)->post('/admin/attendance/mark', [
            'class_id' => $class->id, 'term_id' => $term->id, 'attendance_date' => '2025-10-02',
            'records' => [['student_id' => $student->id, 'status' => 'present']],
        ])->assertForbidden();
    }

    // ------------------------------------------------------------------
    // Parent
    // ------------------------------------------------------------------
    public function test_parent_can_view_their_linked_childs_attendance(): void
    {
        [$student] = $this->studentWithAttendance();
        $parentUser = User::factory()->create(['role' => 'parent']);
        $parentProfile = ParentGuardian::factory()->create(['user_id' => $parentUser->id]);
        $parentProfile->syncStudent($student, 'Mother', true);

        $response = $this->actingAs($parentUser)->get("/parent/children/{$student->id}/attendance");

        $response->assertOk();
    }

    public function test_parent_cannot_view_a_non_linked_students_attendance(): void
    {
        [$ownChild] = $this->studentWithAttendance();
        [$otherChild] = $this->studentWithAttendance();
        $parentUser = User::factory()->create(['role' => 'parent']);
        $parentProfile = ParentGuardian::factory()->create(['user_id' => $parentUser->id]);
        $parentProfile->syncStudent($ownChild, 'Mother', true);

        $response = $this->actingAs($parentUser)->get("/parent/children/{$otherChild->id}/attendance");

        $response->assertForbidden();
    }

    public function test_parent_cannot_mark_or_modify_attendance(): void
    {
        [$student, $class, $term] = $this->studentWithAttendance();
        $parentUser = User::factory()->create(['role' => 'parent']);
        $parentProfile = ParentGuardian::factory()->create(['user_id' => $parentUser->id]);
        $parentProfile->syncStudent($student, 'Mother', true);

        $this->actingAs($parentUser)->get('/admin/attendance/mark')->assertForbidden();
        $this->actingAs($parentUser)->post('/admin/attendance/mark', [
            'class_id' => $class->id, 'term_id' => $term->id, 'attendance_date' => '2025-10-02',
            'records' => [['student_id' => $student->id, 'status' => 'present']],
        ])->assertForbidden();
    }

    // ------------------------------------------------------------------
    // Admin
    // ------------------------------------------------------------------
    public function test_admin_can_view_and_manage_attendance(): void
    {
        [$student] = $this->studentWithAttendance();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get('/admin/attendance')->assertOk();
        $this->actingAs($admin)->get('/admin/attendance/mark')->assertOk();
        $this->actingAs($admin)->get("/admin/students/{$student->id}/attendance")->assertOk();
    }
}
