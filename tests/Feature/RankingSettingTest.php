<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\AssessmentType;
use App\Models\ParentGuardian;
use App\Models\SchoolClass;
use App\Models\Score;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Models\TermResult;
use App\Models\User;
use App\Services\ResultCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingSettingTest extends TestCase
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

    /** Two students in one class/term, with clearly different scores so ranking would be meaningful if computed. */
    private function classWithTwoScoredStudents(): array
    {
        $year = AcademicYear::factory()->create();
        $class = SchoolClass::factory()->create(['academic_year_id' => $year->id]);
        $subject = Subject::factory()->create();
        $class->subjects()->attach($subject->id, ['academic_year_id' => $year->id]);
        $term = Term::factory()->create(['academic_year_id' => $year->id]);
        $type = AssessmentType::factory()->create();
        $assessment = Assessment::create([
            'name' => 'Test', 'assessment_type_id' => $type->id, 'academic_year_id' => $year->id,
            'term_id' => $term->id, 'class_id' => $class->id, 'subject_id' => $subject->id, 'max_score' => 100,
        ]);

        $top = Student::factory()->create(['class_id' => $class->id, 'status' => 'active']);
        $bottom = Student::factory()->create(['class_id' => $class->id, 'status' => 'active']);

        foreach ([[$top, 90], [$bottom, 40]] as [$student, $score]) {
            $enrollment = $student->enrollments()->create(['subject_id' => $subject->id, 'class_id' => $class->id, 'academic_year_id' => $year->id]);
            Score::create([
                'assessment_id' => $assessment->id, 'enrollment_id' => $enrollment->id, 'student_id' => $student->id,
                'subject_id' => $subject->id, 'class_id' => $class->id, 'academic_year_id' => $year->id,
                'term_id' => $term->id, 'score' => $score,
            ]);
        }

        return [$class, $term, $top, $bottom];
    }

    // ------------------------------------------------------------------
    // Default state and toggling
    // ------------------------------------------------------------------
    public function test_ranking_is_enabled_by_default(): void
    {
        $this->assertTrue(Setting::rankingEnabled());
    }

    public function test_admin_can_disable_ranking(): void
    {
        $this->actingAs($this->admin())->put('/admin/settings', ['ranking_enabled' => '0']);

        $this->assertFalse(Setting::rankingEnabled());
    }

    public function test_admin_can_re_enable_ranking(): void
    {
        Setting::setRankingEnabled(false);

        $this->actingAs($this->admin())->put('/admin/settings', ['ranking_enabled' => '1']);

        $this->assertTrue(Setting::rankingEnabled());
    }

    public function test_non_admin_cannot_change_the_ranking_setting(): void
    {
        $studentUser = User::factory()->create(['role' => 'student']);

        $this->actingAs($studentUser)->put('/admin/settings', ['ranking_enabled' => '0'])->assertForbidden();

        $this->assertTrue(Setting::rankingEnabled());
    }

    // ------------------------------------------------------------------
    // computeForClassTerm respects the setting
    // ------------------------------------------------------------------
    public function test_positions_are_calculated_when_ranking_is_enabled(): void
    {
        [$class, $term, $top, $bottom] = $this->classWithTwoScoredStudents();

        $this->service()->computeForClassTerm($class, $term);

        $this->assertSame(1, TermResult::where('student_id', $top->id)->value('position'));
        $this->assertSame(2, TermResult::where('student_id', $bottom->id)->value('position'));
    }

    public function test_positions_are_not_calculated_when_ranking_is_disabled(): void
    {
        Setting::setRankingEnabled(false);
        [$class, $term, $top, $bottom] = $this->classWithTwoScoredStudents();

        $this->service()->computeForClassTerm($class, $term);

        $this->assertNull(TermResult::where('student_id', $top->id)->value('position'));
        $this->assertNull(TermResult::where('student_id', $bottom->id)->value('position'));
    }

    public function test_disabling_ranking_after_a_compute_clears_the_stored_position_on_recompute(): void
    {
        [$class, $term, $top] = $this->classWithTwoScoredStudents();

        $this->service()->computeForClassTerm($class, $term);
        $this->assertNotNull(TermResult::where('student_id', $top->id)->value('position'));

        Setting::setRankingEnabled(false);
        $this->service()->computeForClassTerm($class, $term);

        $this->assertNull(TermResult::where('student_id', $top->id)->value('position'));
    }

    // ------------------------------------------------------------------
    // Display is gated on the live setting, not just the stored value
    // ------------------------------------------------------------------
    public function test_position_is_hidden_from_a_published_result_when_ranking_is_disabled(): void
    {
        [$class, $term, $top] = $this->classWithTwoScoredStudents();
        $this->service()->computeForClassTerm($class, $term); // ranking enabled (default) — position gets stored
        $termResult = TermResult::where('student_id', $top->id)->first();
        $termResult->update(['status' => 'published']);
        $this->assertNotNull($termResult->position);

        // Disable ranking WITHOUT recomputing — the stored position value
        // is still non-null, but display must still hide it.
        Setting::setRankingEnabled(false);

        $response = $this->actingAs($top->user)->get("/student/results/{$termResult->id}");

        $response->assertOk();
        $response->assertDontSee('Position in class');
    }

    public function test_position_is_shown_when_ranking_is_enabled(): void
    {
        [$class, $term, $top] = $this->classWithTwoScoredStudents();
        $this->service()->computeForClassTerm($class, $term);
        $termResult = TermResult::where('student_id', $top->id)->first();
        $termResult->update(['status' => 'published']);

        $response = $this->actingAs($top->user)->get("/student/results/{$termResult->id}");

        $response->assertOk();
        $response->assertSee('Position in class');
    }

    public function test_position_is_hidden_from_parent_view_when_ranking_is_disabled(): void
    {
        [$class, $term, $top] = $this->classWithTwoScoredStudents();
        $this->service()->computeForClassTerm($class, $term);
        $termResult = TermResult::where('student_id', $top->id)->first();
        $termResult->update(['status' => 'published']);
        Setting::setRankingEnabled(false);

        $parentUser = User::factory()->create(['role' => 'parent']);
        $parentProfile = ParentGuardian::factory()->create(['user_id' => $parentUser->id]);
        $parentProfile->syncStudent($top, 'Father', true);

        $response = $this->actingAs($parentUser)->get("/parent/children/{$top->id}/results/{$termResult->id}");

        $response->assertOk();
        $response->assertDontSee('Position in class');
    }
}
