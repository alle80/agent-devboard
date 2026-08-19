<?php

namespace Alle80\Devboard\Tests\Feature;

use Alle80\Devboard\Models\Checklist;
use Alle80\Devboard\Models\Todo;
use Alle80\Devboard\Settings\AppSettings;
use Alle80\Devboard\Tests\TestCase;

class DashboardTest extends TestCase
{
    public function test_dashboard_route_renders_the_board(): void
    {
        $user = $this->actingAsUser();
        $list = Checklist::create(['name' => 'dev', 'user_id' => $user->id]);
        Todo::create(['title' => 'Ship it', 'order' => 1, 'checklist_id' => $list->id]);

        $this->get(config('devboard.dashboard_route'))
            ->assertOk()
            ->assertSee('Ship it')
            ->assertSee('max-w-5xl', false); // wider desktop container
    }

    public function test_tab_side_setting_exists_with_default(): void
    {
        $this->assertContains(app(AppSettings::class)->tab_side, ['right', 'left']);
        $this->assertArrayHasKey('tab_side', AppSettings::fields());
    }
}
