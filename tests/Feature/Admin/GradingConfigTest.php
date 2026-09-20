<?php

namespace Tests\Feature\Admin;

use App\Models\AcademicYear;
use App\Models\AssessmentType;
use App\Models\GradeBand;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradingConfigTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    // ------------------------------------------------------------------
    // Terms
    // ------------------------------------------------------------------
    public function test_admin_can_create_a_term(): void
    {
        $year = AcademicYear::factory()->create();

        $response = $this->actingAs($this->admin())->post('/admin/terms', [
            'academic_year_id' => $year->id, 'name' => 'First Term', 'sequence' => 1, 'is_current' => '1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('terms', ['academic_year_id' => $year->id, 'name' => 'First Term', 'is_current' => true]);
    }

    public function test_only_one_term_can_be_current_at_a_time(): void
    {
        $year = AcademicYear::factory()->create();
        $first = Term::factory()->create(['academic_year_id' => $year->id, 'name' => 'First Term', 'is_current' => true]);

        $this->actingAs($this->admin())->post('/admin/terms', [
            'academic_year_id' => $year->id, 'name' => 'Second Term', 'sequence' => 2, 'is_current' => '1',
        ]);

        $this->assertFalse($first->fresh()->is_current);
        $this->assertDatabaseHas('terms', ['name' => 'Second Term', 'is_current' => true]);
    }

    public function test_duplicate_term_name_within_the_same_year_is_rejected(): void
    {
        $year = AcademicYear::factory()->create();
        Term::factory()->create(['academic_year_id' => $year->id, 'name' => 'First Term']);

        $response = $this->actingAs($this->admin())->post('/admin/terms', [
            'academic_year_id' => $year->id, 'name' => 'First Term', 'sequence' => 1,
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_same_term_name_is_allowed_in_a_different_year(): void
    {
        $yearA = AcademicYear::factory()->create();
        $yearB = AcademicYear::factory()->create();
        Term::factory()->create(['academic_year_id' => $yearA->id, 'name' => 'First Term']);

        $response = $this->actingAs($this->admin())->post('/admin/terms', [
            'academic_year_id' => $yearB->id, 'name' => 'First Term', 'sequence' => 1,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('terms', 2);
    }

    public function test_term_with_assessments_cannot_be_deleted(): void
    {
        $term = Term::factory()->create();
        \App\Models\Assessment::factory()->create(['term_id' => $term->id]);

        $this->actingAs($this->admin())->delete("/admin/terms/{$term->id}");

        $this->assertDatabaseHas('terms', ['id' => $term->id]);
    }

    // ------------------------------------------------------------------
    // Assessment Types
    // ------------------------------------------------------------------
    public function test_admin_can_create_an_assessment_type(): void
    {
        $response = $this->actingAs($this->admin())->post('/admin/assessment-types', [
            'name' => 'Continuous Assessment', 'status' => 'active',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('assessment_types', ['name' => 'Continuous Assessment']);
    }

    public function test_duplicate_assessment_type_name_is_rejected(): void
    {
        AssessmentType::factory()->create(['name' => 'Examination']);

        $response = $this->actingAs($this->admin())->post('/admin/assessment-types', [
            'name' => 'Examination', 'status' => 'active',
        ]);

        $response->assertSessionHasErrors('name');
    }

    // ------------------------------------------------------------------
    // Grade bands — scoped to an academic year, overlap prevention within that year
    // ------------------------------------------------------------------
    public function test_admin_can_create_a_grade_band_for_an_academic_year(): void
    {
        $year = AcademicYear::factory()->create();

        $response = $this->actingAs($this->admin())->post('/admin/grade-bands', [
            'academic_year_id' => $year->id, 'min_score' => 80, 'max_score' => 100, 'grade' => 'A', 'remark' => 'Excellent', 'status' => 'active',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('grade_bands', ['academic_year_id' => $year->id, 'grade' => 'A', 'min_score' => 80, 'max_score' => 100]);
    }

    public function test_overlapping_active_grade_band_within_the_same_year_is_rejected(): void
    {
        $year = AcademicYear::factory()->create();
        GradeBand::create(['academic_year_id' => $year->id, 'min_score' => 70, 'max_score' => 100, 'grade' => 'A', 'remark' => 'Excellent']);

        $response = $this->actingAs($this->admin())->post('/admin/grade-bands', [
            'academic_year_id' => $year->id, 'min_score' => 60, 'max_score' => 75, 'grade' => 'B', 'remark' => 'Good', 'status' => 'active',
        ]);

        $response->assertSessionHasErrors('min_score');
        $this->assertDatabaseCount('grade_bands', 1);
    }

    public function test_the_same_range_is_allowed_in_a_different_academic_year(): void
    {
        $yearA = AcademicYear::factory()->create();
        $yearB = AcademicYear::factory()->create();
        GradeBand::create(['academic_year_id' => $yearA->id, 'min_score' => 70, 'max_score' => 100, 'grade' => 'A', 'remark' => 'Excellent']);

        // Same range as yearA's band, but for yearB — must NOT be treated as an overlap.
        $response = $this->actingAs($this->admin())->post('/admin/grade-bands', [
            'academic_year_id' => $yearB->id, 'min_score' => 70, 'max_score' => 100, 'grade' => 'A', 'remark' => 'Excellent', 'status' => 'active',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('grade_bands', 2);
    }

    public function test_non_overlapping_grade_band_in_the_same_year_is_accepted(): void
    {
        $year = AcademicYear::factory()->create();
        GradeBand::create(['academic_year_id' => $year->id, 'min_score' => 70, 'max_score' => 100, 'grade' => 'A', 'remark' => 'Excellent']);

        $response = $this->actingAs($this->admin())->post('/admin/grade-bands', [
            'academic_year_id' => $year->id, 'min_score' => 0, 'max_score' => 69, 'grade' => 'F', 'remark' => 'Needs Improvement', 'status' => 'active',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('grade_bands', 2);
    }

    public function test_min_score_cannot_exceed_max_score(): void
    {
        $year = AcademicYear::factory()->create();

        $response = $this->actingAs($this->admin())->post('/admin/grade-bands', [
            'academic_year_id' => $year->id, 'min_score' => 90, 'max_score' => 80, 'grade' => 'A', 'remark' => 'Excellent', 'status' => 'active',
        ]);

        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('grade_bands', 0);
    }

    public function test_changing_a_future_years_grading_scale_does_not_affect_a_past_years_computed_grade(): void
    {
        $pastYear = AcademicYear::factory()->create();
        $futureYear = AcademicYear::factory()->create();
        GradeBand::create(['academic_year_id' => $pastYear->id, 'min_score' => 0, 'max_score' => 100, 'grade' => 'A', 'remark' => 'Excellent']);
        GradeBand::create(['academic_year_id' => $futureYear->id, 'min_score' => 0, 'max_score' => 100, 'grade' => 'A', 'remark' => 'Excellent']);

        // Change the FUTURE year's scale only.
        $futureBand = GradeBand::where('academic_year_id', $futureYear->id)->first();
        $this->actingAs($this->admin())->put("/admin/grade-bands/{$futureBand->id}", [
            'academic_year_id' => $futureYear->id, 'min_score' => 0, 'max_score' => 100, 'grade' => 'Z', 'remark' => 'Changed', 'status' => 'active',
        ]);

        $service = app(\App\Services\ResultCalculationService::class);
        $band = $service->gradeBandFor(90.0, $pastYear->id);

        $this->assertSame('A', $band->grade); // untouched by the future year's change
    }

    // ------------------------------------------------------------------
    // Authorization
    // ------------------------------------------------------------------
    public function test_non_admin_cannot_manage_grading_configuration(): void
    {
        $parentUser = User::factory()->create(['role' => 'parent']);

        $this->actingAs($parentUser)->get('/admin/terms')->assertForbidden();
        $this->actingAs($parentUser)->get('/admin/assessment-types')->assertForbidden();
        $this->actingAs($parentUser)->get('/admin/grade-bands')->assertForbidden();
        $this->actingAs($parentUser)->get('/admin/settings')->assertForbidden();
    }
}
