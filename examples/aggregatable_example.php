<?php

/**
 * Aggregatable Trait Examples
 * 
 * This file demonstrates how to use the Aggregatable trait
 * for statistical analysis and data aggregation operations.
 */

namespace App\Examples;

use App\Models\Product;
use App\Models\Order;
use App\Models\Sale;
use Carbon\Carbon;

class AggregatableExamples
{
    /**
     * Example 1: Basic Aggregations
     * Perform multiple aggregation operations at once.
     */
    public function example1(): void
    {
        // Single aggregation operation
        $results = Product::aggregate([
            'count' => '*',
        ]);
        // Returns: ['count' => 1500]
        
        // Multiple aggregations
        $results = Product::aggregate([
            'count' => '*',
            'sum' => 'price',
            'avg' => 'price',
            'min' => 'price',
            'max' => 'price',
        ]);
        
        /*
        Returns:
        [
            'count' => 1500,
            'sum' => 75000.00,
            'avg' => 50.00,
            'min' => 10.00,
            'max' => 500.00
        ]
        */
    }

    /**
     * Example 2: Statistical Aggregations
     * Calculate median, mode, variance, and standard deviation.
     */
    public function example2(): void
    {
        // Advanced statistical operations
        $results = Product::aggregate([
            'median' => 'price',
            'mode' => 'category_id',
            'variance' => 'price',
            'stddev' => 'price',
        ]);
        
        /*
        Returns:
        [
            'median' => 45.00,
            'mode' => 3,
            'variance' => 1234.56,
            'stddev' => 35.14
        ]
        */
        
        // All statistics at once
        $results = Product::aggregate([
            'count' => '*',
            'sum' => 'price',
            'avg' => 'price',
            'min' => 'price',
            'max' => 'price',
            'median' => 'price',
            'variance' => 'price',
            'stddev' => 'price',
        ]);
    }

    /**
     * Example 3: Statistical Summary
     * Get comprehensive statistical summary for a field.
     */
    public function example3(): void
    {
        // Complete statistical analysis
        $summary = Product::statisticalSummary('price');
        
        /*
        Returns:
        [
            'count' => 1500,
            'sum' => 75000.00,
            'avg' => 50.00,
            'min' => 10.00,
            'max' => 500.00,
            'median' => 45.00,
            'mode' => 29.99,
            'variance' => 1234.5678,
            'std_dev' => 35.1365,
            'percentiles' => [
                'p25' => 25.00,
                'p50' => 45.00,
                'p75' => 75.00,
                'p90' => 150.00,
                'p95' => 250.00,
                'p99' => 450.00
            ]
        ]
        */
    }

    /**
     * Example 4: Group By with Aggregations
     * Group data and calculate aggregations for each group.
     */
    public function example4(): void
    {
        // Group by category with counts
        $stats = Product::groupByWithAggregations('category_id', [
            'count' => '*',
        ]);
        
        // Group by category with multiple aggregations
        $stats = Product::groupByWithAggregations('category_id', [
            'count' => '*',
            'sum' => 'price',
            'avg' => 'price',
            'min' => 'price',
            'max' => 'price',
        ]);
        
        /*
        Returns Collection:
        [
            ['category_id' => 1, 'count' => 50, 'sum' => 2500, 'avg' => 50, ...],
            ['category_id' => 2, 'count' => 75, 'sum' => 3750, 'avg' => 50, ...],
            ['category_id' => 3, 'count' => 100, 'sum' => 5000, 'avg' => 50, ...],
        ]
        */
        
        // Group by status
        $stats = Order::groupByWithAggregations('status', [
            'count' => '*',
            'sum' => 'total',
        ]);
    }

    /**
     * Example 5: Percentile Calculations
     * Calculate percentiles for distribution analysis.
     */
    public function example5(): void
    {
        // Standard percentiles
        $percentiles = Product::percentiles('price');
        
        /*
        Returns:
        [
            'p25' => 25.00,
            'p50' => 45.00,
            'p75' => 75.00,
            'p90' => 150.00,
            'p95' => 250.00,
            'p99' => 450.00
        ]
        */
        
        // Custom percentiles
        $percentiles = Product::percentiles('price', [10, 25, 50, 75, 90]);
        
        /*
        Returns:
        [
            'p10' => 15.00,
            'p25' => 25.00,
            'p50' => 45.00,
            'p75' => 75.00,
            'p90' => 150.00
        ]
        */
        
        // Use for pricing analysis
        $orderPercentiles = Order::percentiles('total', [50, 75, 90, 95]);
    }

