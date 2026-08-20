<?php

namespace Alle80\Griglia\Livewire;

use Alle80\Griglia\Themes;

class ThemedIngredientModal extends IngredientModal
{
    public string $theme = 'pixel';

    public function render()
    {
        return view('griglia::livewire.ingredient-modal', $this->viewData() + [
            't' => Themes::get($this->theme),
        ]);
    }
}
