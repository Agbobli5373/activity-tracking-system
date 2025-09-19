<?php

namespace App\Http\Controllers;

use App\Services\MetricsService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class MetricsController extends Controller
{
    private MetricsService $metricsService;

    public function __construct(MetricsService $metricsService)
    {
        $this->metricsService = $metricsService;
    }

    public function index(): Response
    {
        // Update database connections metric
        try {
            $connections = \DB::getConnections();
            $activeConnections = 0;
            foreach ($connections as $connection) {
                if ($connection->getPdo()) {
                    $activeConnections++;
                }
            }
            $this->metricsService->setDatabaseConnections($activeConnections);
        } catch (\Exception $e) {
            // Log error but don't fail metrics collection
            \Log::warning('Failed to collect database metrics: ' . $e->getMessage());
        }

        $metrics = $this->metricsService->getMetricsOutput();

        return response($metrics, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }
}
