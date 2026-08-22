<?php

namespace Alle80\Griglia;

use Alle80\Griglia\Console\AutoArchive;
use Alle80\Griglia\Console\DescribeImages;
use Alle80\Griglia\Console\ThemeExport;
use Alle80\Griglia\Console\ThemeImport;
use Alle80\Griglia\Console\AgentStatusImport;
use Alle80\Griglia\Console\ContextCommand;
use Alle80\Griglia\Console\GrigliaCheck;
use Alle80\Griglia\Console\DocsBuild;
use Alle80\Griglia\Console\DocsGenerate;
use Alle80\Griglia\Console\EmptyTrash;
use Alle80\Griglia\Console\SkillsImport;
use Alle80\Griglia\Console\Watch;
use Alle80\Griglia\Settings\AgentSettings;
use Alle80\Griglia\Settings\AppSettings;
use Alle80\Griglia\Settings\OptimizationSettings;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class GrigliaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/griglia.php', 'griglia');

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
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'griglia');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'griglia');
        \Illuminate\Support\Facades\Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'griglia');

        // <livewire:griglia::todo-list />, griglia::ingredient-modal, griglia::themed-todo-list,
        // griglia::themed-ingredient-modal, griglia::checklist-switcher, griglia::settings-page
        Livewire::addNamespace('griglia', classNamespace: 'Alle80\\Griglia\\Livewire');
        // Security: GrigliaAccess replaces `auth` on the package routes, so it must also run on Livewire's
        // /livewire/update requests (only "persistent" middleware is re-applied there)
        Livewire::addPersistentMiddleware([\Alle80\Griglia\Http\Middleware\GrigliaAccess::class, \Alle80\Griglia\Http\Middleware\GrigliaAdmin::class, \Alle80\Griglia\Http\Middleware\SetLocale::class]);

        if (config('griglia.register_routes', true)) {
            // After the host app's routes, so host routes keep precedence over package pages
            $this->app->booted(fn () => $this->loadRoutesFrom(__DIR__.'/../routes/web.php'));
        }

        if ($this->app->runningInConsole()) {
            $this->commands([GrigliaCheck::class, AgentStatusImport::class, ContextCommand::class, DocsBuild::class, DocsGenerate::class, EmptyTrash::class, SkillsImport::class, Watch::class, DescribeImages::class, AutoArchive::class, ThemeExport::class, ThemeImport::class]);

            $this->publishes([__DIR__.'/../config/griglia.php' => config_path('griglia.php')], 'griglia-config');
            $this->publishes([__DIR__.'/../AGENTS.md' => base_path('AGENTS.md')], 'griglia-agents');
            $this->publishes([__DIR__.'/../resources/views' => resource_path('views/vendor/griglia')], 'griglia-views');
            $this->publishes([__DIR__.'/../resources/lang' => $this->app->langPath('vendor/griglia')], 'griglia-lang');
            // The host-side helpers (they run on the machine where the agent runs, not in the container):
            // skills catalogue, agent context, token stats, agent status — see docs/agent/*.md
            $this->publishes([__DIR__.'/../scripts' => base_path('scripts')], 'griglia-scripts');
            // Also under Laravel's own tag: a default app republishes `laravel-assets` after every
            // composer update, so the precompiled build stays in sync without anyone remembering.
            $this->publishes([__DIR__.'/../public' => public_path('vendor/griglia')], ['griglia-assets', 'laravel-assets']);

            $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
                $schedule->command('griglia:auto-archive')->dailyAt('03:30')->withoutOverlapping();
            });
        }
    }
}
