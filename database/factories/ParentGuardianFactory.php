<?php

namespace Database\Factories;

use App\Models\ParentGuardian;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ParentGuardianFactory extends Factory
{
    protected $model = ParentGuardian::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(['role' => 'parent']),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
        ];
    }
}