    /**
     * Example 6: Trend Analysis
     * Analyze data trends over time.
     */
    public function example6(): void
    {
        // Daily order count
        $dailyOrders = Order::trend('created_at', 'day');
        
        /*
        Returns Collection:
        [
            ['period' => '2024-11-01', 'value' => 45],
            ['period' => '2024-11-02', 'value' => 52],
            ['period' => '2024-11-03', 'value' => 38],
            ...
        ]
        */
        
        // Monthly revenue
        $monthlyRevenue = Order::trend('created_at', 'month', 'total', 'sum');
        
        /*
        Returns Collection:
        [
            ['period' => '2024-01', 'value' => 45000.00],
            ['period' => '2024-02', 'value' => 52000.00],
            ['period' => '2024-03', 'value' => 48000.00],
            ...
        ]
        */
        
        // Weekly average order value
        $weeklyAvg = Order::trend('created_at', 'week', 'total', 'avg');
        
        // Hourly sales
        $hourlySales = Sale::trend('created_at', 'hour');
    }

    /**
     * Example 7: Pivot Tables
     * Create pivot tables for cross-tabulation analysis.
     */
    public function example7(): void
    {
        // Sales by customer and month
        $pivot = Order::pivot('customer_id', 'month', 'total', 'sum');
        
        /*
        Returns:
        [
            'data' => [
                1 => ['Jan' => 1500, 'Feb' => 2000, 'Mar' => 1800],
                2 => ['Jan' => 3000, 'Feb' => 2500, 'Mar' => 2800],
                3 => ['Jan' => 1200, 'Feb' => 1400, 'Mar' => 1600],
            ],
            'columns' => ['Jan', 'Feb', 'Mar'],
            'rows' => [1, 2, 3]
        ]
        */
        
        // Product count by category and status
        $pivot = Product::pivot('category_id', 'status', 'id', 'count');
        
        // Average price by category and brand
        $pivot = Product::pivot('category_id', 'brand_id', 'price', 'avg');
    }

    /**
     * Example 8: Moving Average
     * Calculate moving averages for smoothing trends.
     */
    public function example8(): void
    {
        // 7-day moving average of prices
        $ma = Product::movingAverage('price', 7, 'created_at');
        
        /*
        Returns Collection:
        [
            [
                'period' => '2024-11-08',
                'value' => 50.00,
                'moving_average' => 48.50,
                'window_size' => 7
            ],
            [
                'period' => '2024-11-09',
                'value' => 52.00,
                'moving_average' => 49.20,
                'window_size' => 7
            ],
            ...
        ]
        */
        
        // 30-day moving average
        $ma30 = Order::movingAverage('total', 30, 'created_at');
        
        // 12-month moving average
        $ma12 = Sale::movingAverage('revenue', 12, 'month');
    }

    /**
     * Example 9: Histogram Generation
     * Create histogram distribution data.
     */
    public function example9(): void
    {
        // Price distribution in 10 bins
        $histogram = Product::histogram('price', 10);
        
        /*
        Returns:
        [
            [
                'bin' => 1,
                'range' => '10.00-59.00',
                'start' => 10.00,
                'end' => 59.00,
                'count' => 150,
                'percentage' => 10.00
            ],
            [
                'bin' => 2,
                'range' => '59.00-108.00',
                'start' => 59.00,
                'end' => 108.00,
                'count' => 300,
                'percentage' => 20.00
            ],
            ...
        ]
        */
        
        // Order total distribution
        $histogram = Order::histogram('total', 15);
        
        // Age distribution
        $histogram = User::histogram('age', 8);
    }

    /**
     * Example 10: Correlation Analysis
     * Calculate correlation between two variables.
     */
    public function example10(): void
    {
        // Correlation between price and sales
        $correlation = Product::correlation('price', 'sales_count');
        // Returns: -0.7234 (negative correlation - higher price, lower sales)
        
        // Correlation between marketing spend and revenue
        $correlation = Order::correlation('marketing_spend', 'total');
        // Returns: 0.8456 (positive correlation)
        
        // Interpretation:
        // 1.0 = perfect positive correlation
        // 0.0 = no correlation
        // -1.0 = perfect negative correlation
        
        if ($correlation > 0.7) {
            echo "Strong positive correlation";
        } elseif ($correlation < -0.7) {
            echo "Strong negative correlation";
        } else {
            echo "Weak or no correlation";
        }
    }

