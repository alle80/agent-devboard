<?php

namespace Alle80\Griglia\Database\Factories;

use Alle80\Griglia\Models\Question;
use Alle80\Griglia\Models\Todo;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Question> */
class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        return [
            'todo_id' => Todo::factory(),
            'question' => fake()->sentence().'?',
            'order' => 0,
        ];
    }
}
