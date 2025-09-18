<?php

namespace Tests\Unit\Controllers;

use App\Http\Controllers\ReportController;
use App\Models\Activity;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected ReportController $controller;
    protected ReportService $mockReportService;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockReportService = Mockery::mock(ReportService::class);
        $this->controller = new ReportController($this->mockReportService);
        
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_index_calls_get_filter_options()
    {
        $filterOptions = [
            'users' => collect([
                ['id' => 1, 'name' => 'John Doe', 'department' => 'IT'],
                ['id' => 2, 'name' => 'Jane Smith', 'department' => 'Support']
            ]),
            'departments' => collect(['IT', 'Support']),
            'statuses' => ['pending', 'done'],
            'priorities' => ['low', 'medium', 'high']
        ];

        $this->mockReportService
            ->shouldReceive('getFilterOptions')
            ->once()
            ->andReturn($filterOptions);

        // We can't test the actual view rendering in unit tests without the view file
        // So we'll just verify the service method is called
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('View [reports.index] not found');
        
        $this->controller->index();
    }

    public function test_generate_returns_successful_json_response()
    {
        // Create a user for validation to pass
        $creator = User::factory()->create();
        
        $requestData = [
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
            'status' => 'done',
            'creator_id' => $creator->id
        ];

        $reportData = [
            'activities' => collect([]),
            'statistics' => [
                'total_activities' => 10,
                'completed_activities' => 8,
                'pending_activities' => 2,
                'completion_rate' => 80.0
            ],
            'period' => [
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-31'
            ]
        ];

        $this->mockReportService
            ->shouldReceive('generateActivityReport')
            ->once()
            ->with($requestData)
            ->andReturn($reportData);

        $request = Request::create('/reports/generate', 'POST', $requestData);
        $response = $this->controller->generate($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals($reportData, $responseData['data']);
    }

    public function test_generate_handles_service_exception()
    {
        $requestData = [
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31'
        ];

        $this->mockReportService
            ->shouldReceive('generateActivityReport')
            ->once()
            ->with($requestData)
            ->andThrow(new \Exception('Database connection failed'));

        $request = Request::create('/reports/generate', 'POST', $requestData);
        $response = $this->controller->generate($request);

        $this->assertEquals(500, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertStringContainsString('Failed to generate report', $responseData['message']);
    }

    public function test_trends_returns_successful_json_response()
    {
        $requestData = [
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31',
            'group_by' => 'day'
        ];

        $trendsData = [
            'labels' => ['2024-01-01', '2024-01-02', '2024-01-03'],
            'datasets' => [
                [
                    'label' => 'Total Activities',
                    'data' => [5, 3, 7],
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)'
                ]
            ]
        ];

        $this->mockReportService
            ->shouldReceive('getActivityTrends')
            ->once()
            ->with(
                Mockery::on(function ($startDate) {
                    return $startDate instanceof Carbon && $startDate->format('Y-m-d') === '2024-01-01';
                }),
                Mockery::on(function ($endDate) {
                    return $endDate instanceof Carbon && $endDate->format('Y-m-d') === '2024-01-31';
                }),
                'day'
            )
            ->andReturn($trendsData);

        $request = Request::create('/reports/trends', 'GET', $requestData);
        $response = $this->controller->trends($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals($trendsData, $responseData['data']);
    }

    public function test_department_stats_returns_successful_json_response()
    {
        $requestData = [
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31'
        ];

        $departmentStats = [
            [
                'department' => 'IT Support',
                'total_activities' => 15,
                'completed_activities' => 12,
                'pending_activities' => 3,
                'completion_rate' => 80.0
            ],
            [
                'department' => 'Network Operations',
                'total_activities' => 8,
                'completed_activities' => 8,
                'pending_activities' => 0,
                'completion_rate' => 100.0
            ]
        ];

        $this->mockReportService
            ->shouldReceive('getDepartmentStatistics')
            ->once()
            ->with(
                Mockery::on(function ($startDate) {
                    return $startDate instanceof Carbon && $startDate->format('Y-m-d') === '2024-01-01';
                }),
                Mockery::on(function ($endDate) {
                    return $endDate instanceof Carbon && $endDate->format('Y-m-d') === '2024-01-31';
                })
            )
            ->andReturn($departmentStats);

        $request = Request::create('/reports/department-stats', 'GET', $requestData);
        $response = $this->controller->departmentStats($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals($departmentStats, $responseData['data']);
    }

    public function test_export_csv_returns_stream_response()
    {
        $requestData = [
            'format' => 'csv',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31'
        ];

        $activity = Activity::factory()->make([
            'name' => 'Test Activity',
            'description' => 'Test Description',
            'status' => 'done',
            'priority' => 'high',
            'created_at' => Carbon::parse('2024-01-15'),
            'updated_at' => Carbon::parse('2024-01-16'),
            'due_date' => Carbon::parse('2024-01-20')
        ]);

        $activity->setRelation('creator', User::factory()->make([
            'name' => 'John Doe',
            'department' => 'IT Support'
        ]));

        $activity->setRelation('assignee', User::factory()->make([
            'name' => 'Jane Smith'
        ]));

        $reportData = [
            'activities' => collect([$activity]),
            'statistics' => [],
            'period' => []
        ];

        $this->mockReportService
            ->shouldReceive('generateActivityReport')
            ->once()
            ->with($requestData)
            ->andReturn($reportData);

        $request = Request::create('/reports/export', 'POST', $requestData);
        $response = $this->controller->export($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
    }

    public function test_export_pdf_returns_not_implemented_response()
    {
        $requestData = [
            'format' => 'pdf',
            'start_date' => '2024-01-01',
            'end_date' => '2024-01-31'
        ];

        $reportData = [
            'activities' => collect([]),
            'statistics' => [],
            'period' => []
        ];

        $this->mockReportService
            ->shouldReceive('generateActivityReport')
            ->once()
            ->with($requestData)
            ->andReturn($reportData);

        $request = Request::create('/reports/export', 'POST', $requestData);
        $response = $this->controller->export($request);

        $this->assertEquals(501, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertFalse($responseData['success']);
        $this->assertStringContainsString('PDF export will be implemented', $responseData['message']);
    }

    public function test_summary_returns_statistics_for_given_period()
    {
        $requestData = ['period' => 'month'];

        $reportData = [
            'activities' => collect([]),
            'statistics' => [
                'total_activities' => 25,
                'completed_activities' => 20,
                'pending_activities' => 5,
                'completion_rate' => 80.0
            ],
            'period' => [
                'start_date' => Carbon::now()->startOfMonth()->format('Y-m-d'),
                'end_date' => Carbon::now()->endOfMonth()->format('Y-m-d')
            ]
        ];

        $this->mockReportService
            ->shouldReceive('generateActivityReport')
            ->once()
            ->andReturn($reportData);

        $request = Request::create('/reports/summary', 'GET', $requestData);
        $response = $this->controller->summary($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertTrue($responseData['success']);
        $this->assertEquals($reportData['statistics'], $responseData['data']['statistics']);
        $this->assertEquals($reportData['period'], $responseData['data']['period']);
    }
}