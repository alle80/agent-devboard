<?php

namespace Alle80\Devboard;

use Alle80\Devboard\Console\AutoArchive;
use Alle80\Devboard\Console\DescribeImages;
use Alle80\Devboard\Console\ThemeExport;
use Alle80\Devboard\Console\ThemeImport;
use Alle80\Devboard\Console\ContextCommand;
use Alle80\Devboard\Console\DevboardCheck;
use Alle80\Devboard\Console\SkillsImport;
use Alle80\Devboard\Console\Watch;
use Alle80\Devboard\Settings\AgentSettings;
use Alle80\Devboard\Settings\AppSettings;
use Alle80\Devboard\Settings\OptimizationSettings;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class DevboardServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/devboard.php', 'devboard');

        // spatie/laravel-settings: our settings classes and their value migrations
        $this->app->booting(function () {
            $config = $this->app['config'];
            $config->set('settings.settings', array_values(array_unique(array_merge((array) $config->get('settings.settings', []), [AgentSettings::class, AppSettings::class, OptimizationSettings::class]))));
            $config->set('settings.migrations_paths', array_values(array_unique(array_merge((array) $config->get('settings.migrations_paths', []), [__DIR__.'/../database/settings']))));
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'devboard');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'devboard');
        Blade::componentNamespace('Alle80\\Devboard\\View\\Components', 'devboard');
        Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'devboard');

        // <livewire:devboard::todo-list />, devboard::ingredient-modal, devboard::themed-todo-list,
        // devboard::themed-ingredient-modal, devboard::checklist-switcher, devboard::settings-page
        Livewire::addNamespace('devboard', classNamespace: 'Alle80\\Devboard\\Livewire');

        if (config('devboard.register_routes', true)) {
            // After the host app's routes, so its own routes (e.g. dedicated styles) win over /{theme}
            $this->app->booted(fn () => $this->loadRoutesFrom(__DIR__.'/../routes/web.php'));
        }

        if ($this->app->runningInConsole()) {
            $this->commands([DevboardCheck::class, ContextCommand::class, SkillsImport::class, Watch::class, DescribeImages::class, AutoArchive::class, ThemeExport::class, ThemeImport::class]);

            $this->publishes([__DIR__.'/../config/devboard.php' => config_path('devboard.php')], 'devboard-config');
            $this->publishes([__DIR__.'/../AGENTS.md' => base_path('AGENTS.md')], 'devboard-agents');
            $this->publishes([__DIR__.'/../resources/views' => resource_path('views/vendor/devboard')], 'devboard-views');
            $this->publishes([__DIR__.'/../resources/lang' => $this->app->langPath('vendor/devboard')], 'devboard-lang');
            $this->publishes([__DIR__.'/../public' => public_path('vendor/devboard')], 'devboard-assets');

            $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
                $schedule->command('devboard:auto-archive')->dailyAt('03:30');
            });
        }
    }
}
