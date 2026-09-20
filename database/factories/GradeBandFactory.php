<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\GradeBand;
use Illuminate\Database\Eloquent\Factories\Factory;

class GradeBandFactory extends Factory
{
    protected $model = GradeBand::class;

    public function definition(): array
    {
        return [
            'academic_year_id' => AcademicYear::factory(),
            'min_score' => 0,
            'max_score' => 100,
            'grade' => 'A',
            'remark' => 'Excellent',
            'status' => 'active',
        ];
    }
}
