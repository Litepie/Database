<?php

namespace Litepie\Database\Traits;

use Illuminate\Support\Facades\DB;

/**
 * Measurable Trait
 * 
 * Provides query performance monitoring and metrics for Eloquent models.
 * Track execution time, memory usage, query counts, and identify performance bottlenecks.
 * 
 * Usage:
 * ```php
 * class Product extends Model
 * {
 *     use Measurable;
 * }
 * 
 * // Enable profiling
 * Product::enableQueryProfiling();
 * 
 * // Perform operations
 * $products = Product::where('price', '>', 100)->get();
 * 
 * // Get metrics
 * $metrics = Product::getQueryMetrics();
 * $report = Product::getPerformanceReport();
 * ```
 */
trait Measurable
{
    /**
     * Performance metrics storage.
     */
    protected static array $queryMetrics = [
        'queries' => [],
        'execution_times' => [],
        'memory_usage' => [],
        'cache_hits' => 0,
        'cache_misses' => 0,
    ];

    /**
     * Profiling enabled flag.
     */
    protected static bool $profilingEnabled = false;

    /**
     * Start time for operations.
     */
    protected static float $startTime = 0.0;

    /**
     * Start memory usage.
     */
    protected static int $startMemory = 0;

    /**
     * Slow query threshold in milliseconds.
     */
    protected static float $slowQueryThreshold = 100.0;

    /**
     * Boot the trait.
     */
    public static function bootMeasurable(): void
    {
        // Listen to model events for automatic profiling
        if (static::$profilingEnabled) {
            static::retrieved(function ($model) {
                static::recordModelEvent('retrieved');
            });

            static::created(function ($model) {
                static::recordModelEvent('created');
            });

            static::updated(function ($model) {
                static::recordModelEvent('updated');
            });

            static::deleted(function ($model) {
                static::recordModelEvent('deleted');
            });
        }
    }

    /**
     * Enable query profiling.
     */
    public static function enableQueryProfiling(): void
    {
        static::$profilingEnabled = true;
        static::$startTime = microtime(true);
        static::$startMemory = memory_get_usage(true);
        
        DB::enableQueryLog();
    }

    /**
     * Disable query profiling.
     */
    public static function disableQueryProfiling(): void
    {
        static::$profilingEnabled = false;
        DB::disableQueryLog();
    }

    /**
     * Check if profiling is enabled.
     */
    public static function isProfilingEnabled(): bool
    {
        return static::$profilingEnabled;
    }

    /**
     * Set slow query threshold.
     */
    public static function setSlowQueryThreshold(float $milliseconds): void
    {
        static::$slowQueryThreshold = $milliseconds;
    }

    /**
     * Profile a query operation.
     */
    public static function profileQuery(callable $callback, string $operation = 'query')
    {
        if (!static::$profilingEnabled) {
            return $callback();
        }

        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        $queryCountBefore = count(DB::getQueryLog());
        
        $result = $callback();
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $memoryUsed = $endMemory - $startMemory;
        
        // Record metrics
        static::$queryMetrics['execution_times'][] = $executionTime;
        static::$queryMetrics['memory_usage'][] = $memoryUsed;
        
        // Get new queries
        $allQueries = DB::getQueryLog();
        $newQueries = array_slice($allQueries, $queryCountBefore);
        
        foreach ($newQueries as $query) {
            static::$queryMetrics['queries'][] = [
                'sql' => $query['query'],
                'bindings' => $query['bindings'],
                'time' => $query['time'],
                'operation' => $operation,
                'timestamp' => now(),
                'model' => static::class,
                'is_slow' => $query['time'] > static::$slowQueryThreshold,
            ];
        }
        
        return $result;
    }

    /**
     * Record a model event.
     */
    protected static function recordModelEvent(string $event): void
    {
        if (!static::$profilingEnabled) {
            return;
        }

        static::$queryMetrics['queries'][] = [
            'event' => $event,
            'model' => static::class,
            'timestamp' => now(),
        ];
    }

    /**
     * Get total query execution time.
     */
    public static function getTotalQueryTime(): float
    {
        return array_sum(static::$queryMetrics['execution_times']);
    }

