<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /** Class with one subject already assigned to it, for the "happy path" setup. */
    private function classWithSubject(): array
    {
        $year = AcademicYear::factory()->create();
        $class = SchoolClass::factory()->create(['academic_year_id' => $year->id]);
        $subject = Subject::factory()->create();
        $class->subjects()->attach($subject->id, ['academic_year_id' => $year->id]);

        return [$class, $subject, $year];
    }

    // ------------------------------------------------------------------
    // Valid enrollment: subject IS assigned to the student's class
    // ------------------------------------------------------------------
    public function test_admin_can_enroll_a_student_in_a_subject_assigned_to_their_class(): void
    {
        [$class, $subject, $year] = $this->classWithSubject();
        $student = Student::factory()->create(['class_id' => $class->id]);

        $response = $this->actingAs($this->admin())->post("/admin/students/{$student->id}/enrollments", [
            'subject_id' => $subject->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'class_id' => $class->id,
            'academic_year_id' => $year->id,
            'status' => 'enrolled',
        ]);
    }

    // ------------------------------------------------------------------
    // Invalid enrollment: subject is NOT assigned to the student's class
    // ------------------------------------------------------------------
    public function test_enrollment_is_rejected_when_subject_is_not_assigned_to_the_students_class(): void
    {
        [$class] = $this->classWithSubject();
        $student = Student::factory()->create(['class_id' => $class->id]);

        // A subject that exists, but was never assigned to this class.
        $unassignedSubject = Subject::factory()->create();

        $response = $this->actingAs($this->admin())->post("/admin/students/{$student->id}/enrollments", [
            'subject_id' => $unassignedSubject->id,
        ]);

        $response->assertSessionHasErrors('subject_id');
        $this->assertDatabaseMissing('enrollments', [
            'student_id' => $student->id,
            'subject_id' => $unassignedSubject->id,
        ]);
    }

    public function test_enrollment_is_rejected_when_student_has_no_class_assigned(): void
    {
        $student = Student::factory()->create(['class_id' => null]);
        $subject = Subject::factory()->create();

        $response = $this->actingAs($this->admin())->post("/admin/students/{$student->id}/enrollments", [
            'subject_id' => $subject->id,
        ]);

        $response->assertSessionHasErrors('subject_id');
        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_the_class_subject_rule_cannot_be_bypassed_by_a_manually_crafted_request(): void
    {
        // Even if an attacker somehow still sends class_id/academic_year_id
        // fields (the old, removed form shape), they're simply ignored —
        // the controller derives both from the student's own record, and
        // the subject must still belong to that class.
        [$class] = $this->classWithSubject();
        [$otherClass, $otherClassSubject, $otherYear] = $this->classWithSubject();
        $student = Student::factory()->create(['class_id' => $class->id]);

        $response = $this->actingAs($this->admin())->post("/admin/students/{$student->id}/enrollments", [
            'subject_id' => $otherClassSubject->id, // belongs to $otherClass, not $student's class
            'class_id' => $otherClass->id,           // spoofed — should be ignored entirely
            'academic_year_id' => $otherYear->id,     // spoofed — should be ignored entirely
        ]);

        $response->assertSessionHasErrors('subject_id');
        $this->assertDatabaseCount('enrollments', 0);
    }

    // ------------------------------------------------------------------
    // Duplicate enrollment
    // ------------------------------------------------------------------
    public function test_duplicate_enrollment_for_the_same_year_is_rejected(): void
    {
        [$class, $subject, $year] = $this->classWithSubject();
        $student = Student::factory()->create(['class_id' => $class->id]);

        $student->enrollments()->create([
            'subject_id' => $subject->id,
            'class_id' => $class->id,
            'academic_year_id' => $year->id,
        ]);

        $response = $this->actingAs($this->admin())->post("/admin/students/{$student->id}/enrollments", [
            'subject_id' => $subject->id,
        ]);

        $response->assertSessionHasErrors('subject_id');
        $this->assertDatabaseCount('enrollments', 1);
    }

    // ------------------------------------------------------------------
    // Changing a student's class — existing enrollments vs. new ones
    // ------------------------------------------------------------------
    public function test_existing_enrollments_are_untouched_when_a_students_class_changes(): void
    {
        [$oldClass, $oldSubject, $oldYear] = $this->classWithSubject();
        [$newClass] = $this->classWithSubject();
        $student = Student::factory()->create(['class_id' => $oldClass->id]);

        $enrollment = $student->enrollments()->create([
            'subject_id' => $oldSubject->id,
            'class_id' => $oldClass->id,
            'academic_year_id' => $oldYear->id,
        ]);

        // Move the student to a different class.
        $this->actingAs($this->admin())->put("/admin/students/{$student->id}", [
            'name' => $student->user->name,
            'email' => $student->user->email,
            'admission_number' => $student->admission_number,
            'first_name' => $student->first_name,
            'last_name' => $student->last_name,
            'admission_date' => $student->admission_date->format('Y-m-d'),
            'class_id' => $newClass->id,
            'status' => 'active',
        ]);

        // The historical enrollment record still points at the OLD class
        // and year — it's a record of what the student actually studied,
        // not a live pointer that follows them around.
        $this->assertDatabaseHas('enrollments', [
            'id' => $enrollment->id,
            'class_id' => $oldClass->id,
            'academic_year_id' => $oldYear->id,
        ]);
    }

    public function test_after_a_class_change_new_enrollment_is_validated_against_the_new_class(): void
    {
        [$oldClass, $oldSubject] = $this->classWithSubject();
        [$newClass, $newSubject, $newYear] = $this->classWithSubject();
        $student = Student::factory()->create(['class_id' => $oldClass->id]);

        $this->actingAs($this->admin())->put("/admin/students/{$student->id}", [
            'name' => $student->user->name,
            'email' => $student->user->email,
            'admission_number' => $student->admission_number,
            'first_name' => $student->first_name,
            'last_name' => $student->last_name,
            'admission_date' => $student->admission_date->format('Y-m-d'),
            'class_id' => $newClass->id,
            'status' => 'active',
        ]);
        $student->refresh();

        // The OLD class's subject is no longer enrollable...
        $rejected = $this->actingAs($this->admin())->post("/admin/students/{$student->id}/enrollments", [
            'subject_id' => $oldSubject->id,
        ]);
        $rejected->assertSessionHasErrors('subject_id');

        // ...but the NEW class's subject is.
        $accepted = $this->actingAs($this->admin())->post("/admin/students/{$student->id}/enrollments", [
            'subject_id' => $newSubject->id,
        ]);
        $accepted->assertRedirect();
        $this->assertDatabaseHas('enrollments', [
            'student_id' => $student->id,
            'subject_id' => $newSubject->id,
            'class_id' => $newClass->id,
            'academic_year_id' => $newYear->id,
        ]);
    }

    // ------------------------------------------------------------------
    // Removal
    // ------------------------------------------------------------------
    public function test_admin_can_remove_an_enrollment(): void
    {
        [$class, $subject, $year] = $this->classWithSubject();
        $student = Student::factory()->create(['class_id' => $class->id]);
        $enrollment = $student->enrollments()->create([
            'subject_id' => $subject->id,
            'class_id' => $class->id,
            'academic_year_id' => $year->id,
        ]);

        $this->actingAs($this->admin())->delete("/admin/students/{$student->id}/enrollments/{$enrollment->id}");

        $this->assertDatabaseMissing('enrollments', ['id' => $enrollment->id]);
    }

    // ------------------------------------------------------------------
    // Authorization / cross-role protection
    // ------------------------------------------------------------------
    public function test_non_admin_cannot_enroll_students(): void
    {
        [$class, $subject] = $this->classWithSubject();
        $student = Student::factory()->create(['class_id' => $class->id]);
        $studentUser = User::factory()->create(['role' => 'student']);

        $this->actingAs($studentUser)->post("/admin/students/{$student->id}/enrollments", [
            'subject_id' => $subject->id,
        ])->assertForbidden();

        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_parent_cannot_enroll_their_own_child(): void
    {
        [$class, $subject] = $this->classWithSubject();
        $student = Student::factory()->create(['class_id' => $class->id]);
        $parentUser = User::factory()->create(['role' => 'parent']);

        $this->actingAs($parentUser)->post("/admin/students/{$student->id}/enrollments", [
            'subject_id' => $subject->id,
        ])->assertForbidden();

        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_non_admin_cannot_remove_an_enrollment(): void
    {
        [$class, $subject, $year] = $this->classWithSubject();
        $student = Student::factory()->create(['class_id' => $class->id]);
        $enrollment = $student->enrollments()->create([
            'subject_id' => $subject->id,
            'class_id' => $class->id,
            'academic_year_id' => $year->id,
        ]);
        $studentUser = User::factory()->create(['role' => 'student']);

        $this->actingAs($studentUser)
            ->delete("/admin/students/{$student->id}/enrollments/{$enrollment->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('enrollments', ['id' => $enrollment->id]);
    }
}
