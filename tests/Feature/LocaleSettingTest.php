<?php

namespace Alle80\Griglia\Tests\Feature;

use Alle80\Griglia\Livewire\SettingsPage;
use Alle80\Griglia\Settings\AppSettings;
use Alle80\Griglia\Support\Locale;
use Alle80\Griglia\Tests\TestCase;
use Livewire\Livewire;

/** Selettore della lingua della board in /settings (impostazione app.locale). */
class LocaleSettingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsUser();
    }

    public function test_available_languages_and_options(): void
    {
        $this->assertSame(['en', 'it'], Locale::available());
        $this->assertSame('Italiano', Locale::name('it'));
        $this->assertSame('ZZ', Locale::name('zz')); // lingua sconosciuta: resta il codice

        $options = Locale::options();
        $this->assertSame(['', 'en', 'it'], array_keys($options));
        $this->assertStringContainsString('EN', $options['']);
    }

    public function test_no_choice_leaves_the_application_language_alone(): void
    {
        $this->assertSame('', app(AppSettings::class)->locale);
        $this->assertSame('', Locale::chosen());

        app()->setLocale('it');
        Locale::apply();
        $this->assertSame('it', app()->getLocale());
        $this->assertSame('it', Locale::current());
    }

    public function test_chosen_language_dresses_every_board_page(): void
    {
        $settings = app(AppSettings::class);
        $settings->locale = 'it';
        $settings->save();

        $this->get('/slate')->assertOk()->assertSee('Cerca…');
        $this->get('/settings')->assertOk()->assertSee('Lingua della board');
    }

    public function test_an_unknown_language_falls_back_to_the_application_one(): void
    {
        $settings = app(AppSettings::class);
        $settings->locale = 'xx';
        $settings->save();

        $this->assertSame('', Locale::chosen());
        $this->get('/slate')->assertOk()->assertSee('Search…');
    }

    public function test_the_settings_page_switches_language_and_refuses_unknown_ones(): void
    {
        $page = Livewire::test(SettingsPage::class)->assertSee('Board language');

        $page->set('values.app.locale', 'it');
        $this->assertSame('it', app(AppSettings::class)->refresh()->locale);
        $page->assertSee('Lingua della board'); // la pagina si ridisegna già tradotta

        $page->set('values.app.locale', 'bogus');
        $this->assertSame('it', app(AppSettings::class)->refresh()->locale);
    }
}