    /**
     * Get current memory usage since profiling started.
     */
    public static function getMemoryUsage(): int
    {
        if (static::$startMemory === 0) {
            return 0;
        }
        return memory_get_usage(true) - static::$startMemory;
    }

    /**
     * Get peak memory usage.
     */
    public static function getPeakMemoryUsage(): int
    {
        return memory_get_peak_usage(true);
    }

    /**
     * Get total number of queries executed.
     */
    public static function getQueryCount(): int
    {
        return count(static::$queryMetrics['queries']);
    }

    /**
     * Get query log.
     */
    public static function getQueryLog(): array
    {
        return static::$queryMetrics['queries'];
    }

    /**
     * Get slow queries (queries exceeding threshold).
     */
    public static function getSlowQueries(?float $threshold = null): array
    {
        $threshold = $threshold ?? static::$slowQueryThreshold;
        
        return array_filter(static::$queryMetrics['queries'], function ($query) use ($threshold) {
            return isset($query['time']) && $query['time'] > $threshold;
        });
    }

    /**
     * Get duplicate queries.
     */
    public static function getDuplicateQueries(): array
    {
        $queryHashes = [];
        $duplicates = [];
        
        foreach (static::$queryMetrics['queries'] as $query) {
            if (!isset($query['sql'])) {
                continue;
            }

            $hash = md5($query['sql'] . serialize($query['bindings'] ?? []));
            
            if (isset($queryHashes[$hash])) {
                $duplicates[] = $query;
            } else {
                $queryHashes[$hash] = true;
            }
        }
        
        return $duplicates;
    }

    /**
     * Get cache hit rate.
     */
    public static function getCacheHitRate(): float
    {
        $total = static::$queryMetrics['cache_hits'] + static::$queryMetrics['cache_misses'];
        
        if ($total === 0) {
            return 0.0;
        }
        
        return (static::$queryMetrics['cache_hits'] / $total) * 100;
    }

    /**
     * Record cache hit.
     */
    public static function recordCacheHit(): void
    {
        static::$queryMetrics['cache_hits']++;
    }

    /**
     * Record cache miss.
     */
    public static function recordCacheMiss(): void
    {
        static::$queryMetrics['cache_misses']++;
    }

    /**
     * Get all performance metrics.
     */
    public static function getQueryMetrics(): array
    {
        $queryCount = static::getQueryCount();
        
        return [
            'profiling_enabled' => static::$profilingEnabled,
            'total_queries' => $queryCount,
            'total_execution_time' => static::getTotalQueryTime(),
            'average_query_time' => $queryCount > 0 ? static::getTotalQueryTime() / $queryCount : 0,
            'memory_usage' => static::getMemoryUsage(),
            'peak_memory_usage' => static::getPeakMemoryUsage(),
            'cache_hit_rate' => static::getCacheHitRate(),
            'slow_queries_count' => count(static::getSlowQueries()),
            'duplicate_queries_count' => count(static::getDuplicateQueries()),
        ];
    }

