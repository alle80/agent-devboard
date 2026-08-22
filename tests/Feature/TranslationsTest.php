<?php

namespace Alle80\Griglia\Tests\Feature;

use Alle80\Griglia\Livewire\ThemedTodoList;
use Alle80\Griglia\Tests\TestCase;
use Livewire\Livewire;

class TranslationsTest extends TestCase
{
    protected function flatten(array $a, string $prefix = ''): array
    {
        $out = [];
        foreach ($a as $k => $v) {
            if (is_array($v)) {
                $out += $this->flatten($v, $prefix.$k.'.');
            } else {
                $out[$prefix.$k] = $v;
            }
        }

        return $out;
    }

    public function test_english_and_italian_have_the_same_keys(): void
    {
        $en = $this->flatten(require __DIR__.'/../../resources/lang/en/t.php');
        $it = $this->flatten(require __DIR__.'/../../resources/lang/it/t.php');

        $this->assertSame([], array_diff_key($en, $it), 'keys missing in it');
        $this->assertSame([], array_diff_key($it, $en), 'keys missing in en');
        $this->assertNotEmpty($en);
    }

    public function test_translations_are_used_by_the_ui(): void
    {
        $this->actingAsUser();
        $this->get('/')->assertOk()->assertSee('Search…')->assertSee('add a task');
        Livewire::test(ThemedTodoList::class)->call('startInsert', 1)->assertSee('write here…');

        // the texts of the theme (add button, placeholders, counter) follow the board language too (task 516)
        app()->setLocale('it');
        $this->get('/')->assertOk()->assertSee('Cerca…')->assertSee('aggiungi')->assertDontSee('add a task');
        Livewire::test(ThemedTodoList::class)->call('startInsert', 1)->assertSee('scrivi qui…')->assertDontSee('write here…');
    }
}
