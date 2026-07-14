<?php

namespace App\Http\Controllers;

use App\Services\MetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class MetricsController extends Controller
{
    private MetricsService $metrics;

    public function __construct()
    {
        $this->metrics = MetricsService::getInstance();
    }

    public function index(): Response|JsonResponse
    {
        if (config('prometheus.collect_db_metrics', true)) {
            $this->collectDbMetrics();
        }
        if (config('prometheus.collect_user_metrics', true)) {
            $this->collectUserMetrics();
        }
        if (config('prometheus.collect_forum_metrics', true)) {
            $this->collectForumMetrics();
        }

        return response($this->metrics->render(), 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }

    private function collectDbMetrics(): void
    {
        try {
            try {
                $dbSize = 0;
                $driver = DB::connection()->getDriverName();
                if ($driver === 'sqlite') {
                    $result = DB::select('SELECT page_count * page_size / 1024 as size_kb FROM pragma_page_count(), pragma_page_size()');
                    $dbSize = (float) ($result[0]->size_kb ?? 0);
                } elseif ($driver === 'mysql') {
                    $dbName = DB::getDatabaseName();
                    $result = DB::select('SELECT ROUND(SUM(data_length + index_length) / 1024, 0) as size_kb FROM information_schema.tables WHERE table_schema = ?', [$dbName]);
                    $dbSize = (float) ($result[0]->size_kb ?? 0);
                }
                $gaugeDb = $this->metrics->createGauge('database_size_kb', 'Database file size in KB');
                $gaugeDb->set($dbSize);
            } catch (\Throwable $e) {
                report($e);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function collectUserMetrics(): void
    {
        try {
            $totalUsers = \App\Models\User::count();
            $gaugeUsers = $this->metrics->createGauge('users_total', 'Total registered users');
            $gaugeUsers->set($totalUsers);
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            $activeUsers = \App\Models\User::where('created_at', '>=', now()->subDay())->count();
            $gaugeActive = $this->metrics->createGauge('users_active_last_24h', 'Users active in the last 24 hours');
            $gaugeActive->set($activeUsers);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function collectForumMetrics(): void
    {
        $tryAndGauge = function (string $name, string $help, callable $query): void {
            try {
                $value = $query();
                $gauge = $this->metrics->createGauge($name, $help);
                $gauge->set((float) $value);
            } catch (\Throwable $e) {
                report($e);
            }
        };

        $tryAndGauge('forum_topics_total', 'Total forum topics', fn () => \App\Models\ForumTopic::count());
        $tryAndGauge('forum_posts_total', 'Total forum posts', fn () => \App\Models\ForumPost::count());
        $tryAndGauge('forum_categories_total', 'Total forum categories', fn () => \App\Models\ForumCategory::count());
        $tryAndGauge('forum_posts_last_hour', 'Forum posts created in the last hour', fn () => \App\Models\ForumPost::where('created_at', '>=', now()->subHour())->count());
        $tryAndGauge('forum_topics_last_hour', 'Forum topics created in the last hour', fn () => \App\Models\ForumTopic::where('created_at', '>=', now()->subHour())->count());
    }
}