    /**
     * Get performance report with recommendations.
     */
    public static function getPerformanceReport(): array
    {
        $metrics = static::getQueryMetrics();
        $slowQueries = static::getSlowQueries();
        $duplicateQueries = static::getDuplicateQueries();
        $recommendations = static::generateRecommendations($metrics);
        
        return [
            'summary' => [
                'model' => static::class,
                'total_queries' => $metrics['total_queries'],
                'total_time' => round($metrics['total_execution_time'], 2) . 'ms',
                'average_time' => round($metrics['average_query_time'], 2) . 'ms',
                'memory_used' => static::formatBytes($metrics['memory_usage']),
                'peak_memory' => static::formatBytes($metrics['peak_memory_usage']),
                'cache_hit_rate' => round($metrics['cache_hit_rate'], 2) . '%',
            ],
            'issues' => [
                'slow_queries_count' => count($slowQueries),
                'duplicate_queries_count' => count($duplicateQueries),
                'high_memory_usage' => $metrics['memory_usage'] > 50 * 1024 * 1024, // 50MB
                'low_cache_hit_rate' => $metrics['cache_hit_rate'] > 0 && $metrics['cache_hit_rate'] < 80,
            ],
            'slow_queries' => array_map(function ($query) {
                return [
                    'sql' => $query['sql'] ?? 'N/A',
                    'time' => ($query['time'] ?? 0) . 'ms',
                    'operation' => $query['operation'] ?? 'unknown',
                ];
            }, array_slice($slowQueries, 0, 10)), // Top 10 slow queries
            'duplicate_queries' => array_map(function ($query) {
                return [
                    'sql' => $query['sql'] ?? 'N/A',
                    'operation' => $query['operation'] ?? 'unknown',
                ];
            }, array_slice($duplicateQueries, 0, 10)), // Top 10 duplicates
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Generate performance recommendations.
     */
    protected static function generateRecommendations(array $metrics): array
    {
        $recommendations = [];
        
        // Check for slow queries
        if ($metrics['slow_queries_count'] > 0) {
            $recommendations[] = [
                'type' => 'slow_queries',
                'severity' => 'high',
                'message' => 'Detected ' . $metrics['slow_queries_count'] . ' slow queries. Consider adding database indexes or optimizing queries.',
                'action' => 'Review slow queries and add appropriate indexes',
            ];
        }
        
        // Check for duplicate queries
        if ($metrics['duplicate_queries_count'] > 5) {
            $recommendations[] = [
                'type' => 'duplicate_queries',
                'severity' => 'medium',
                'message' => 'Detected ' . $metrics['duplicate_queries_count'] . ' duplicate queries. This may indicate N+1 query problems.',
                'action' => 'Use eager loading (with()) or enable query result caching',
            ];
        }
        
        // Check cache hit rate
        if ($metrics['cache_hit_rate'] > 0 && $metrics['cache_hit_rate'] < 80) {
            $recommendations[] = [
                'type' => 'low_cache_hit_rate',
                'severity' => 'medium',
                'message' => 'Cache hit rate is ' . round($metrics['cache_hit_rate'], 2) . '%. This is below the recommended 80%.',
                'action' => 'Adjust cache TTL or review caching strategy',
            ];
        }
        
        // Check memory usage
        if ($metrics['memory_usage'] > 100 * 1024 * 1024) { // 100MB
            $recommendations[] = [
                'type' => 'high_memory_usage',
                'severity' => 'high',
                'message' => 'Memory usage is ' . static::formatBytes($metrics['memory_usage']) . '. This is high for typical queries.',
                'action' => 'Consider using chunked processing or cursor pagination for large datasets',
            ];
        }
        
        // Check query count
        if ($metrics['total_queries'] > 50) {
            $recommendations[] = [
                'type' => 'high_query_count',
                'severity' => 'medium',
                'message' => 'Executed ' . $metrics['total_queries'] . ' queries. This is high for a single operation.',
                'action' => 'Review relationships and use eager loading to reduce query count',
            ];
        }
        
        // Check average query time
        if ($metrics['average_query_time'] > 50) {
            $recommendations[] = [
                'type' => 'slow_average_time',
                'severity' => 'medium',
                'message' => 'Average query time is ' . round($metrics['average_query_time'], 2) . 'ms. Aim for under 50ms.',
                'action' => 'Optimize queries, add indexes, or review table structure',
            ];
        }
        
        return $recommendations;
    }

    /**
     * Benchmark a query operation.
     */
    public static function benchmark(callable $callback, int $iterations = 1): array
    {
        $times = [];
        $memoryUsages = [];
        
        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            $startMemory = memory_get_usage(true);
            
            $result = $callback();
            
            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);
            
            $times[] = ($endTime - $startTime) * 1000; // Convert to ms
            $memoryUsages[] = $endMemory - $startMemory;
        }
        
        return [
            'iterations' => $iterations,
            'times' => $times,
            'avg_time' => round(array_sum($times) / count($times), 2) . 'ms',
            'min_time' => round(min($times), 2) . 'ms',
            'max_time' => round(max($times), 2) . 'ms',
            'memory_usages' => array_map([static::class, 'formatBytes'], $memoryUsages),
            'avg_memory' => static::formatBytes((int) (array_sum($memoryUsages) / count($memoryUsages))),
            'min_memory' => static::formatBytes(min($memoryUsages)),
            'max_memory' => static::formatBytes(max($memoryUsages)),
        ];
    }

