<?php

namespace Alle80\Devboard\Livewire;

use Alle80\Devboard\Themes;

class ThemedIngredientModal extends IngredientModal
{
    public string $theme = 'pixel';

    public function render()
    {
        return view('devboard::livewire.ingredient-modal', $this->viewData() + [
            't' => Themes::get($this->theme),
        ]);
    }
}
