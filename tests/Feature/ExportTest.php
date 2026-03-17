<?php

namespace Tests\Feature;

use App\Enums\ComponentType;
use App\Enums\FactFieldType;
use App\Models\Component;
use App\Models\FactDefinition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
    }

    public function test_export_returns_csv_content_type(): void
    {
        $this->get(route('components.export'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_export_includes_fact_definition_names_as_headers(): void
    {
        FactDefinition::factory()->create(['name' => 'CPU Cores', 'field_type' => FactFieldType::Number->value]);

        $response = $this->get(route('components.export'));

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('CPU Cores', $content);
    }

    public function test_export_with_type_filter_returns_only_matching_rows(): void
    {
        Component::factory()->create(['name' => 'App One', 'type' => ComponentType::Application->value]);
        Component::factory()->create(['name' => 'Server One', 'type' => ComponentType::ItComponent->value]);

        $response = $this->get(route('components.export', ['type' => ComponentType::Application->value]));

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('App One', $content);
        $this->assertStringNotContainsString('Server One', $content);
    }

    public function test_export_includes_all_components_without_filter(): void
    {
        Component::factory()->create(['name' => 'First App']);
        Component::factory()->create(['name' => 'Second App']);

        $response = $this->get(route('components.export'));

        $content = $response->streamedContent();
        $this->assertStringContainsString('First App', $content);
        $this->assertStringContainsString('Second App', $content);
    }
}
