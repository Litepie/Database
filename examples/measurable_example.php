<?php

/**
 * Measurable Trait Examples
 * 
 * This file demonstrates how to use the Measurable trait
 * for performance monitoring and optimization.
 */

namespace App\Examples;

use App\Models\Product;
use App\Models\Order;
use App\Models\User;

class MeasurableExamples
{
    /**
     * Example 1: Basic Profiling
     * Enable profiling and track query performance.
     */
    public function example1(): void
    {
        // Enable profiling
        Product::enableQueryProfiling();
        
        // Perform operations
        $products = Product::where('price', '>', 100)->get();
        
        // Get metrics
        $metrics = Product::getQueryMetrics();
        /*
        Returns:
        [
            'profiling_enabled' => true,
            'total_queries' => 1,
            'total_execution_time' => 15.42, // milliseconds
            'average_query_time' => 15.42,
            'memory_usage' => 2048576, // bytes
            'peak_memory_usage' => 4096000,
            'cache_hit_rate' => 0.0,
            'slow_queries_count' => 0,
            'duplicate_queries_count' => 0
        ]
        */
        
        // Disable profiling when done
        Product::disableQueryProfiling();
    }

    /**
     * Example 2: Performance Report
     * Get detailed performance report with recommendations.
     */
    public function example2(): void
    {
        Product::enableQueryProfiling();
        
        // Simulate some operations
        $products = Product::with('category', 'reviews')
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->take(100)
            ->get();
        
        // Get comprehensive report
        $report = Product::getPerformanceReport();
        /*
        Returns:
        [
            'summary' => [
                'model' => 'App\Models\Product',
                'total_queries' => 3,
                'total_time' => '45.67ms',
                'average_time' => '15.22ms',
                'memory_used' => '1.5 MB',
                'peak_memory' => '8.2 MB',
                'cache_hit_rate' => '0.00%'
            ],
            'issues' => [
                'slow_queries_count' => 0,
                'duplicate_queries_count' => 0,
                'high_memory_usage' => false,
                'low_cache_hit_rate' => false
            ],
            'slow_queries' => [],
            'duplicate_queries' => [],
            'recommendations' => []
        ]
        */
        
        Product::disableQueryProfiling();
    }

    /**
     * Example 3: Identifying Slow Queries
     * Set threshold and detect slow queries.
     */
    public function example3(): void
    {
        // Set slow query threshold to 50ms
        Product::setSlowQueryThreshold(50.0);
        Product::enableQueryProfiling();
        
        // Perform operations
        $products = Product::with('category', 'reviews', 'images')
            ->where('price', '>', 100)
            ->get();
        
        // Get slow queries
        $slowQueries = Product::getSlowQueries();
        /*
        Returns array of slow queries:
        [
            [
                'sql' => 'select * from products where price > ?',
                'bindings' => [100],
                'time' => 125.45, // milliseconds
                'operation' => 'query',
                'timestamp' => '2024-11-28 10:30:45',
                'is_slow' => true
            ]
        ]
        */
        
        // Get slow queries with custom threshold
        $verySlow = Product::getSlowQueries(200.0);
        
        Product::disableQueryProfiling();
    }

    /**
     * Example 4: Detecting Duplicate Queries (N+1 Problem)
     * Identify and fix N+1 query problems.
     */
    public function example4(): void
    {
        Product::enableQueryProfiling();
        
        // BAD: This causes N+1 queries
        $products = Product::take(10)->get();
        foreach ($products as $product) {
            $category = $product->category; // Lazy loading - creates N queries
        }
        
        $duplicates = Product::getDuplicateQueries();
        // Will show multiple duplicate category queries
        
        // Reset metrics
        Product::resetQueryMetrics();
        
        // GOOD: Use eager loading
        $products = Product::with('category')->take(10)->get();
        foreach ($products as $product) {
            $category = $product->category; // Already loaded
        }
        
        $duplicatesAfter = Product::getDuplicateQueries();
        // Should show 0 duplicates
        
        Product::disableQueryProfiling();
    }

    /**
     * Example 5: Benchmarking Operations
     * Compare performance of different approaches.
     */
    public function example5(): void
    {
        // Benchmark without eager loading
        $results1 = Product::benchmark(function () {
            $products = Product::take(50)->get();
            foreach ($products as $product) {
                $category = $product->category;
            }
        }, 10); // Run 10 times
        
        /*
        Returns:
        [
            'iterations' => 10,
            'times' => [125.5, 128.3, 127.1, ...],
            'avg_time' => '126.80ms',
            'min_time' => '125.50ms',
            'max_time' => '130.20ms',
            'memory_usages' => ['2.5 MB', '2.6 MB', ...],
            'avg_memory' => '2.55 MB',
            'min_memory' => '2.5 MB',
            'max_memory' => '2.7 MB'
        ]
        */
        
        // Benchmark with eager loading
        $results2 = Product::benchmark(function () {
            $products = Product::with('category')->take(50)->get();
            foreach ($products as $product) {
                $category = $product->category;
            }
        }, 10);
        
        // Compare results
        echo "Without eager loading: {$results1['avg_time']}\n";
        echo "With eager loading: {$results2['avg_time']}\n";
    }

