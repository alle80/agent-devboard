<?php

namespace Alle80\Griglia\Tests;

use Alle80\Griglia\Tests\Support\User;
use Alle80\Griglia\GrigliaServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\LaravelSettings\LaravelSettingsServiceProvider;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Alle80\Griglia\Mode::reset(); // static cache must not leak between tests
        $this->withoutVite();

        // Users table of the host app + package migrations (tables + settings defaults)
        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->artisan('migrate')->run();
    }

    protected function getPackageProviders($app): array
    {
        return [LivewireServiceProvider::class, LaravelSettingsServiceProvider::class, \NotificationChannels\WebPush\WebPushServiceProvider::class, GrigliaServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]);
        $app['config']->set('app.key', 'base64:2fl+Ktvkfl+Fuz4Qp/A75G2RTiWVA/ZoKZvp6fiiM10=');
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('griglia.user_model', User::class);
        $app['config']->set('griglia.agent_list', 'dev');
        $app['config']->set('webpush.database_connection', 'testing');
        $app['config']->set('filesystems.disks.public', ['driver' => 'local', 'root' => storage_path('framework/testing/public'), 'url' => 'http://localhost/storage']);
    }

    /** A logged-in user with its default list. */
    protected function actingAsUser(string $email = 'user@example.com'): User
    {
        $user = User::create(['name' => 'User', 'email' => $email, 'password' => bcrypt('secret')]);
        $this->actingAs($user);

        return $user;
    }
}
