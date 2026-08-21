<?php

use Alle80\Griglia\Http\Middleware\RedirectToDefaultStyle;
use Alle80\Griglia\Http\Middleware\RememberStyle;
use Alle80\Griglia\Livewire\SettingsPage;
use Alle80\Griglia\Livewire\ThemedTodoList;
use Alle80\Griglia\Themes;
use Alle80\Griglia\Http\Controllers\ThemeAssetController;
use Alle80\Griglia\Http\Controllers\PushSubscriptionController;
use Alle80\Griglia\Http\Controllers\ServiceWorkerController;
use Alle80\Griglia\Http\Middleware\GrigliaAccess;
use Alle80\Griglia\Http\Middleware\GrigliaAdmin;
use Alle80\Griglia\Http\Middleware\OpenFromLink;
use Alle80\Griglia\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

Route::middleware(array_merge(array_values(array_diff((array) config('griglia.middleware', ['web']), ['auth'])), [GrigliaAccess::class, SetLocale::class, RememberStyle::class, OpenFromLink::class]))
    ->prefix((string) config('griglia.route_prefix', ''))
    ->group(function () {
        if (config('griglia.home_route', true)) {
            Route::get('/', ThemedTodoList::class)
                ->defaults('theme', Themes::default())
                ->middleware(RedirectToDefaultStyle::class)
                ->name('griglia.home');
        }

        Route::get('/settings', SettingsPage::class)->middleware(GrigliaAdmin::class)->name('griglia.settings');
        Route::get('/context', \Alle80\Griglia\Livewire\ContextPage::class)->middleware(GrigliaAdmin::class)->name('griglia.context');
        Route::get('/plans/new', \Alle80\Griglia\Livewire\PlanPage::class)->name('griglia.plans.create');
        Route::get('/plans/{list}/edit', \Alle80\Griglia\Livewire\PlanPage::class)->whereNumber('list')->name('griglia.plans.edit');
        Route::get('/stats', \Alle80\Griglia\Livewire\StatsPage::class)->name('griglia.stats');
        Route::get('/agents', \Alle80\Griglia\Livewire\AgentsPage::class)->name('griglia.agents');

        // Web Push subscriptions of the logged-in user (+ a test notification)
        Route::post('/griglia/push-subscriptions', [PushSubscriptionController::class, 'store'])->middleware('throttle:'.config('griglia.rate_limits.push_subscriptions', '30,1').',griglia-push')->name('griglia.push.store');
        Route::delete('/griglia/push-subscriptions', [PushSubscriptionController::class, 'destroy'])->middleware('throttle:'.config('griglia.rate_limits.push_subscriptions', '30,1').',griglia-push')->name('griglia.push.destroy');
        Route::post('/griglia/notifications/test', [PushSubscriptionController::class, 'test'])->middleware('throttle:'.config('griglia.rate_limits.notifications_test', '5,1').',griglia-notify-test')->name('griglia.notifications.test');
        // Attachments through an authorised controller (the disk may be private)
        Route::get('/griglia/attachments/{attachment}', \Alle80\Griglia\Http\Controllers\AttachmentController::class)->whereNumber('attachment')->name('griglia.attachment');

        // Speech to text (server mode): short recording → AI SDK transcription
        Route::post('/griglia/transcribe', \Alle80\Griglia\Http\Controllers\TranscribeController::class)->middleware('throttle:'.config('griglia.rate_limits.transcribe', '10,1').',griglia-transcribe')->name('griglia.transcribe');

        if ($dash = config('griglia.dashboard_route')) {
            Route::get($dash, \Alle80\Griglia\Livewire\DashboardTodoList::class)->name('griglia.dashboard');
        }

        // Generic themes (built-in, config, registered and installed packs); unknown slugs → 404 in mount().
        // Registered after the host app's routes, so its own paths always win.
        Route::get('/{theme}', ThemedTodoList::class)
            ->where('theme', '[a-z0-9][a-z0-9-]*')
            ->name('griglia.theme');
    });

// Web Push service worker (root scope)
Route::get('/griglia-sw.js', ServiceWorkerController::class)->middleware('web')->name('griglia.sw');

// Files of installed theme packs (public: CSS/images/fonts only)
Route::get('/griglia-themes/{slug}/{path}', ThemeAssetController::class)
    ->where('path', '.*')->middleware('web')->name('griglia.theme-asset');
