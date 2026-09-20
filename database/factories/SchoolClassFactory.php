<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\SchoolClass;
use Illuminate\Database\Eloquent\Factories\Factory;

class SchoolClassFactory extends Factory
{
    protected $model = SchoolClass::class;

    public function definition(): array
    {
        return [
            'name' => 'Grade ' . fake()->numberBetween(1, 12),
            'section' => null,
            'academic_year_id' => AcademicYear::factory(),
            'status' => 'active',
        ];
    }
}
