<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AssessmentType;
use App\Models\GradeBand;
use App\Models\ParentGuardian;
use App\Models\SchoolClass;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Models\TermResult;
use App\Models\User;
use App\Services\ResultCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TermResultTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function service(): ResultCalculationService
    {
        return app(ResultCalculationService::class);
    }

    /** One class, one subject (assigned to the class), one term, and a CA(40)+Exam(60) assessment pair. */
    private function classWithGradedSubject(): array
    {
        $year = AcademicYear::factory()->create();
        $class = SchoolClass::factory()->create(['academic_year_id' => $year->id]);
        $subject = Subject::factory()->create();
        $class->subjects()->attach($subject->id, ['academic_year_id' => $year->id]);
        $term = Term::factory()->create(['academic_year_id' => $year->id]);
        $type = AssessmentType::factory()->create();

        $ca = Assessment::create([
            'name' => 'CA', 'assessment_type_id' => $type->id, 'academic_year_id' => $year->id,
            'term_id' => $term->id, 'class_id' => $class->id, 'subject_id' => $subject->id, 'max_score' => 40,
        ]);
        $exam = Assessment::create([
            'name' => 'Exam', 'assessment_type_id' => $type->id, 'academic_year_id' => $year->id,
            'term_id' => $term->id, 'class_id' => $class->id, 'subject_id' => $subject->id, 'max_score' => 60,
        ]);

        return [$year, $class, $subject, $term, $ca, $exam];
    }

    private function enrollAndScore(Student $student, SchoolClass $class, Subject $subject, AcademicYear $year, Assessment $ca, Assessment $exam, float $caScore, float $examScore): void
    {
        $enrollment = $student->enrollments()->create([
            'subject_id' => $subject->id, 'class_id' => $class->id, 'academic_year_id' => $year->id,
        ]);

        foreach ([[$ca, $caScore], [$exam, $examScore]] as [$assessment, $score]) {
            Score::create([
                'assessment_id' => $assessment->id, 'enrollment_id' => $enrollment->id, 'student_id' => $student->id,
                'subject_id' => $subject->id, 'class_id' => $class->id, 'academic_year_id' => $year->id,
                'term_id' => $assessment->term_id, 'score' => $score,
            ]);
        }
    }

    // ------------------------------------------------------------------
    // Correct total / percentage calculation
    // ------------------------------------------------------------------
    public function test_subject_total_is_the_sum_of_scores_against_the_sum_of_assessment_max_scores(): void
    {
        [$year, $class, $subject, $term, $ca, $exam] = $this->classWithGradedSubject();
        $student = Student::factory()->create(['class_id' => $class->id]);
        $this->enrollAndScore($student, $class, $subject, $year, $ca, $exam, 30, 50); // 80/100

        $result = $this->service()->subjectResult($student, $subject, $class, $term);

        $this->assertSame(80.0, $result['total_score']);
        $this->assertSame(100, $result['total_max_score']);
        $this->assertSame(80.0, $result['percentage']);
    }

    public function test_a_missed_assessment_counts_as_zero_not_as_excluded(): void
    {
        [$year, $class, $subject, $term, $ca, $exam] = $this->classWithGradedSubject();
        $student = Student::factory()->create(['class_id' => $class->id]);
        $enrollment = $student->enrollments()->create(['subject_id' => $subject->id, 'class_id' => $class->id, 'academic_year_id' => $year->id]);

        // Only the CA was scored — the exam was never entered.
        Score::create([
            'assessment_id' => $ca->id, 'enrollment_id' => $enrollment->id, 'student_id' => $student->id,
            'subject_id' => $subject->id, 'class_id' => $class->id, 'academic_year_id' => $year->id,
            'term_id' => $ca->term_id, 'score' => 30,
        ]);

        $result = $this->service()->subjectResult($student, $subject, $class, $term);

        $this->assertSame(30.0, $result['total_score']);
        $this->assertSame(100, $result['total_max_score']); // still counts the exam's max
        $this->assertSame(30.0, $result['percentage']);
    }

    // ------------------------------------------------------------------
    // Correct grade + remark from the configured grading scale
    // ------------------------------------------------------------------
    public function test_grade_and_remark_come_from_the_configured_grading_scale(): void
    {
        [$year, $class, $subject, $term, $ca, $exam] = $this->classWithGradedSubject();
        GradeBand::create(['academic_year_id' => $year->id, 'min_score' => 80, 'max_score' => 100, 'grade' => 'A', 'remark' => 'Excellent']);
        GradeBand::create(['academic_year_id' => $year->id, 'min_score' => 70, 'max_score' => 79, 'grade' => 'B', 'remark' => 'Very Good']);
        GradeBand::create(['academic_year_id' => $year->id, 'min_score' => 0, 'max_score' => 69, 'grade' => 'F', 'remark' => 'Needs Improvement']);

        $student = Student::factory()->create(['class_id' => $class->id]);
        $this->enrollAndScore($student, $class, $subject, $year, $ca, $exam, 32, 50); // 82/100 = 82%

        $result = $this->service()->subjectResult($student, $subject, $class, $term);

        $this->assertSame(82.0, $result['percentage']);
        $this->assertSame('A', $result['grade']);
        $this->assertSame('Excellent', $result['remark']);
    }

    public function test_a_percentage_with_no_matching_band_gets_no_grade(): void
    {
        [$year, $class, $subject, $term, $ca, $exam] = $this->classWithGradedSubject();
        GradeBand::create(['academic_year_id' => $year->id, 'min_score' => 50, 'max_score' => 100, 'grade' => 'A', 'remark' => 'Pass']);
        // Nothing covers 0-49.

        $student = Student::factory()->create(['class_id' => $class->id]);
        $this->enrollAndScore($student, $class, $subject, $year, $ca, $exam, 5, 5); // 10%

        $result = $this->service()->subjectResult($student, $subject, $class, $term);

        $this->assertNull($result['grade']);
        $this->assertNull($result['remark']);
    }

    // ------------------------------------------------------------------
    // computeForClassTerm — averages and ranking
    // ------------------------------------------------------------------
    public function test_compute_for_class_term_ranks_students_by_average_with_ties_handled(): void
    {
        [$year, $class, $subject, $term, $ca, $exam] = $this->classWithGradedSubject();

        $top = Student::factory()->create(['class_id' => $class->id, 'status' => 'active']);
        $this->enrollAndScore($top, $class, $subject, $year, $ca, $exam, 40, 60); // 100%

        $tiedA = Student::factory()->create(['class_id' => $class->id, 'status' => 'active']);
        $this->enrollAndScore($tiedA, $class, $subject, $year, $ca, $exam, 20, 30); // 50%

        $tiedB = Student::factory()->create(['class_id' => $class->id, 'status' => 'active']);
        $this->enrollAndScore($tiedB, $class, $subject, $year, $ca, $exam, 25, 25); // 50%

        $last = Student::factory()->create(['class_id' => $class->id, 'status' => 'active']);
        $this->enrollAndScore($last, $class, $subject, $year, $ca, $exam, 5, 5); // 10%

        $this->service()->computeForClassTerm($class, $term);

        $this->assertSame(1, TermResult::where('student_id', $top->id)->value('position'));
        $this->assertSame(2, TermResult::where('student_id', $tiedA->id)->value('position'));
        $this->assertSame(2, TermResult::where('student_id', $tiedB->id)->value('position'));
        $this->assertSame(4, TermResult::where('student_id', $last->id)->value('position'));
    }

    public function test_recomputing_does_not_change_publish_status_but_does_update_the_numbers(): void
    {
        [$year, $class, $subject, $term, $ca, $exam] = $this->classWithGradedSubject();
        $student = Student::factory()->create(['class_id' => $class->id, 'status' => 'active']);
        $this->enrollAndScore($student, $class, $subject, $year, $ca, $exam, 20, 20); // 40/100 = 40%

        $this->service()->computeForClassTerm($class, $term);
        $termResult = TermResult::where('student_id', $student->id)->first();
        $this->assertSame(40.0, (float) $termResult->average_percentage);
        $termResult->update(['status' => 'published']);

        // Correct the CA score upward, then recompute.
        Score::where('assessment_id', $ca->id)->where('student_id', $student->id)->update(['score' => 40]); // now 60/100 = 60%
        $this->service()->computeForClassTerm($class, $term);

        $termResult->refresh();
        $this->assertSame('published', $termResult->status); // untouched by recompute
        $this->assertSame(60.0, (float) $termResult->average_percentage); // but the number itself did update
    }

    // ------------------------------------------------------------------
    // Publish / unpublish visibility
    // ------------------------------------------------------------------
    public function test_unpublished_result_is_hidden_from_student_and_parent(): void
    {
        [$year, $class, $subject, $term, $ca, $exam] = $this->classWithGradedSubject();
        $student = Student::factory()->create(['class_id' => $class->id]);
        $this->enrollAndScore($student, $class, $subject, $year, $ca, $exam, 20, 20);
        $this->service()->computeForClassTerm($class, $term);
        $termResult = TermResult::where('student_id', $student->id)->first();

        $parentUser = User::factory()->create(['role' => 'parent']);
        $parentProfile = ParentGuardian::factory()->create(['user_id' => $parentUser->id]);
        $parentProfile->syncStudent($student, 'Father', true);

        $this->actingAs($student->user)->get("/student/results/{$termResult->id}")->assertForbidden();
        $this->actingAs($parentUser)->get("/parent/children/{$student->id}/results/{$termResult->id}")->assertForbidden();
    }

    public function test_published_result_is_visible_to_the_correct_student_and_parent(): void
    {
        [$year, $class, $subject, $term, $ca, $exam] = $this->classWithGradedSubject();
        $student = Student::factory()->create(['class_id' => $class->id]);
        $this->enrollAndScore($student, $class, $subject, $year, $ca, $exam, 20, 20);
        $this->service()->computeForClassTerm($class, $term);
        $termResult = TermResult::where('student_id', $student->id)->first();
        $termResult->update(['status' => 'published']);

        $parentUser = User::factory()->create(['role' => 'parent']);
        $parentProfile = ParentGuardian::factory()->create(['user_id' => $parentUser->id]);
        $parentProfile->syncStudent($student, 'Father', true);

        $this->actingAs($student->user)->get("/student/results/{$termResult->id}")->assertOk();
        $this->actingAs($parentUser)->get("/parent/children/{$student->id}/results/{$termResult->id}")->assertOk();
    }

    // ------------------------------------------------------------------
    // Student authorization — own vs. another student's result
    // ------------------------------------------------------------------
    public function test_student_cannot_view_another_students_result_even_when_published(): void
    {
        [$year, $class, $subject, $term, $ca, $exam] = $this->classWithGradedSubject();
        $studentA = Student::factory()->create(['class_id' => $class->id]);
        $studentB = Student::factory()->create(['class_id' => $class->id]);
        $this->enrollAndScore($studentA, $class, $subject, $year, $ca, $exam, 20, 20);
        $this->service()->computeForClassTerm($class, $term);
        $termResultA = TermResult::where('student_id', $studentA->id)->first();
        $termResultA->update(['status' => 'published']);

        $this->actingAs($studentB->user)->get("/student/results/{$termResultA->id}")->assertForbidden();
    }

    // ------------------------------------------------------------------
    // Parent authorization — linked vs. non-linked child
    // ------------------------------------------------------------------
    public function test_parent_cannot_view_a_non_linked_students_result(): void
    {
        [$year, $class, $subject, $term, $ca, $exam] = $this->classWithGradedSubject();
        $ownChild = Student::factory()->create(['class_id' => $class->id]);
        $otherChild = Student::factory()->create(['class_id' => $class->id]);
        $this->enrollAndScore($otherChild, $class, $subject, $year, $ca, $exam, 20, 20);
        $this->service()->computeForClassTerm($class, $term);
        $otherResult = TermResult::where('student_id', $otherChild->id)->first();
        $otherResult->update(['status' => 'published']);

        $parentUser = User::factory()->create(['role' => 'parent']);
        $parentProfile = ParentGuardian::factory()->create(['user_id' => $parentUser->id]);
        $parentProfile->syncStudent($ownChild, 'Mother', true);

        $this->actingAs($parentUser)->get("/parent/children/{$otherChild->id}/results/{$otherResult->id}")->assertForbidden();
    }

    // ------------------------------------------------------------------
    // Admin authorization
    // ------------------------------------------------------------------
    public function test_admin_can_compute_and_publish_results(): void
    {
        [$year, $class, $subject, $term, $ca, $exam] = $this->classWithGradedSubject();
        $student = Student::factory()->create(['class_id' => $class->id, 'status' => 'active']);
        $this->enrollAndScore($student, $class, $subject, $year, $ca, $exam, 20, 20);

        $this->actingAs($this->admin())->post('/admin/term-results/compute', [
            'class_id' => $class->id, 'term_id' => $term->id,
        ])->assertRedirect();

        $termResult = TermResult::where('student_id', $student->id)->firstOrFail();

        $this->actingAs($this->admin())
            ->patch("/admin/term-results/{$termResult->id}/publish")
            ->assertRedirect();

        $this->assertSame('published', $termResult->fresh()->status);
    }

    public function test_non_admin_cannot_compute_or_publish_results(): void
    {
        [$year, $class, $subject, $term, $ca, $exam] = $this->classWithGradedSubject();
        $student = Student::factory()->create(['class_id' => $class->id]);
        $this->enrollAndScore($student, $class, $subject, $year, $ca, $exam, 20, 20);
        $this->service()->computeForClassTerm($class, $term);
        $termResult = TermResult::where('student_id', $student->id)->first();

        $studentUser = User::factory()->create(['role' => 'student']);

        $this->actingAs($studentUser)->post('/admin/term-results/compute', [
            'class_id' => $class->id, 'term_id' => $term->id,
        ])->assertForbidden();

        $this->actingAs($studentUser)
            ->patch("/admin/term-results/{$termResult->id}/publish")
            ->assertForbidden();

        $this->assertSame('draft', $termResult->fresh()->status);
    }
}