    /**
     * Example 11: Date Range Scopes
     * Filter data by date ranges.
     */
    public function example11(): void
    {
        // Current month orders
        $orders = Order::currentPeriod('created_at', 'month')->get();
        
        // Current week sales
        $sales = Sale::currentPeriod('created_at', 'week')->get();
        
        // Today's products
        $products = Product::currentPeriod('created_at', 'day')->get();
        
        // Previous month
        $lastMonth = Order::previousPeriod('created_at', 'month')->get();
        
        // Previous year
        $lastYear = Sale::previousPeriod('created_at', 'year')->get();
        
        // Custom date range
        $range = Order::dateRange('created_at', '2024-01-01', '2024-12-31')->get();
    }

    /**
     * Example 12: Combined Filtering and Aggregation
     * Combine scopes with aggregations.
     */
    public function example12(): void
    {
        // Current month statistics
        $stats = Order::currentPeriod('created_at', 'month')
            ->aggregate([
                'count' => '*',
                'sum' => 'total',
                'avg' => 'total',
            ]);
        
        // Active products price summary
        $summary = Product::where('status', 'active')
            ->statisticalSummary('price');
        
        // High-value orders this week
        $weeklyHigh = Order::currentPeriod('created_at', 'week')
            ->where('total', '>', 1000)
            ->aggregate([
                'count' => '*',
                'sum' => 'total',
            ]);
    }

    /**
     * Example 13: Dashboard Analytics
     * Real-world dashboard example.
     */
    public function example13(): void
    {
        // Sales Dashboard
        $dashboard = [
            // Today's stats
            'today' => Order::currentPeriod('created_at', 'day')
                ->aggregate([
                    'count' => '*',
                    'sum' => 'total',
                    'avg' => 'total',
                ]),
            
            // This month stats
            'month' => Order::currentPeriod('created_at', 'month')
                ->aggregate([
                    'count' => '*',
                    'sum' => 'total',
                ]),
            
            // Previous month stats
            'previous_month' => Order::previousPeriod('created_at', 'month')
                ->aggregate([
                    'count' => '*',
                    'sum' => 'total',
                ]),
            
            // Monthly trend
            'trend' => Order::trend('created_at', 'month', 'total', 'sum'),
            
            // Top products by category
            'by_category' => Order::groupByWithAggregations('category_id', [
                'count' => '*',
                'sum' => 'total',
            ]),
        ];
        
        // Calculate growth
        $growth = (($dashboard['month']['sum'] - $dashboard['previous_month']['sum']) 
                   / $dashboard['previous_month']['sum']) * 100;
    }

    /**
     * Example 14: Revenue Analysis
     * Comprehensive revenue analytics.
     */
    public function example14(): void
    {
        // Daily revenue trend for last 30 days
        $dailyRevenue = Order::dateRange('created_at', 
                Carbon::now()->subDays(30), 
                Carbon::now()
            )
            ->trend('created_at', 'day', 'total', 'sum');
        
        // Revenue by customer segment
        $segmentRevenue = Order::groupByWithAggregations('customer_segment', [
            'count' => '*',
            'sum' => 'total',
            'avg' => 'total',
        ]);
        
        // Revenue distribution
        $distribution = Order::histogram('total', 10);
        
        // Revenue statistics
        $stats = Order::statisticalSummary('total');
        
        // Revenue correlation with marketing
        $correlation = Order::correlation('marketing_spend', 'total');
    }

    /**
     * Example 15: Inventory Analysis
     * Product and inventory analytics.
     */
    public function example15(): void
    {
        // Stock level statistics
        $stockStats = Product::statisticalSummary('stock_quantity');
        
        // Low stock products (below 25th percentile)
        $percentiles = Product::percentiles('stock_quantity', [25]);
        $lowStockThreshold = $percentiles['p25'];
        $lowStock = Product::where('stock_quantity', '<', $lowStockThreshold)->get();
        
        // Price distribution by category
        $priceByCategory = Product::groupByWithAggregations('category_id', [
            'avg' => 'price',
            'min' => 'price',
            'max' => 'price',
        ]);
        
        // Stock trend over time
        $stockTrend = Product::trend('updated_at', 'day', 'stock_quantity', 'avg');
    }

    /**
     * Example 16: Customer Analytics
     * Customer behavior analysis.
     */
    public function example16(): void
    {
        // Order value distribution
        $orderValues = Order::percentiles('total', [25, 50, 75, 90, 95]);
        
        // Average order value by month
        $monthlyAOV = Order::trend('created_at', 'month', 'total', 'avg');
        
        // Customer lifetime value stats
        $clvStats = Customer::statisticalSummary('lifetime_value');
        
        // Orders per customer
        $ordersPerCustomer = Order::groupByWithAggregations('customer_id', [
            'count' => '*',
            'sum' => 'total',
            'avg' => 'total',
        ]);
    }

