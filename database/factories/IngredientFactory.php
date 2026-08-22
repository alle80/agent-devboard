<?php

namespace Alle80\Griglia\Database\Factories;

use Alle80\Griglia\Models\Ingredient;
use Alle80\Griglia\Models\Todo;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Ingredient> */
class IngredientFactory extends Factory
{
    protected $model = Ingredient::class;

    public function definition(): array
    {
        return [
            'todo_id' => Todo::factory(),
            'name' => fake()->words(3, true),
            'order' => 0,
        ];
    }
}
