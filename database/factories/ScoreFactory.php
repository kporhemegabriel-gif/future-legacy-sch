<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\Score;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Independent defaults only — tests that need a Score whose FKs actually
 * line up with a real Assessment/Enrollment should pass every field
 * explicitly rather than rely on this factory to cross-reference them.
 */
class ScoreFactory extends Factory
{
    protected $model = Score::class;

    public function definition(): array
    {
        return [
            'assessment_id' => Assessment::factory(),
            'enrollment_id' => Enrollment::factory(),
            'student_id' => Student::factory(),
            'subject_id' => Subject::factory(),
            'class_id' => SchoolClass::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'term_id' => Term::factory(),
            'score' => 50,
            'recorded_by' => null,
        ];
    }
}
