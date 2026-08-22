<?php

namespace Alle80\Griglia\Database\Factories;

use Alle80\Griglia\Models\Checklist;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Checklist> */
class ChecklistFactory extends Factory
{
    protected $model = Checklist::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'user_id' => null,
        ];
    }
}
