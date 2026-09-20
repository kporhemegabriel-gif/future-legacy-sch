<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AssessmentType;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoreManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /** A class+subject+term+assessment(max 40) with one enrolled student. */
    private function setupAssessmentWithEnrolledStudent(int $maxScore = 40): array
    {
        $year = AcademicYear::factory()->create();
        $class = SchoolClass::factory()->create(['academic_year_id' => $year->id]);
        $subject = Subject::factory()->create();
        $class->subjects()->attach($subject->id, ['academic_year_id' => $year->id]);
        $term = Term::factory()->create(['academic_year_id' => $year->id]);
        $type = AssessmentType::factory()->create();

        $assessment = Assessment::create([
            'name' => 'Test Assessment',
            'assessment_type_id' => $type->id,
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'max_score' => $maxScore,
            'status' => 'active',
        ]);

        $student = Student::factory()->create(['class_id' => $class->id]);
        $enrollment = $student->enrollments()->create([
            'subject_id' => $subject->id,
            'class_id' => $class->id,
            'academic_year_id' => $year->id,
        ]);

        return [$assessment, $student, $enrollment, $class, $subject, $year];
    }

    // ------------------------------------------------------------------
    // Valid scores
    // ------------------------------------------------------------------
    public function test_admin_can_enter_a_valid_score(): void
    {
        [$assessment, $student] = $this->setupAssessmentWithEnrolledStudent(40);

        $response = $this->actingAs($this->admin())->put("/admin/assessments/{$assessment->id}/scores", [
            'scores' => [
                ['student_id' => $student->id, 'score' => 35],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('scores', [
            'assessment_id' => $assessment->id,
            'student_id' => $student->id,
            'score' => 35.00,
        ]);
    }

    public function test_a_score_equal_to_the_maximum_is_valid(): void
    {
        [$assessment, $student] = $this->setupAssessmentWithEnrolledStudent(20);

        $response = $this->actingAs($this->admin())->put("/admin/assessments/{$assessment->id}/scores", [
            'scores' => [['student_id' => $student->id, 'score' => 20]],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('scores', ['assessment_id' => $assessment->id, 'student_id' => $student->id, 'score' => 20.00]);
    }

    // ------------------------------------------------------------------
    // Score above maximum
    // ------------------------------------------------------------------
    public function test_score_above_maximum_is_rejected(): void
    {
        [$assessment, $student] = $this->setupAssessmentWithEnrolledStudent(20);

        $response = $this->actingAs($this->admin())->put("/admin/assessments/{$assessment->id}/scores", [
            'scores' => [['student_id' => $student->id, 'score' => 21]],
        ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('scores', 0);
    }

    // ------------------------------------------------------------------
    // Negative score
    // ------------------------------------------------------------------
    public function test_negative_score_is_rejected(): void
    {
        [$assessment, $student] = $this->setupAssessmentWithEnrolledStudent(40);

        $response = $this->actingAs($this->admin())->put("/admin/assessments/{$assessment->id}/scores", [
            'scores' => [['student_id' => $student->id, 'score' => -5]],
        ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('scores', 0);
    }

    // ------------------------------------------------------------------
    // Duplicate score (re-submission updates, never duplicates)
    // ------------------------------------------------------------------
    public function test_resubmitting_a_score_updates_rather_than_duplicates(): void
    {
        [$assessment, $student] = $this->setupAssessmentWithEnrolledStudent(40);
        $admin = $this->admin();

        $this->actingAs($admin)->put("/admin/assessments/{$assessment->id}/scores", [
            'scores' => [['student_id' => $student->id, 'score' => 20]],
        ]);
        $this->actingAs($admin)->put("/admin/assessments/{$assessment->id}/scores", [
            'scores' => [['student_id' => $student->id, 'score' => 30]],
        ]);

        $this->assertDatabaseCount('scores', 1);
        $this->assertDatabaseHas('scores', ['assessment_id' => $assessment->id, 'student_id' => $student->id, 'score' => 30.00]);
    }

    public function test_the_database_itself_rejects_a_true_duplicate_score_row(): void
    {
        [$assessment, $student, $enrollment] = $this->setupAssessmentWithEnrolledStudent(40);

        \App\Models\Score::create([
            'assessment_id' => $assessment->id, 'enrollment_id' => $enrollment->id, 'student_id' => $student->id,
            'subject_id' => $assessment->subject_id, 'class_id' => $assessment->class_id,
            'academic_year_id' => $assessment->academic_year_id, 'term_id' => $assessment->term_id, 'score' => 20,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        \App\Models\Score::create([
            'assessment_id' => $assessment->id, 'enrollment_id' => $enrollment->id, 'student_id' => $student->id,
            'subject_id' => $assessment->subject_id, 'class_id' => $assessment->class_id,
            'academic_year_id' => $assessment->academic_year_id, 'term_id' => $assessment->term_id, 'score' => 25,
        ]);
    }

    // ------------------------------------------------------------------
    // Score audit trail
    // ------------------------------------------------------------------
    public function test_recording_a_score_writes_an_audit_entry(): void
    {
        [$assessment, $student] = $this->setupAssessmentWithEnrolledStudent(40);
        $admin = $this->admin();

        $this->actingAs($admin)->put("/admin/assessments/{$assessment->id}/scores", [
            'scores' => [['student_id' => $student->id, 'score' => 22]],
        ]);

        $this->assertDatabaseHas('score_audits', [
            'assessment_id' => $assessment->id,
            'student_id' => $student->id,
            'changed_by' => $admin->id,
            'old_score' => null,
            'new_score' => 22.00,
        ]);
    }

    public function test_updating_a_score_writes_an_audit_entry_with_the_old_value(): void
    {
        [$assessment, $student] = $this->setupAssessmentWithEnrolledStudent(40);
        $admin = $this->admin();

        $this->actingAs($admin)->put("/admin/assessments/{$assessment->id}/scores", [
            'scores' => [['student_id' => $student->id, 'score' => 22]],
        ]);
        $this->actingAs($admin)->put("/admin/assessments/{$assessment->id}/scores", [
            'scores' => [['student_id' => $student->id, 'score' => 28]],
        ]);

        $this->assertDatabaseHas('score_audits', [
            'assessment_id' => $assessment->id,
            'student_id' => $student->id,
            'old_score' => 22.00,
            'new_score' => 28.00,
        ]);
        $this->assertDatabaseCount('score_audits', 2);
    }

    // ------------------------------------------------------------------
    // Enrollment integrity — forged / mismatched IDs
    // ------------------------------------------------------------------
    public function test_a_score_is_rejected_for_a_student_not_enrolled_in_the_assessments_subject(): void
    {
        [$assessment] = $this->setupAssessmentWithEnrolledStudent(40);
        $notEnrolledStudent = Student::factory()->create(); // no enrollment at all

        $response = $this->actingAs($this->admin())->put("/admin/assessments/{$assessment->id}/scores", [
            'scores' => [['student_id' => $notEnrolledStudent->id, 'score' => 30]],
        ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('scores', 0);
    }

    public function test_a_score_is_rejected_for_a_student_enrolled_in_a_different_class(): void
    {
        [$assessment, , , , $subject] = $this->setupAssessmentWithEnrolledStudent(40);

        // A student enrolled in the SAME subject but a DIFFERENT class —
        // must still be rejected for this assessment.
        $otherYear = AcademicYear::factory()->create();
        $otherClass = SchoolClass::factory()->create(['academic_year_id' => $otherYear->id]);
        $otherClass->subjects()->attach($subject->id, ['academic_year_id' => $otherYear->id]);
        $otherStudent = Student::factory()->create(['class_id' => $otherClass->id]);
        $otherStudent->enrollments()->create([
            'subject_id' => $subject->id,
            'class_id' => $otherClass->id,
            'academic_year_id' => $otherYear->id,
        ]);

        $response = $this->actingAs($this->admin())->put("/admin/assessments/{$assessment->id}/scores", [
            'scores' => [['student_id' => $otherStudent->id, 'score' => 30]],
        ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('scores', 0);
    }

    public function test_a_dropped_enrollment_no_longer_permits_a_score(): void
    {
        [$assessment, $student, $enrollment] = $this->setupAssessmentWithEnrolledStudent(40);
        $enrollment->update(['status' => 'dropped']);

        $response = $this->actingAs($this->admin())->put("/admin/assessments/{$assessment->id}/scores", [
            'scores' => [['student_id' => $student->id, 'score' => 30]],
        ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('scores', 0);
    }

    // ------------------------------------------------------------------
    // Authorization
    // ------------------------------------------------------------------
    public function test_non_admin_cannot_modify_scores(): void
    {
        [$assessment, $student] = $this->setupAssessmentWithEnrolledStudent(40);
        $studentUser = User::factory()->create(['role' => 'student']);
        $parentUser = User::factory()->create(['role' => 'parent']);

        $this->actingAs($studentUser)->get("/admin/assessments/{$assessment->id}/scores")->assertForbidden();
        $this->actingAs($parentUser)->put("/admin/assessments/{$assessment->id}/scores", [
            'scores' => [['student_id' => $student->id, 'score' => 30]],
        ])->assertForbidden();

        $this->assertDatabaseCount('scores', 0);
    }
}
