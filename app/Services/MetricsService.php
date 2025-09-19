<?php

namespace App\Services;

use Prometheus\CollectorRegistry;
use Prometheus\Storage\InMemory;
use Prometheus\RenderTextFormat;

class MetricsService
{
    private CollectorRegistry $registry;
    private $requestCounter;
    private $responseTimeHistogram;
    private $errorCounter;
    private $dbConnectionGauge;
    private $cacheHitCounter;
    private $cacheMissCounter;

    public function __construct()
    {
        $this->registry = new CollectorRegistry(new InMemory());

        // Initialize metrics
        $this->initializeMetrics();
    }

    private function initializeMetrics(): void
    {
        // HTTP request counter
        $this->requestCounter = $this->registry->getOrRegisterCounter(
            'laravel',
            'http_requests_total',
            'Total number of HTTP requests',
            ['method', 'route', 'status']
        );

        // Response time histogram
        $this->responseTimeHistogram = $this->registry->getOrRegisterHistogram(
            'laravel',
            'http_request_duration_seconds',
            'HTTP request duration in seconds',
            ['method', 'route'],
            [0.1, 0.5, 1, 2, 5, 10]
        );

        // Error counter
        $this->errorCounter = $this->registry->getOrRegisterCounter(
            'laravel',
            'http_errors_total',
            'Total number of HTTP errors',
            ['method', 'route', 'status']
        );

        // Database connections gauge
        $this->dbConnectionGauge = $this->registry->getOrRegisterGauge(
            'laravel',
            'database_connections_active',
            'Number of active database connections'
        );

        // Cache metrics
        $this->cacheHitCounter = $this->registry->getOrRegisterCounter(
            'laravel',
            'cache_hits_total',
            'Total number of cache hits'
        );

        $this->cacheMissCounter = $this->registry->getOrRegisterCounter(
            'laravel',
            'cache_misses_total',
            'Total number of cache misses'
        );
    }

    public function incrementRequestCounter(string $method, string $route, string $status): void
    {
        $this->requestCounter->inc([$method, $route, $status]);
    }

    public function observeResponseTime(string $method, string $route, float $duration): void
    {
        $this->responseTimeHistogram->observe($duration, [$method, $route]);
    }

    public function incrementErrorCounter(string $method, string $route, string $status): void
    {
        $this->errorCounter->inc([$method, $route, $status]);
    }

    public function setDatabaseConnections(int $connections): void
    {
        $this->dbConnectionGauge->set($connections);
    }

    public function incrementCacheHit(): void
    {
        $this->cacheHitCounter->inc();
    }

    public function incrementCacheMiss(): void
    {
        $this->cacheMissCounter->inc();
    }

    public function getMetricsOutput(): string
    {
        $renderer = new RenderTextFormat();
        return $renderer->render($this->registry->getMetricFamilySamples());
    }

    public function getRegistry(): CollectorRegistry
    {
        return $this->registry;
    }
}
