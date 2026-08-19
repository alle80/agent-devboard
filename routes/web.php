<?php

use Alle80\Devboard\Http\Middleware\RedirectToDefaultStyle;
use Alle80\Devboard\Http\Middleware\RememberStyle;
use Alle80\Devboard\Livewire\SettingsPage;
use Alle80\Devboard\Livewire\ThemedTodoList;
use Alle80\Devboard\Themes;
use Alle80\Devboard\Http\Controllers\ThemeAssetController;
use Illuminate\Support\Facades\Route;

Route::middleware(array_merge((array) config('devboard.middleware', ['web', 'auth']), [RememberStyle::class]))
    ->prefix((string) config('devboard.route_prefix', ''))
    ->group(function () {
        if (config('devboard.home_route', true)) {
            Route::get('/', ThemedTodoList::class)
                ->defaults('theme', Themes::default())
                ->middleware(RedirectToDefaultStyle::class)
                ->name('devboard.home');
        }

        Route::get('/settings', SettingsPage::class)->name('devboard.settings');

        if ($dash = config('devboard.dashboard_route')) {
            Route::get($dash, \Alle80\Devboard\Livewire\DashboardTodoList::class)->name('devboard.dashboard');
        }

        // Generic themes (built-in, config, registered and installed packs); unknown slugs → 404 in mount().
        // Registered after the host app's routes, so its own paths always win.
        Route::get('/{theme}', ThemedTodoList::class)
            ->where('theme', '[a-z0-9][a-z0-9-]*')
            ->name('devboard.theme');
    });

// Files of installed theme packs (public: CSS/images/fonts only)
Route::get('/devboard-themes/{slug}/{path}', ThemeAssetController::class)
    ->where('path', '.*')->middleware('web')->name('devboard.theme-asset');