    /**
     * Explain query execution plan.
     */
    public static function explainQuery($query): array
    {
        $sql = $query instanceof \Illuminate\Database\Eloquent\Builder 
            ? $query->toSql() 
            : $query;
            
        $bindings = $query instanceof \Illuminate\Database\Eloquent\Builder 
            ? $query->getBindings() 
            : [];
        
        // Replace placeholders with actual values for EXPLAIN
        $fullSql = $sql;
        foreach ($bindings as $binding) {
            $value = is_string($binding) ? "'" . addslashes($binding) . "'" : $binding;
            $fullSql = preg_replace('/\?/', $value, $fullSql, 1);
        }
        
        $explain = DB::select("EXPLAIN $fullSql");
        
        return [
            'sql' => $sql,
            'bindings' => $bindings,
            'full_sql' => $fullSql,
            'explain' => $explain,
        ];
    }

    /**
     * Reset all metrics.
     */
    public static function resetQueryMetrics(): void
    {
        static::$queryMetrics = [
            'queries' => [],
            'execution_times' => [],
            'memory_usage' => [],
            'cache_hits' => 0,
            'cache_misses' => 0,
        ];
        
        static::$startTime = microtime(true);
        static::$startMemory = memory_get_usage(true);
        
        DB::flushQueryLog();
    }

    /**
     * Format bytes to human-readable format.
     */
    protected static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unit = 0;
        
        while ($bytes >= 1024 && $unit < count($units) - 1) {
            $bytes /= 1024;
            $unit++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unit];
    }

    /**
     * Get queries grouped by operation type.
     */
    public static function getQueriesByOperation(): array
    {
        $grouped = [];
        
        foreach (static::$queryMetrics['queries'] as $query) {
            $operation = $query['operation'] ?? 'unknown';
            
            if (!isset($grouped[$operation])) {
                $grouped[$operation] = [
                    'count' => 0,
                    'total_time' => 0,
                    'queries' => [],
                ];
            }
            
            $grouped[$operation]['count']++;
            $grouped[$operation]['total_time'] += $query['time'] ?? 0;
            $grouped[$operation]['queries'][] = $query;
        }
        
        return $grouped;
    }

    /**
     * Get the most expensive queries.
     */
    public static function getMostExpensiveQueries(int $limit = 10): array
    {
        $queries = static::$queryMetrics['queries'];
        
        usort($queries, function ($a, $b) {
            return ($b['time'] ?? 0) <=> ($a['time'] ?? 0);
        });
        
        return array_slice($queries, 0, $limit);
    }

    /**
     * Print performance report to console.
     */
    public static function printPerformanceReport(): void
    {
        $report = static::getPerformanceReport();
        
        echo "\n" . str_repeat('=', 80) . "\n";
        echo "PERFORMANCE REPORT: " . $report['summary']['model'] . "\n";
        echo str_repeat('=', 80) . "\n\n";
        
        echo "SUMMARY:\n";
        foreach ($report['summary'] as $key => $value) {
            echo "  " . str_pad(ucwords(str_replace('_', ' ', $key)), 20) . ": $value\n";
        }
        
        echo "\nISSUES:\n";
        foreach ($report['issues'] as $key => $value) {
            $status = $value ? '✗ YES' : '✓ NO';
            echo "  " . str_pad(ucwords(str_replace('_', ' ', $key)), 30) . ": $status\n";
        }
        
        if (!empty($report['recommendations'])) {
            echo "\nRECOMMENDATIONS:\n";
            foreach ($report['recommendations'] as $i => $rec) {
                echo "  " . ($i + 1) . ". [{$rec['severity']}] {$rec['message']}\n";
                echo "     → {$rec['action']}\n\n";
            }
        }
        
        echo str_repeat('=', 80) . "\n\n";
    }
}
