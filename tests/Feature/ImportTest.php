<?php

namespace Tests\Feature;

use App\Enums\ComponentType;
use App\Models\Component;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsAdmin();
    }

    private function makeCsvFile(string $content, string $name = 'import.csv'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'csv_');
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, 'text/csv', null, true);
    }

    public function test_import_preview_with_valid_csv_shows_success_rows(): void
    {
        $csv = "id,name,type,description,lifecycle_stage,lifecycle_start_date,lifecycle_end_date,owner,tags\n";
        $csv .= ",Valid App,Application,,,,,,\n";

        $this->post(route('components.import.preview'), [
            'file' => $this->makeCsvFile($csv),
        ])->assertOk()->assertSee('Valid');
    }

    public function test_import_preview_with_invalid_type_shows_error_for_that_row(): void
    {
        $csv = "id,name,type,description,lifecycle_stage,lifecycle_start_date,lifecycle_end_date,owner,tags\n";
        $csv .= ",Bad App,InvalidType,,,,,,\n";

        $response = $this->post(route('components.import.preview'), [
            'file' => $this->makeCsvFile($csv),
        ]);

        $response->assertOk()->assertSee('Error');
    }

    public function test_import_confirm_creates_components_for_valid_rows(): void
    {
        $csv = "id,name,type,description,lifecycle_stage,lifecycle_start_date,lifecycle_end_date,owner,tags\n";
        $csv .= ",Valid App,Application,,,,,,\n";
        $csv .= ",Invalid App,BadType,,,,,,\n";

        $this->post(route('components.import.preview'), [
            'file' => $this->makeCsvFile($csv),
        ]);

        $this->post(route('components.import.store'))
            ->assertRedirect(route('components.index'));

        $this->assertDatabaseHas('components', ['name' => 'Valid App']);
        $this->assertDatabaseMissing('components', ['name' => 'Invalid App']);
    }

    public function test_import_confirm_skips_invalid_rows(): void
    {
        $csv = "id,name,type,description,lifecycle_stage,lifecycle_start_date,lifecycle_end_date,owner,tags\n";
        $csv .= ",Good App,Application,,,,,,\n";
        $csv .= ",,BadType,,,,,,\n";

        $this->post(route('components.import.preview'), [
            'file' => $this->makeCsvFile($csv),
        ]);

        $this->post(route('components.import.store'));

        $this->assertDatabaseCount('components', 1);
        $this->assertDatabaseHas('components', ['name' => 'Good App']);
    }

    public function test_unknown_csv_column_does_not_throw(): void
    {
        $csv = "id,name,type,unknown_column,description,lifecycle_stage,lifecycle_start_date,lifecycle_end_date,owner,tags\n";
        $csv .= ",My App,Application,some_value,,,,,\n";

        $this->post(route('components.import.preview'), [
            'file' => $this->makeCsvFile($csv),
        ])->assertOk();
    }

    public function test_viewer_cannot_import_components(): void
    {
        $this->actingAsViewer();

        $csv = "id,name,type,description,lifecycle_stage,lifecycle_start_date,lifecycle_end_date,owner,tags\n";
        $csv .= ",Viewer App,Application,,,,,,\n";

        $this->post(route('components.import.preview'), [
            'file' => $this->makeCsvFile($csv),
        ])->assertForbidden();
    }
}
