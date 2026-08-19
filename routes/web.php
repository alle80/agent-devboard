<?php

use Alle80\Devboard\Http\Middleware\RedirectToDefaultStyle;
use Alle80\Devboard\Http\Middleware\RememberStyle;
use Alle80\Devboard\Livewire\SettingsPage;
use Alle80\Devboard\Livewire\ThemedTodoList;
use Alle80\Devboard\Themes;
use Alle80\Devboard\Http\Controllers\ThemeAssetController;
use Alle80\Devboard\Http\Controllers\PushSubscriptionController;
use Alle80\Devboard\Http\Controllers\ServiceWorkerController;
use Alle80\Devboard\Http\Middleware\DevboardAccess;
use Alle80\Devboard\Http\Middleware\DevboardAdmin;
use Alle80\Devboard\Http\Middleware\OpenFromLink;
use Illuminate\Support\Facades\Route;

Route::middleware(array_merge(array_values(array_diff((array) config('devboard.middleware', ['web']), ['auth'])), [DevboardAccess::class, RememberStyle::class, OpenFromLink::class]))
    ->prefix((string) config('devboard.route_prefix', ''))
    ->group(function () {
        if (config('devboard.home_route', true)) {
            Route::get('/', ThemedTodoList::class)
                ->defaults('theme', Themes::default())
                ->middleware(RedirectToDefaultStyle::class)
                ->name('devboard.home');
        }

        Route::get('/settings', SettingsPage::class)->middleware(DevboardAdmin::class)->name('devboard.settings');
        Route::get('/context', \Alle80\Devboard\Livewire\ContextPage::class)->middleware(DevboardAdmin::class)->name('devboard.context');
        Route::get('/stats', \Alle80\Devboard\Livewire\StatsPage::class)->name('devboard.stats');
        Route::get('/agents', \Alle80\Devboard\Livewire\AgentsPage::class)->name('devboard.agents');

        // Web Push subscriptions of the logged-in user (+ a test notification)
        Route::post('/devboard/push-subscriptions', [PushSubscriptionController::class, 'store'])->middleware('throttle:'.config('devboard.rate_limits.push_subscriptions', '30,1').',devboard-push')->name('devboard.push.store');
        Route::delete('/devboard/push-subscriptions', [PushSubscriptionController::class, 'destroy'])->middleware('throttle:'.config('devboard.rate_limits.push_subscriptions', '30,1').',devboard-push')->name('devboard.push.destroy');
        Route::post('/devboard/notifications/test', [PushSubscriptionController::class, 'test'])->middleware('throttle:'.config('devboard.rate_limits.notifications_test', '5,1').',devboard-notify-test')->name('devboard.notifications.test');
        // Attachments through an authorised controller (the disk may be private)
        Route::get('/devboard/attachments/{attachment}', \Alle80\Devboard\Http\Controllers\AttachmentController::class)->whereNumber('attachment')->name('devboard.attachment');

        // Speech to text (server mode): short recording → AI SDK transcription
        Route::post('/devboard/transcribe', \Alle80\Devboard\Http\Controllers\TranscribeController::class)->middleware('throttle:'.config('devboard.rate_limits.transcribe', '10,1').',devboard-transcribe')->name('devboard.transcribe');

        if ($dash = config('devboard.dashboard_route')) {
            Route::get($dash, \Alle80\Devboard\Livewire\DashboardTodoList::class)->name('devboard.dashboard');
        }

        // Generic themes (built-in, config, registered and installed packs); unknown slugs → 404 in mount().
        // Registered after the host app's routes, so its own paths always win.
        Route::get('/{theme}', ThemedTodoList::class)
            ->where('theme', '[a-z0-9][a-z0-9-]*')
            ->name('devboard.theme');
    });

// Web Push service worker (root scope)
Route::get('/devboard-sw.js', ServiceWorkerController::class)->middleware('web')->name('devboard.sw');

// Files of installed theme packs (public: CSS/images/fonts only)
Route::get('/devboard-themes/{slug}/{path}', ThemeAssetController::class)
    ->where('path', '.*')->middleware('web')->name('devboard.theme-asset');
