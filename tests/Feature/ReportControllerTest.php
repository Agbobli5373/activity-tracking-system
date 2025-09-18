<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create([
            'role' => 'admin',
            'department' => 'IT Support'
        ]);
        
        $this->actingAs($this->user);
    }

    public function test_reports_index_page_loads_successfully()
    {
        $response = $this->get(route('reports.index'));

        $response->assertStatus(200);
        $response->assertViewIs('reports.index');
        $response->assertViewHas('filterOptions');
    }

    public function test_generate_report_returns_valid_data()
    {
        // Create test activities
        Activity::factory()->count(5)->create([
            'created_by' => $this->user->id,
            'status' => 'done',
            'priority' => 'high',
            'created_at' => Carbon::now()->subDays(2)
        ]);

        Activity::factory()->count(3)->create([
            'created_by' => $this->user->id,
            'status' => 'pending',
            'priority' => 'medium',
            'created_at' => Carbon::now()->subDay()
        ]);

        $response = $this->postJson(route('reports.generate'), [
            'start_date' => Carbon::now()->subWeek()->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'activities',
                'statistics' => [
                    'total_activities',
                    'completed_activities',
                    'pending_activities',
                    'completion_rate',
                    'priority_breakdown',
                    'user_statistics',
                    'daily_statistics'
                ],
                'period',
                'filters'
            ]
        ]);

        $data = $response->json('data');
        $this->assertEquals(8, $data['statistics']['total_activities']);
        $this->assertEquals(5, $data['statistics']['completed_activities']);
        $this->assertEquals(3, $data['statistics']['pending_activities']);
        $this->assertEquals(62.5, $data['statistics']['completion_rate']);
    }

    public function test_generate_report_with_filters()
    {
        // Create activities with different statuses
        Activity::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'done',
            'created_at' => Carbon::now()->subDay()
        ]);

        Activity::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'pending',
            'created_at' => Carbon::now()->subDay()
        ]);

        $response = $this->postJson(route('reports.generate'), [
            'start_date' => Carbon::now()->subWeek()->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
            'status' => 'done'
        ]);

        $response->assertStatus(200);
        
        $data = $response->json('data');
        $this->assertEquals(1, $data['statistics']['total_activities']);
        $this->assertEquals(1, $data['statistics']['completed_activities']);
        $this->assertEquals(0, $data['statistics']['pending_activities']);
    }

    public function test_export_csv_downloads_file()
    {
        Activity::factory()->create([
            'created_by' => $this->user->id,
            'name' => 'Test Activity',
            'description' => 'Test Description',
            'status' => 'done',
            'created_at' => Carbon::now()->subDay()
        ]);

        $response = $this->post(route('reports.export'), [
            'format' => 'csv',
            'start_date' => Carbon::now()->subWeek()->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }

    public function test_export_pdf_downloads_file()
    {
        Activity::factory()->create([
            'created_by' => $this->user->id,
            'name' => 'Test Activity',
            'description' => 'Test Description',
            'status' => 'done',
            'created_at' => Carbon::now()->subDay()
        ]);

        $response = $this->post(route('reports.export'), [
            'format' => 'pdf',
            'start_date' => Carbon::now()->subWeek()->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }

    public function test_export_excel_downloads_file()
    {
        Activity::factory()->create([
            'created_by' => $this->user->id,
            'name' => 'Test Activity',
            'description' => 'Test Description',
            'status' => 'done',
            'created_at' => Carbon::now()->subDay()
        ]);

        $response = $this->post(route('reports.export'), [
            'format' => 'excel',
            'start_date' => Carbon::now()->subWeek()->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
        ]);

        $response->assertStatus(200);
        $this->assertStringContainsString('spreadsheet', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }

    public function test_trends_endpoint_returns_chart_data()
    {
        Activity::factory()->count(3)->create([
            'created_by' => $this->user->id,
            'created_at' => Carbon::now()->subDays(2)
        ]);

        Activity::factory()->count(2)->create([
            'created_by' => $this->user->id,
            'created_at' => Carbon::now()->subDay()
        ]);

        $response = $this->getJson(route('reports.trends') . '?' . http_build_query([
            'start_date' => Carbon::now()->subWeek()->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
            'group_by' => 'day'
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'labels',
                'datasets' => [
                    '*' => [
                        'label',
                        'data',
                        'backgroundColor',
                        'borderColor'
                    ]
                ]
            ]
        ]);
    }

    public function test_department_stats_returns_data()
    {
        Activity::factory()->count(2)->create([
            'created_by' => $this->user->id,
            'status' => 'done',
            'created_at' => Carbon::now()->subDay()
        ]);

        Activity::factory()->create([
            'created_by' => $this->user->id,
            'status' => 'pending',
            'created_at' => Carbon::now()->subDay()
        ]);

        $response = $this->getJson(route('reports.department-stats') . '?' . http_build_query([
            'start_date' => Carbon::now()->subWeek()->format('Y-m-d'),
            'end_date' => Carbon::now()->format('Y-m-d'),
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'department',
                    'total_activities',
                    'completed_activities',
                    'pending_activities',
                    'completion_rate'
                ]
            ]
        ]);
    }

    public function test_summary_returns_period_statistics()
    {
        Activity::factory()->count(5)->create([
            'created_by' => $this->user->id,
            'status' => 'done',
            'created_at' => Carbon::now()->subDays(2)
        ]);

        $response = $this->getJson(route('reports.summary') . '?' . http_build_query([
            'period' => 'week'
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'statistics',
                'period'
            ]
        ]);
    }

    public function test_report_validation_fails_with_invalid_dates()
    {
        $response = $this->postJson(route('reports.generate'), [
            'start_date' => Carbon::now()->format('Y-m-d'),
            'end_date' => Carbon::now()->subDay()->format('Y-m-d'), // End date before start date
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['end_date']);
    }

    public function test_report_validation_fails_with_future_dates()
    {
        $response = $this->postJson(route('reports.generate'), [
            'start_date' => Carbon::now()->addDay()->format('Y-m-d'), // Future date
            'end_date' => Carbon::now()->addWeek()->format('Y-m-d'),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['end_date']);
    }
}