<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Prometheus\CollectorRegistry;
use Prometheus\Counter;
use Prometheus\Gauge;
use Prometheus\Histogram;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\InMemory;
use Prometheus\Storage\Redis;

class MetricsService
{
    private static ?MetricsService $instance = null;

    private CollectorRegistry $registry;

    private bool $redisAvailable = false;

    private function __construct()
    {
        $this->registry = $this->createRegistry();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    private function createRegistry(): CollectorRegistry
    {
        $driver = config('prometheus.storage', 'in_memory');

        if ($driver === 'redis') {
            try {
                $redisConfig = config('prometheus.redis', []);
                $redis = new \Redis;
                $redis->connect(
                    $redisConfig['host'] ?? '127.0.0.1',
                    $redisConfig['port'] ?? 6379,
                    $redisConfig['timeout'] ?? 0.1,
                    null,
                    $redisConfig['read_timeout'] ?? 0.1,
                    ['persistent' => $redisConfig['persistent_connections'] ?? false]
                );
                if (! empty($redisConfig['password'])) {
                    $redis->auth($redisConfig['password']);
                }
                $adapter = new Redis($redis);
                $adapter->setPrefix($redisConfig['prefix'] ?? 'PROMETHEUS_');
                $this->redisAvailable = true;

                return new CollectorRegistry($adapter);
            } catch (\Throwable $e) {
                Log::warning('Prometheus Redis connection failed, falling back to InMemory: '.$e->getMessage());
            }
        }

        return new CollectorRegistry(new InMemory);
    }

    public function getRegistry(): CollectorRegistry
    {
        return $this->registry;
    }

    public function render(): string
    {
        $renderer = new RenderTextFormat;

        return $renderer->render($this->registry->getMetricFamilySamples());
    }

    public function createCounter(string $name, string $help, array $labels = []): Counter
    {
        $namespace = config('prometheus.namespace', 'smashconnect');

        return $this->registry->getOrRegisterCounter($namespace, $name, $help, $labels);
    }

    public function createGauge(string $name, string $help, array $labels = []): Gauge
    {
        $namespace = config('prometheus.namespace', 'smashconnect');

        return $this->registry->getOrRegisterGauge($namespace, $name, $help, $labels);
    }

    public function createHistogram(string $name, string $help, array $labels = [], ?array $buckets = null): Histogram
    {
        $namespace = config('prometheus.namespace', 'smashconnect');
        if ($buckets === null) {
            $buckets = config('prometheus.http_buckets');
        }

        return $this->registry->getOrRegisterHistogram($namespace, $name, $help, $labels, $buckets);
    }

    public function isRedisAvailable(): bool
    {
        return $this->redisAvailable;
    }
}
