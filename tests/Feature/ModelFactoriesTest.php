<?php

namespace Alle80\Griglia\Tests\Feature;

use Alle80\Griglia\Models\Checklist;
use Alle80\Griglia\Models\Ingredient;
use Alle80\Griglia\Models\Question;
use Alle80\Griglia\Models\Todo;
use Alle80\Griglia\Tests\TestCase;

class ModelFactoriesTest extends TestCase
{
    public function test_package_model_factories_create_valid_related_records(): void
    {
        $checklist = Checklist::factory()->create();
        $todo = Todo::factory()->for($checklist)->create();
        $ingredient = Ingredient::factory()->for($todo)->create();
        $question = Question::factory()->for($todo)->create();

        $this->assertTrue($todo->checklist->is($checklist));
        $this->assertTrue($ingredient->todo->is($todo));
        $this->assertTrue($question->todo->is($todo));
    }
}
