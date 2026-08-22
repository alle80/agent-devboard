<?php

use Alle80\Griglia\Http\Controllers\AttachmentController;
use Alle80\Griglia\Http\Controllers\PushSubscriptionController;
use Alle80\Griglia\Http\Controllers\ServiceWorkerController;
use Alle80\Griglia\Http\Controllers\ThemeAssetController;
use Alle80\Griglia\Http\Controllers\TranscribeController;
use Alle80\Griglia\Http\Middleware\GrigliaAccess;
use Alle80\Griglia\Http\Middleware\GrigliaAdmin;
use Alle80\Griglia\Http\Middleware\OpenFromLink;
use Alle80\Griglia\Http\Middleware\RememberStyle;
use Alle80\Griglia\Http\Middleware\SetLocale;
use Alle80\Griglia\Livewire\AgentsPage;
use Alle80\Griglia\Livewire\ContextPage;
use Alle80\Griglia\Livewire\DashboardTodoList;
use Alle80\Griglia\Livewire\PlanPage;
use Alle80\Griglia\Livewire\PlansPage;
use Alle80\Griglia\Livewire\SettingsPage;
use Alle80\Griglia\Livewire\StatsPage;
use Alle80\Griglia\Livewire\ThemedTodoList;
use Illuminate\Support\Facades\Route;

Route::middleware(array_merge(array_values(array_diff((array) config('griglia.middleware', ['web']), ['auth'])), [GrigliaAccess::class, SetLocale::class, RememberStyle::class, OpenFromLink::class]))
    ->prefix((string) config('griglia.route_prefix', ''))
    ->group(function () {
        if (config('griglia.home_route', true)) {
            Route::get('/', ThemedTodoList::class)
                ->name('griglia.home');
        }

        Route::get('/settings', SettingsPage::class)->middleware(GrigliaAdmin::class)->name('griglia.settings');
        Route::get('/context', ContextPage::class)->middleware(GrigliaAdmin::class)->name('griglia.context');
        Route::get('/plans', PlansPage::class)->name('griglia.plans.index');
        Route::get('/plans/new', PlanPage::class)->name('griglia.plans.create');
        Route::get('/plans/{list}/edit', PlanPage::class)->whereNumber('list')->name('griglia.plans.edit');
        Route::get('/stats', StatsPage::class)->name('griglia.stats');
        Route::get('/agents', AgentsPage::class)->name('griglia.agents');

        // Web Push subscriptions of the logged-in user (+ a test notification)
        Route::post('/griglia/push-subscriptions', [PushSubscriptionController::class, 'store'])->middleware('throttle:'.config('griglia.rate_limits.push_subscriptions', '30,1').',griglia-push')->name('griglia.push.store');
        Route::delete('/griglia/push-subscriptions', [PushSubscriptionController::class, 'destroy'])->middleware('throttle:'.config('griglia.rate_limits.push_subscriptions', '30,1').',griglia-push')->name('griglia.push.destroy');
        Route::post('/griglia/notifications/test', [PushSubscriptionController::class, 'test'])->middleware('throttle:'.config('griglia.rate_limits.notifications_test', '5,1').',griglia-notify-test')->name('griglia.notifications.test');
        // Attachments through an authorised controller (the disk may be private)
        Route::get('/griglia/attachments/{attachment}', AttachmentController::class)->whereNumber('attachment')->name('griglia.attachment');

        // Speech to text (server mode): short recording → AI SDK transcription
        Route::post('/griglia/transcribe', TranscribeController::class)->middleware('throttle:'.config('griglia.rate_limits.transcribe', '10,1').',griglia-transcribe')->name('griglia.transcribe');

        if ($dash = config('griglia.dashboard_route')) {
            Route::get($dash, DashboardTodoList::class)->name('griglia.dashboard');
        }

    });

// Web Push service worker (root scope)
Route::get('/griglia-sw.js', ServiceWorkerController::class)->middleware('web')->name('griglia.sw');

// Files of installed theme packs (public: CSS/images/fonts only)
Route::get('/griglia-themes/{slug}/{path}', ThemeAssetController::class)
    ->where('path', '.*')->middleware('web')->name('griglia.theme-asset');
