<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_admin_panel(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.index'))->assertOk()->assertSee('Admin Panel');
    }

    public function test_editor_cannot_access_admin_panel(): void
    {
        $this->actingAsEditor();

        $this->get(route('admin.index'))->assertForbidden();
    }

    public function test_viewer_cannot_access_admin_panel(): void
    {
        $this->actingAsViewer();

        $this->get(route('admin.index'))->assertForbidden();
    }

    public function test_admin_panel_shows_stat_counts(): void
    {
        $this->actingAsAdmin();

        $this->get(route('admin.index'))
            ->assertOk()
            ->assertSee('Components')
            ->assertSee('Users')
            ->assertSee('Tags')
            ->assertSee('Component Types');
    }
}
