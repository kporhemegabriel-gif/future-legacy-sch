<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\ParentGuardian;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_create_a_student_with_an_account(): void
    {
        $class = SchoolClass::factory()->create();

        $response = $this->actingAs($this->admin())->post('/admin/students', [
            'name' => 'Kofi Adjei',
            'email' => 'kofi.adjei@students.futurelegacyschool.edu',
            'password' => 'password123',
            'admission_number' => 'FLS-2026-0001',
            'first_name' => 'Kofi',
            'last_name' => 'Adjei',
            'admission_date' => '2026-09-01',
            'class_id' => $class->id,
            'academic_year_id' => $class->academic_year_id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', ['email' => 'kofi.adjei@students.futurelegacyschool.edu', 'role' => 'student', 'status' => 'active']);
        $this->assertDatabaseHas('students', ['admission_number' => 'FLS-2026-0001', 'status' => 'active']);
    }

    public function test_role_and_status_cannot_be_injected_via_the_student_create_form(): void
    {
        $class = SchoolClass::factory()->create();

        $this->actingAs($this->admin())->post('/admin/students', [
            'name' => 'Injected Role',
            'email' => 'injected@students.futurelegacyschool.edu',
            'password' => 'password123',
            'role' => 'admin', // attempted injection — StoreStudentRequest doesn't validate or pass this through
            'status' => 'inactive', // attempted injection
            'admission_number' => 'FLS-2026-0002',
            'first_name' => 'Injected',
            'last_name' => 'Role',
            'admission_date' => '2026-09-01',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'injected@students.futurelegacyschool.edu',
            'role' => 'student',
            'status' => 'active',
        ]);
    }

    public function test_duplicate_admission_number_is_rejected(): void
    {
        Student::factory()->create(['admission_number' => 'FLS-2026-0099']);

        $response = $this->actingAs($this->admin())->post('/admin/students', [
            'name' => 'Second Student',
            'email' => 'second@students.futurelegacyschool.edu',
            'password' => 'password123',
            'admission_number' => 'FLS-2026-0099',
            'first_name' => 'Second',
            'last_name' => 'Student',
            'admission_date' => '2026-09-01',
        ]);

        $response->assertSessionHasErrors('admission_number');
    }

    public function test_admin_can_update_a_students_enrollment_status(): void
    {
        $student = Student::factory()->create();

        $response = $this->actingAs($this->admin())->put("/admin/students/{$student->id}", [
            'name' => $student->user->name,
            'email' => $student->user->email,
            'admission_number' => $student->admission_number,
            'first_name' => $student->first_name,
            'last_name' => $student->last_name,
            'admission_date' => $student->admission_date->format('Y-m-d'),
            'status' => 'withdrawn',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('students', ['id' => $student->id, 'status' => 'withdrawn']);
    }

    public function test_toggling_account_status_never_touches_students_status(): void
    {
        $student = Student::factory()->create(['status' => 'active']);

        $this->actingAs($this->admin())->patch("/admin/students/{$student->id}/toggle-account");

        $student->refresh();
        $this->assertSame('inactive', $student->user->fresh()->status);
        $this->assertSame('active', $student->status); // untouched
    }

    public function test_a_student_can_only_view_their_own_profile(): void
    {
        $studentA = Student::factory()->create();
        $studentB = Student::factory()->create();

        $response = $this->actingAs($studentB->user)->get('/student/profile');

        $response->assertOk();
        $response->assertSee($studentB->admission_number);
        $response->assertDontSee($studentA->admission_number);
    }

    public function test_a_parent_is_blocked_from_the_admin_student_show_route_entirely(): void
    {
        // Parents use their own /parent/children/{student} route (tested in
        // ParentManagementTest); the role:admin middleware on this route
        // must block them well before any Policy check runs.
        $ownChild = Student::factory()->create();
        $otherChild = Student::factory()->create();

        $parentUser = User::factory()->create(['role' => 'parent']);
        $parentProfile = ParentGuardian::factory()->create(['user_id' => $parentUser->id]);
        $parentProfile->syncStudent($ownChild, 'Mother', true);

        $this->actingAs($parentUser)->get("/admin/students/{$otherChild->id}")->assertForbidden();
    }

    public function test_non_admin_cannot_create_students(): void
    {
        $parentUser = User::factory()->create(['role' => 'parent']);

        $this->actingAs($parentUser)->get('/admin/students/create')->assertForbidden();
    }
}
