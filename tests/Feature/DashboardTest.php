<?php

namespace Alle80\Griglia\Tests\Feature;

use Alle80\Griglia\Models\Checklist;
use Alle80\Griglia\Models\Todo;
use Alle80\Griglia\Settings\AppSettings;
use Alle80\Griglia\Tests\TestCase;

class DashboardTest extends TestCase
{
    public function test_dashboard_route_renders_the_board(): void
    {
        $user = $this->actingAsUser();
        $list = Checklist::create(['name' => 'dev', 'user_id' => $user->id]);
        Todo::create(['title' => 'Ship it', 'order' => 1, 'checklist_id' => $list->id]);

        $this->get(config('griglia.dashboard_route'))
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
