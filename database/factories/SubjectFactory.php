<?php

namespace Database\Factories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('???###')),
            'name' => fake()->randomElement(['Mathematics', 'English Language', 'Integrated Science', 'Social Studies', 'French', 'ICT']),
            'status' => 'active',
        ];
    }
}
