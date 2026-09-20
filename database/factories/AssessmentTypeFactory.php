<?php

namespace Database\Factories;

use App\Models\AssessmentType;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssessmentTypeFactory extends Factory
{
    protected $model = AssessmentType::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement(['Continuous Assessment', 'Test', 'Assignment', 'Examination', 'Class Exercise', 'Project Work']),
            'status' => 'active',
        ];
    }
}
