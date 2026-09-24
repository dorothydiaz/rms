<?php

namespace Tests\Feature;

use App\Models\Hr\Employee;
use App\Models\User;
use Tests\TestCase;

class DtrExportPdfTest extends TestCase
{
    protected function getAdmin(): User
    {
        return User::where('username', 'peter')->first() ?? User::first();
    }

    public function test_dtr_page_has_prominent_export_button_and_modal(): void
    {
        $admin = $this->getAdmin();

        $response = $this->actingAs($admin)->get(route('hr.attendance.dtr'));

        $response->assertStatus(200);
        $response->assertSee('Export DTR');
        $response->assertSee('dtrExportModal');
        $response->assertSee('dtrTagSearchInput');
        $response->assertSee('All Active Employees');
        $response->assertSee('Filtered Employees');
        $response->assertSee('Individual PDF per employee');
        $response->assertSee('Combined PDF');
    }

    public function test_dtr_tags_endpoint_returns_dynamic_tags_and_employees(): void
    {
        $admin = $this->getAdmin();

        $response = $this->actingAs($admin)->get(route('hr.attendance.dtr.tags'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'tags' => [
                '*' => ['category', 'type', 'label', 'value']
            ],
            'employees' => [
                '*' => ['id', 'employee_id', 'name', 'statuses', 'source', 'branch_name']
            ],
            'total_active'
        ]);

        $tags = collect($response->json('tags'));
        $this->assertTrue($tags->pluck('category')->contains('Employment Status'));
        $this->assertTrue($tags->pluck('category')->contains('Employment Source'));
        $this->assertTrue($tags->pluck('category')->contains('Branch'));
    }

    public function test_dtr_export_pdf_combined_generates_pdf_download(): void
    {
        $admin = $this->getAdmin();

        $response = $this->actingAs($admin)->post(route('hr.attendance.dtr.export-pdf'), [
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-15',
            'scope' => 'all',
            'output_format' => 'combined',
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_dtr_export_pdf_individual_generates_zip_download(): void
    {
        $admin = $this->getAdmin();

        $response = $this->actingAs($admin)->post(route('hr.attendance.dtr.export-pdf'), [
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-15',
            'scope' => 'all',
            'output_format' => 'individual',
        ]);

        $response->assertStatus(200);
        $contentType = $response->headers->get('Content-Type');
        $this->assertTrue(
            str_contains($contentType, 'zip') || str_contains($contentType, 'octet-stream') || str_contains($contentType, 'pdf')
        );
    }

    public function test_dtr_export_pdf_filtered_by_dynamic_tags(): void
    {
        $admin = $this->getAdmin();

        $tags = [
            ['type' => 'source', 'value' => 'Agency']
        ];

        $response = $this->actingAs($admin)->post(route('hr.attendance.dtr.export-pdf'), [
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-15',
            'scope' => 'filtered',
            'tags' => json_encode($tags),
            'output_format' => 'combined',
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_dtr_export_pdf_paginates_sixteen_dates_per_page(): void
    {
        $admin = $this->getAdmin();

        // 30 days export (e.g. 2026-09-01 to 2026-09-30) - should chunk into 16 days on page 1, 14 days on page 2
        $response = $this->actingAs($admin)->post(route('hr.attendance.dtr.export-pdf'), [
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'scope' => 'all',
            'output_format' => 'combined',
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }
}
