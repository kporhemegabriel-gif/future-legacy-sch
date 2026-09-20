<?php

namespace Database\Factories;

use App\Models\AssessmentType;
use App\Models\Assessment;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Term;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssessmentFactory extends Factory
{
    protected $model = Assessment::class;

    public function definition(): array
    {
        $class = SchoolClass::factory()->create();

        return [
            'name' => fake()->words(2, true),
            'assessment_type_id' => AssessmentType::factory(),
            'academic_year_id' => $class->academic_year_id,
            'term_id' => Term::factory(['academic_year_id' => $class->academic_year_id]),
            'class_id' => $class->id,
            'subject_id' => Subject::factory(),
            'max_score' => 100,
            'status' => 'active',
            'assessment_date' => null,
        ];
    }
}
