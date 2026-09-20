<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\AssessmentType;
use App\Models\SchoolClass;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    /** A class with one subject assigned, and a term in the same academic year. */
    private function classSubjectTerm(): array
    {
        $year = AcademicYear::factory()->create();
        $class = SchoolClass::factory()->create(['academic_year_id' => $year->id]);
        $subject = Subject::factory()->create();
        $class->subjects()->attach($subject->id, ['academic_year_id' => $year->id]);
        $term = Term::factory()->create(['academic_year_id' => $year->id]);

        return [$year, $class, $subject, $term];
    }

    public function test_admin_can_create_an_assessment_for_a_subject_assigned_to_the_class(): void
    {
        [$year, $class, $subject, $term] = $this->classSubjectTerm();
        $type = AssessmentType::factory()->create();

        $response = $this->actingAs($this->admin())->post('/admin/assessments', [
            'name' => 'First Term CA',
            'assessment_type_id' => $type->id,
            'term_id' => $term->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'max_score' => 40,
            'status' => 'active',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('assessments', [
            'name' => 'First Term CA',
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'academic_year_id' => $year->id, // derived server-side from the class, not submitted
            'max_score' => 40,
        ]);
    }

    public function test_assessment_is_rejected_when_subject_is_not_assigned_to_the_class(): void
    {
        [, $class, , $term] = $this->classSubjectTerm();
        $unassignedSubject = Subject::factory()->create();
        $type = AssessmentType::factory()->create();

        $response = $this->actingAs($this->admin())->post('/admin/assessments', [
            'name' => 'Invalid Assessment',
            'assessment_type_id' => $type->id,
            'term_id' => $term->id,
            'class_id' => $class->id,
            'subject_id' => $unassignedSubject->id,
            'max_score' => 40,
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('subject_id');
        $this->assertDatabaseCount('assessments', 0);
    }

    public function test_assessment_is_rejected_when_term_and_class_belong_to_different_years(): void
    {
        [, $class, $subject] = $this->classSubjectTerm();
        $otherYearTerm = Term::factory()->create(['academic_year_id' => AcademicYear::factory()->create()->id]);
        $type = AssessmentType::factory()->create();

        $response = $this->actingAs($this->admin())->post('/admin/assessments', [
            'name' => 'Mismatched Year',
            'assessment_type_id' => $type->id,
            'term_id' => $otherYearTerm->id,
            'class_id' => $class->id,
            'subject_id' => $subject->id,
            'max_score' => 40,
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors('subject_id');
        $this->assertDatabaseCount('assessments', 0);
    }

    public function test_max_score_must_be_a_positive_integer(): void
    {
        [, $class, $subject, $term] = $this->classSubjectTerm();
        $type = AssessmentType::factory()->create();

        $zero = $this->actingAs($this->admin())->post('/admin/assessments', [
            'name' => 'Zero Max', 'assessment_type_id' => $type->id, 'term_id' => $term->id,
            'class_id' => $class->id, 'subject_id' => $subject->id, 'max_score' => 0, 'status' => 'active',
        ]);
        $zero->assertSessionHasErrors('max_score');

        $negative = $this->actingAs($this->admin())->post('/admin/assessments', [
            'name' => 'Negative Max', 'assessment_type_id' => $type->id, 'term_id' => $term->id,
            'class_id' => $class->id, 'subject_id' => $subject->id, 'max_score' => -10, 'status' => 'active',
        ]);
        $negative->assertSessionHasErrors('max_score');

        $this->assertDatabaseCount('assessments', 0);
    }

    public function test_editing_an_assessment_with_recorded_scores_cannot_change_its_class_subject_or_term(): void
    {
        [, $class, $subject, $term] = $this->classSubjectTerm();
        $type = AssessmentType::factory()->create();
        $assessment = \App\Models\Assessment::factory()->create([
            'class_id' => $class->id, 'subject_id' => $subject->id, 'term_id' => $term->id,
            'academic_year_id' => $class->academic_year_id, 'assessment_type_id' => $type->id, 'max_score' => 40,
        ]);
        Score::factory()->create(['assessment_id' => $assessment->id, 'score' => 30]);

        [, $otherClass] = $this->classSubjectTerm();

        $response = $this->actingAs($this->admin())->put("/admin/assessments/{$assessment->id}", [
            'name' => 'Changed', 'assessment_type_id' => $type->id, 'term_id' => $term->id,
            'class_id' => $otherClass->id, 'subject_id' => $subject->id, 'max_score' => 40, 'status' => 'active',
        ]);

        $response->assertSessionHasErrors('class_id');
        $this->assertDatabaseHas('assessments', ['id' => $assessment->id, 'class_id' => $class->id]);
    }

    public function test_max_score_cannot_be_lowered_below_a_recorded_score(): void
    {
        [, $class, $subject, $term] = $this->classSubjectTerm();
        $type = AssessmentType::factory()->create();
        $assessment = \App\Models\Assessment::factory()->create([
            'class_id' => $class->id, 'subject_id' => $subject->id, 'term_id' => $term->id,
            'academic_year_id' => $class->academic_year_id, 'assessment_type_id' => $type->id, 'max_score' => 40,
        ]);
        Score::factory()->create(['assessment_id' => $assessment->id, 'score' => 35]);

        $response = $this->actingAs($this->admin())->put("/admin/assessments/{$assessment->id}", [
            'name' => $assessment->name, 'assessment_type_id' => $type->id, 'term_id' => $term->id,
            'class_id' => $class->id, 'subject_id' => $subject->id, 'max_score' => 20, 'status' => 'active',
        ]);

        $response->assertSessionHasErrors('max_score');
    }

    public function test_assessment_with_recorded_scores_cannot_be_deleted(): void
    {
        $assessment = \App\Models\Assessment::factory()->create();
        Score::factory()->create(['assessment_id' => $assessment->id]);

        $this->actingAs($this->admin())->delete("/admin/assessments/{$assessment->id}");

        $this->assertDatabaseHas('assessments', ['id' => $assessment->id]);
    }

    public function test_non_admin_cannot_manage_assessments(): void
    {
        $studentUser = User::factory()->create(['role' => 'student']);
        $parentUser = User::factory()->create(['role' => 'parent']);

        $this->actingAs($studentUser)->get('/admin/assessments')->assertForbidden();
        $this->actingAs($studentUser)->get('/admin/assessments/create')->assertForbidden();
        $this->actingAs($parentUser)->post('/admin/assessments', [])->assertForbidden();
    }
}
