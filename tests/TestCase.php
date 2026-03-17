<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    protected function actingAsAdmin(): static
    {
        return $this->actingAs(User::factory()->admin()->create());
    }

    protected function actingAsEditor(): static
    {
        return $this->actingAs(User::factory()->editor()->create());
    }

    protected function actingAsViewer(): static
    {
        return $this->actingAs(User::factory()->viewer()->create());
    }
}