    /**
     * Example 6: Explaining Query Execution
     * Understand how database executes queries.
     */
    public function example6(): void
    {
        $query = Product::where('price', '>', 100)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc');
        
        // Get query execution plan
        $explain = Product::explainQuery($query);
        /*
        Returns:
        [
            'sql' => 'select * from products where price > ? and status = ? order by created_at desc',
            'bindings' => [100, 'active'],
            'full_sql' => 'select * from products where price > 100 and status = \'active\' order by created_at desc',
            'explain' => [
                // MySQL EXPLAIN output
                [
                    'id' => 1,
                    'select_type' => 'SIMPLE',
                    'table' => 'products',
                    'type' => 'range',
                    'possible_keys' => 'price_index,status_index',
                    'key' => 'price_index',
                    'rows' => 150,
                    ...
                ]
            ]
        ]
        */
    }

    /**
     * Example 7: Grouping Queries by Operation
     * Analyze queries by type (select, insert, update, delete).
     */
    public function example7(): void
    {
        Product::enableQueryProfiling();
        
        // Perform various operations
        $product = Product::find(1);
        $products = Product::where('status', 'active')->get();
        Product::create(['name' => 'New Product', 'price' => 99.99]);
        
        // Group queries by operation
        $grouped = Product::getQueriesByOperation();
        /*
        Returns:
        [
            'find' => [
                'count' => 1,
                'total_time' => 15.2,
                'queries' => [...]
            ],
            'query' => [
                'count' => 1,
                'total_time' => 25.4,
                'queries' => [...]
            ],
            'create' => [
                'count' => 1,
                'total_time' => 8.5,
                'queries' => [...]
            ]
        ]
        */
        
        Product::disableQueryProfiling();
    }

    /**
     * Example 8: Most Expensive Queries
     * Find queries that consume the most time.
     */
    public function example8(): void
    {
        Product::enableQueryProfiling();
        
        // Perform operations
        Product::where('status', 'active')->get();
        Product::with('category', 'reviews')->take(100)->get();
        Product::find(1);
        
        // Get top 5 most expensive queries
        $expensive = Product::getMostExpensiveQueries(5);
        /*
        Returns queries sorted by execution time (descending):
        [
            [
                'sql' => 'select * from products ...',
                'time' => 150.25,
                'operation' => 'query',
                ...
            ],
            [
                'sql' => 'select * from categories ...',
                'time' => 75.10,
                'operation' => 'query',
                ...
            ]
        ]
        */
        
        Product::disableQueryProfiling();
    }

    /**
     * Example 9: Cache Hit Rate Tracking
     * Monitor cache effectiveness.
     */
    public function example9(): void
    {
        Product::enableQueryProfiling();
        
        // Simulate cache hits and misses
        for ($i = 0; $i < 10; $i++) {
            $cacheKey = "product_1";
            
            if (cache()->has($cacheKey)) {
                Product::recordCacheHit();
                $product = cache()->get($cacheKey);
            } else {
                Product::recordCacheMiss();
                $product = Product::find(1);
                cache()->put($cacheKey, $product, 3600);
            }
        }
        
        // Get cache statistics
        $hitRate = Product::getCacheHitRate();
        // Returns: 90.0 (90% cache hit rate)
        
        $metrics = Product::getQueryMetrics();
        // $metrics['cache_hit_rate'] => 90.0
        
        Product::disableQueryProfiling();
    }

    /**
     * Example 10: Print Report to Console
     * Output formatted performance report.
     */
    public function example10(): void
    {
        Product::enableQueryProfiling();
        
        // Perform operations
        $products = Product::with('category')
            ->where('price', '>', 100)
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get();
        
        // Print report to console
        Product::printPerformanceReport();
        /*
        Outputs:
        ================================================================================
        PERFORMANCE REPORT: App\Models\Product
        ================================================================================

        SUMMARY:
          Model               : App\Models\Product
          Total Queries       : 2
          Total Time          : 45.67ms
          Average Time        : 22.84ms
          Memory Used         : 1.5 MB
          Peak Memory         : 8.2 MB
          Cache Hit Rate      : 0.00%

        ISSUES:
          Slow Queries Count            : ✓ NO
          Duplicate Queries Count       : ✓ NO
          High Memory Usage             : ✓ NO
          Low Cache Hit Rate            : ✓ NO

        RECOMMENDATIONS:
          (No recommendations - performance looks good!)

        ================================================================================
        */
        
        Product::disableQueryProfiling();
    }

