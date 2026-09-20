<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Factories\Factory;

class AcademicYearFactory extends Factory
{
    protected $model = AcademicYear::class;

    public function definition(): array
    {
        $startYear = fake()->numberBetween(2023, 2026);

        return [
            'name' => "{$startYear}/" . ($startYear + 1),
            'start_date' => "{$startYear}-09-01",
            'end_date' => ($startYear + 1) . '-07-31',
            'is_current' => false,
        ];
    }
}