    /**
     * Example 17: Performance Monitoring
     * Monitor key performance indicators.
     */
    public function example17(): void
    {
        // Conversion rate trend
        $conversions = Order::trend('created_at', 'day');
        $visitors = Visit::trend('created_at', 'day');
        
        // Average response time
        $responseTime = SupportTicket::statisticalSummary('response_time_minutes');
        
        // Service level percentiles
        $sla = SupportTicket::percentiles('resolution_time_hours', [50, 90, 95, 99]);
        
        // Performance correlation
        $correlation = Metric::correlation('response_time', 'customer_satisfaction');
    }

    /**
     * Example 18: Seasonal Analysis
     * Analyze seasonal patterns.
     */
    public function example18(): void
    {
        // Quarterly sales
        $quarterly = Order::trend('created_at', 'quarter', 'total', 'sum');
        
        // Month-over-month comparison
        $thisMonth = Order::currentPeriod('created_at', 'month')
            ->aggregate(['sum' => 'total']);
        $lastMonth = Order::previousPeriod('created_at', 'month')
            ->aggregate(['sum' => 'total']);
        
        // Year-over-year comparison
        $thisYear = Order::currentPeriod('created_at', 'year')
            ->aggregate(['sum' => 'total']);
        $lastYear = Order::previousPeriod('created_at', 'year')
            ->aggregate(['sum' => 'total']);
    }

    /**
     * Example 19: Forecasting Data Preparation
     * Prepare data for forecasting models.
     */
    public function example19(): void
    {
        // Historical trend with moving average
        $historical = Order::dateRange('created_at',
                Carbon::now()->subYear(),
                Carbon::now()
            )
            ->trend('created_at', 'day', 'total', 'sum');
        
        // Smooth with moving average
        $smoothed = Order::movingAverage('total', 30, 'created_at');
        
        // Get statistical baseline
        $baseline = Order::statisticalSummary('total');
        
        // Identify outliers (beyond 2 standard deviations)
        $mean = $baseline['avg'];
        $stdDev = $baseline['std_dev'];
        $outliers = Order::where('total', '>', $mean + (2 * $stdDev))
            ->orWhere('total', '<', $mean - (2 * $stdDev))
            ->get();
    }

    /**
     * Example 20: API Response Example
     * Format aggregations for API responses.
     */
    public function example20(): void
    {
        /*
        // In your controller
        public function analytics(Request $request)
        {
            return response()->json([
                'summary' => Order::statisticalSummary('total'),
                'trend' => Order::trend('created_at', 'month', 'total', 'sum'),
                'by_status' => Order::groupByWithAggregations('status', [
                    'count' => '*',
                    'sum' => 'total',
                ]),
                'distribution' => Order::histogram('total', 10),
                'current_period' => Order::currentPeriod('created_at', 'month')
                    ->aggregate([
                        'count' => '*',
                        'sum' => 'total',
                        'avg' => 'total',
                    ]),
                'previous_period' => Order::previousPeriod('created_at', 'month')
                    ->aggregate([
                        'count' => '*',
                        'sum' => 'total',
                        'avg' => 'total',
                    ]),
            ]);
        }
        */
    }
}

/**
 * USAGE IN MODELS
 */

/*
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Litepie\Database\Traits\Aggregatable;

class Order extends Model
{
    use Aggregatable;
    
    // The trait automatically works with your model
}

class Product extends Model
{
    use Aggregatable;
    
    // All aggregation methods are now available
}
*/

/**
 * CONTROLLER EXAMPLES
 */

/*
namespace App\Http\Controllers;

use App\Models\Order;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    public function dashboard()
    {
        $analytics = [
            'today' => Order::currentPeriod('created_at', 'day')
                ->aggregate(['count' => '*', 'sum' => 'total']),
            
            'month' => Order::currentPeriod('created_at', 'month')
                ->aggregate(['count' => '*', 'sum' => 'total']),
            
            'trend' => Order::trend('created_at', 'day', 'total', 'sum'),
            
            'stats' => Order::statisticalSummary('total'),
        ];
        
        return view('dashboard', compact('analytics'));
    }
    
    public function revenue()
    {
        return response()->json([
            'daily' => Order::trend('created_at', 'day', 'total', 'sum'),
            'monthly' => Order::trend('created_at', 'month', 'total', 'sum'),
            'by_category' => Order::groupByWithAggregations('category_id', [
                'sum' => 'total',
                'avg' => 'total',
            ]),
        ]);
    }
}
*/