    /**
     * Example 11: API Endpoint Performance Monitoring
     * Monitor performance in controller methods.
     */
    public function example11(): void
    {
        // In your API controller
        /*
        public function index(Request $request)
        {
            Product::enableQueryProfiling();
            
            $products = Product::filterQueryString($request->query('filter'))
                ->with('category')
                ->paginate(15);
            
            $report = Product::getPerformanceReport();
            
            // Add metrics to response headers
            return response()->json([
                'data' => $products,
                'meta' => [
                    'query_count' => $report['summary']['total_queries'],
                    'execution_time' => $report['summary']['total_time'],
                ]
            ])->header('X-Query-Count', $report['summary']['total_queries'])
              ->header('X-Execution-Time', $report['summary']['total_time']);
            
            Product::disableQueryProfiling();
        }
        */
    }

    /**
     * Example 12: Development vs Production
     * Use profiling conditionally.
     */
    public function example12(): void
    {
        // Enable profiling only in development
        if (app()->environment('local', 'development')) {
            Product::enableQueryProfiling();
        }
        
        // Perform operations
        $products = Product::where('status', 'active')->get();
        
        // Get metrics only if profiling is enabled
        if (Product::isProfilingEnabled()) {
            $report = Product::getPerformanceReport();
            
            // Log performance issues
            if (!empty($report['recommendations'])) {
                logger()->warning('Performance issues detected', [
                    'model' => Product::class,
                    'recommendations' => $report['recommendations']
                ]);
            }
        }
        
        Product::disableQueryProfiling();
    }

    /**
     * Example 13: Middleware for Automatic Profiling
     * Profile all requests automatically.
     */
    public function example13(): void
    {
        /*
        // Create middleware: app/Http/Middleware/QueryProfiling.php
        
        class QueryProfiling
        {
            public function handle($request, Closure $next)
            {
                if (config('app.debug')) {
                    Product::enableQueryProfiling();
                    Order::enableQueryProfiling();
                    User::enableQueryProfiling();
                }
                
                $response = $next($request);
                
                if (config('app.debug')) {
                    // Collect all metrics
                    $metrics = [
                        'products' => Product::getQueryMetrics(),
                        'orders' => Order::getQueryMetrics(),
                        'users' => User::getQueryMetrics(),
                    ];
                    
                    // Add to debug bar or log
                    debugbar()->addMessage($metrics, 'query_metrics');
                    
                    Product::disableQueryProfiling();
                    Order::disableQueryProfiling();
                    User::disableQueryProfiling();
                }
                
                return $response;
            }
        }
        */
    }

    /**
     * Example 14: Continuous Performance Monitoring
     * Track performance over time.
     */
    public function example14(): void
    {
        Product::enableQueryProfiling();
        
        // Perform operations
        $products = Product::where('status', 'active')->get();
        
        $metrics = Product::getQueryMetrics();
        
        // Store metrics for analysis
        DB::table('performance_metrics')->insert([
            'model' => Product::class,
            'endpoint' => request()->path(),
            'query_count' => $metrics['total_queries'],
            'execution_time' => $metrics['total_execution_time'],
            'memory_usage' => $metrics['memory_usage'],
            'slow_queries_count' => $metrics['slow_queries_count'],
            'created_at' => now(),
        ]);
        
        Product::disableQueryProfiling();
    }

    /**
     * Example 15: Reset Metrics Between Tests
     * Clean slate for each test.
     */
    public function example15(): void
    {
        // Before each test
        Product::enableQueryProfiling();
        Product::resetQueryMetrics();
        
        // Run test
        $products = Product::where('status', 'active')->get();
        
        // Assert metrics
        $metrics = Product::getQueryMetrics();
        assert($metrics['total_queries'] === 1);
        assert($metrics['slow_queries_count'] === 0);
        
        // Clean up
        Product::resetQueryMetrics();
        Product::disableQueryProfiling();
    }
}

/**
 * USAGE IN MODELS
 */

/*
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Litepie\Database\Traits\Measurable;

class Product extends Model
{
    use Measurable;
    
    // Your model code...
}
*/

/**
 * USAGE IN TESTS
 */

/*
use Tests\TestCase;

class ProductPerformanceTest extends TestCase
{
    public function test_product_query_performance()
    {
        Product::enableQueryProfiling();
        Product::resetQueryMetrics();
        
        $products = Product::with('category')->where('status', 'active')->get();
        
        $metrics = Product::getQueryMetrics();
        
        // Assert performance requirements
        $this->assertLessThan(3, $metrics['total_queries']);
        $this->assertLessThan(100, $metrics['average_query_time']); // Under 100ms
        $this->assertEquals(0, $metrics['slow_queries_count']);
        $this->assertEquals(0, $metrics['duplicate_queries_count']);
        
        Product::disableQueryProfiling();
    }
}
*/
