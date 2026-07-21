<?php

namespace App\Console\Commands;

use App\Models\ForumCategory;
use App\Models\ForumPost;
use App\Models\ForumTopic;
use App\Models\User;
use App\Services\MetricsService;
use Illuminate\Console\Command;

class CollectMetrics extends Command
{
    protected $signature = 'metrics:collect {--push : Push metrics to a pushgateway}';

    protected $description = 'Collect and expose application metrics';

    public function handle(): int
    {
        $metrics = MetricsService::getInstance();
        $updated = 0;

        if (config('prometheus.collect_user_metrics', true)) {
            $totalUsers = User::count();
            $gaugeUsers = $metrics->createGauge('users_total', 'Total registered users');
            $gaugeUsers->set($totalUsers);
            $updated++;

            $activeUsers = User::where('created_at', '>=', now()->subDay())->count();
            $gaugeActive = $metrics->createGauge('users_active_last_24h', 'Users active in the last 24 hours');
            $gaugeActive->set($activeUsers);
            $updated++;

            $this->line("  users_total: {$totalUsers}");
            $this->line("  users_active_last_24h: {$activeUsers}");
        }

        if (config('prometheus.collect_forum_metrics', true)) {
            $topics = ForumTopic::count();
            $metrics->createGauge('forum_topics_total', 'Total forum topics')->set($topics);
            $updated++;

            $posts = ForumPost::count();
            $metrics->createGauge('forum_posts_total', 'Total forum posts')->set($posts);
            $updated++;

            $categories = ForumCategory::count();
            $metrics->createGauge('forum_categories_total', 'Total forum categories')->set($categories);
            $updated++;

            $recentPosts = ForumPost::where('created_at', '>=', now()->subHour())->count();
            $metrics->createGauge('forum_posts_last_hour', 'Forum posts created in the last hour')->set($recentPosts);
            $updated++;

            $this->line("  forum_topics: {$topics}");
            $this->line("  forum_posts: {$posts}");
            $this->line("  forum_categories: {$categories}");
            $this->line("  forum_posts_last_hour: {$recentPosts}");
        }

        $this->info("Collected {$updated} metrics.");

        if ($this->option('push')) {
            $this->pushToGateway($metrics);
        }

        return Command::SUCCESS;
    }

    private function pushToGateway(MetricsService $metrics): void
    {
        $gateway = config('prometheus.pushgateway_url');

        if (! $gateway) {
            $this->warn('No pushgateway_url configured. Set PROMETHEUS_PUSHGATEWAY_URL.');

            return;
        }

        try {
            $job = 'smashconnect-'.gethostname();
            $url = rtrim($gateway, '/').'/metrics/job/'.urlencode($job);

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $metrics->render(),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_HTTPHEADER => ['Content-Type: text/plain'],
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 || $httpCode === 202) {
                $this->info("Metrics pushed to {$url}");
            } else {
                $this->warn("Pushgateway responded with HTTP {$httpCode}: {$response}");
            }
        } catch (\Throwable $e) {
            $this->error("Failed to push metrics: {$e->getMessage()}");
        }
    }
}
