<?php

namespace Alle80\Griglia\Database\Factories;

use Alle80\Griglia\Models\Checklist;
use Alle80\Griglia\Models\Todo;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Todo> */
class TodoFactory extends Factory
{
    protected $model = Todo::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'order' => 0,
            'checklist_id' => Checklist::factory(),
        ];
    }
}
