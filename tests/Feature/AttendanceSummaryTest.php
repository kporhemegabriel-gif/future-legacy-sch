<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Term;
use App\Services\AttendanceSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceSummaryTest extends TestCase
{
    use RefreshDatabase;

    private function service(): AttendanceSummaryService
    {
        return app(AttendanceSummaryService::class);
    }

    private function recordDay(Student $student, SchoolClass $class, Term $term, string $date, string $status): void
    {
        Attendance::create([
            'student_id' => $student->id, 'class_id' => $class->id, 'academic_year_id' => $class->academic_year_id,
            'term_id' => $term->id, 'attendance_date' => $date, 'status' => $status,
        ]);
    }

    public function test_summary_counts_each_status_correctly(): void
    {
        $year = AcademicYear::factory()->create();
        $class = SchoolClass::factory()->create(['academic_year_id' => $year->id]);
        $term = Term::factory()->create(['academic_year_id' => $year->id]);
        $student = Student::factory()->create(['class_id' => $class->id]);

        $this->recordDay($student, $class, $term, '2025-10-01', 'present');
        $this->recordDay($student, $class, $term, '2025-10-02', 'present');
        $this->recordDay($student, $class, $term, '2025-10-03', 'absent');
        $this->recordDay($student, $class, $term, '2025-10-04', 'late');
        $this->recordDay($student, $class, $term, '2025-10-05', 'excused');

        $summary = $this->service()->summaryFor($student);

        $this->assertSame(2, $summary['present']);
        $this->assertSame(1, $summary['absent']);
        $this->assertSame(1, $summary['late']);
        $this->assertSame(1, $summary['excused']);
        $this->assertSame(5, $summary['total']);
    }

    public function test_attendance_percentage_counts_only_present_toward_the_numerator(): void
    {
        $year = AcademicYear::factory()->create();
        $class = SchoolClass::factory()->create(['academic_year_id' => $year->id]);
        $term = Term::factory()->create(['academic_year_id' => $year->id]);
        $student = Student::factory()->create(['class_id' => $class->id]);

        // 2 present, 1 late, 1 excused, 1 absent = 5 total, documented rule: 2/5 = 40%
        $this->recordDay($student, $class, $term, '2025-10-01', 'present');
        $this->recordDay($student, $class, $term, '2025-10-02', 'present');
        $this->recordDay($student, $class, $term, '2025-10-03', 'late');
        $this->recordDay($student, $class, $term, '2025-10-04', 'excused');
        $this->recordDay($student, $class, $term, '2025-10-05', 'absent');

        $summary = $this->service()->summaryFor($student);

        $this->assertSame(40.0, $summary['percentage']);
    }

    public function test_summary_with_no_recorded_days_has_a_null_percentage_not_zero(): void
    {
        $student = Student::factory()->create();

        $summary = $this->service()->summaryFor($student);

        $this->assertSame(0, $summary['total']);
        $this->assertNull($summary['percentage']);
    }

    public function test_summary_can_be_scoped_to_a_single_term(): void
    {
        $year = AcademicYear::factory()->create();
        $class = SchoolClass::factory()->create(['academic_year_id' => $year->id]);
        $termA = Term::factory()->create(['academic_year_id' => $year->id, 'name' => 'First Term']);
        $termB = Term::factory()->create(['academic_year_id' => $year->id, 'name' => 'Second Term']);
        $student = Student::factory()->create(['class_id' => $class->id]);

        $this->recordDay($student, $class, $termA, '2025-10-01', 'present');
        $this->recordDay($student, $class, $termA, '2025-10-02', 'absent');
        $this->recordDay($student, $class, $termB, '2026-01-15', 'present');

        $summaryA = $this->service()->summaryFor($student, $termA);
        $summaryB = $this->service()->summaryFor($student, $termB);

        $this->assertSame(2, $summaryA['total']);
        $this->assertSame(1, $summaryB['total']);
    }

    public function test_only_recorded_days_count_never_assumed_calendar_days(): void
    {
        // Only 3 attendance rows exist for this student across a whole
        // month — the summary must reflect exactly 3, never assume
        // weekdays/weekends/holidays in between.
        $year = AcademicYear::factory()->create();
        $class = SchoolClass::factory()->create(['academic_year_id' => $year->id]);
        $term = Term::factory()->create(['academic_year_id' => $year->id]);
        $student = Student::factory()->create(['class_id' => $class->id]);

        $this->recordDay($student, $class, $term, '2025-10-01', 'present');
        $this->recordDay($student, $class, $term, '2025-10-15', 'present');
        $this->recordDay($student, $class, $term, '2025-10-30', 'absent');

        $summary = $this->service()->summaryFor($student);

        $this->assertSame(3, $summary['total']);
    }
}
