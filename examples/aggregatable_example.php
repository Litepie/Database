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

    /**
     * Example 21: Period-over-Period Comparison
     * Compare current period with previous period.
     */
    public function example21(): void
    {
        // Compare this month vs last month
        $comparison = Order::compareWithPreviousPeriod('total', 'sum', 'month');
        
        /*
        Returns:
        [
            'current' => 15000.00,
            'previous' => 12000.00,
            'change' => 3000.00,
            'change_percent' => 25.0,
            'trend' => 'up',
            'period' => 'month'
        ]
        */
        
        // Compare today vs yesterday
        $dailyComparison = Order::compareWithPreviousPeriod('total', 'sum', 'day');
        
        // Compare average order value
        $avgComparison = Order::compareWithPreviousPeriod('total', 'avg', 'month');
        
        // Compare order count
        $countComparison = Order::compareWithPreviousPeriod('*', 'count', 'week');
    }

    /**
     * Example 22: Growth Rate Analysis
     * Calculate growth rate over multiple periods.
     */
    public function example22(): void
    {
        // Calculate 6-month growth rate
        $growth = Order::growthRate('total', 'month', 6);
        
        /*
        Returns:
        [
            'period' => 'month',
            'periods' => 6,
            'growth_rate' => 15.5,  // Average monthly growth
            'data' => [
                ['period' => '2024-06-01', 'value' => 10000],
                ['period' => '2024-07-01', 'value' => 11500],
                ['period' => '2024-08-01', 'value' => 13000],
                ...
            ]
        ]
        */
        
        // Daily growth for last 30 days
        $dailyGrowth = Product::growthRate('sales', 'day', 30);
        
        // Yearly growth for last 5 years
        $yearlyGrowth = Order::growthRate('total', 'year', 5);
    }

    /**
     * Example 23: Top and Bottom N Records
     * Get top/bottom performers.
     */
    public function example23(): void
    {
        // Top 10 products by sales
        $topProducts = Product::topN('sales', 10);
        
        // Top 20 customers by total orders
        $topCustomers = Order::topN('total', 20);
        
        // Bottom 5 performing products
        $bottomProducts = Product::bottomN('sales', 5);
        
        // Custom ordering (ascending)
        $cheapest = Product::topN('price', 10, 'ASC');
    }

    /**
     * Example 24: Ranking with Partitioning
     * Rank records overall or within groups.
     */
    public function example24(): void
    {
        // Rank all products by sales
        $rankedProducts = Product::rankBy('sales');
        
        /*
        Returns each product with a 'rank' field:
        [
            ['id' => 5, 'name' => 'Widget', 'sales' => 1000, 'rank' => 1],
            ['id' => 2, 'name' => 'Gadget', 'sales' => 800, 'rank' => 2],
            ...
        ]
        */
        
        // Rank products within each category
        $rankedByCategory = Product::rankBy('sales', 'category_id');
        
        /*
        Returns products ranked within their category:
        [
            ['id' => 5, 'category_id' => 1, 'sales' => 1000, 'rank' => 1],
            ['id' => 8, 'category_id' => 1, 'sales' => 800, 'rank' => 2],
            ['id' => 3, 'category_id' => 2, 'sales' => 950, 'rank' => 1],
            ['id' => 7, 'category_id' => 2, 'sales' => 700, 'rank' => 2],
            ...
        ]
        */
    }

    /**
     * Example 25: Running Totals
     * Calculate cumulative sums over time.
     */
    public function example25(): void
    {
        // Running total of order amounts
        $runningTotal = Order::runningTotal('total', 'created_at');
        
        /*
        Returns:
        [
            ['id' => 1, 'total' => 100, 'created_at' => '...', 'running_total' => 100],
            ['id' => 2, 'total' => 150, 'created_at' => '...', 'running_total' => 250],
            ['id' => 3, 'total' => 75, 'created_at' => '...', 'running_total' => 325],
            ...
        ]
        */
        
        // Running total for specific period
        $monthlyRunningTotal = Order::query()
            ->whereMonth('created_at', now()->month)
            ->get()
            ->pipe(function ($orders) {
                $runningTotal = 0;
                return $orders->map(function ($order) use (&$runningTotal) {
                    $runningTotal += $order->total;
                    $order->running_total = $runningTotal;
                    return $order;
                });
            });
    }

    /**
     * Example 26: Cumulative Average
     * Calculate running average over time.
     */
    public function example26(): void
    {
        // Cumulative average of order amounts
        $cumulativeAvg = Order::cumulativeAverage('total', 'created_at');
        
        /*
        Returns:
        [
            ['id' => 1, 'total' => 100, 'cumulative_average' => 100],
            ['id' => 2, 'total' => 150, 'cumulative_average' => 125],
            ['id' => 3, 'total' => 75, 'cumulative_average' => 108.33],
            ...
        ]
        */
        
        // Track how average changes over time
        $productAvg = Product::cumulativeAverage('price', 'created_at');
    }

    /**
     * Example 27: Trend with Gap Filling
     * Get trend data with missing periods filled.
     */
    public function example27(): void
    {
        // Last 30 days of orders, filling gaps with 0
        $trend = Order::trendWithGapFilling('created_at', 'day', 0, 30);
        
        /*
        Returns 30 records, one for each day, even if no orders:
        [
            ['period' => '2024-11-01', 'value' => 5],
            ['period' => '2024-11-02', 'value' => 0],  // No orders, filled
            ['period' => '2024-11-03', 'value' => 8],
            ...
        ]
        */
        
        // Last 12 months with revenue
        $monthlyRevenue = Order::trendWithGapFilling(
            'created_at',
            'month',
            0,
            12,
            'total',
            'sum'
        );
        
        // Weekly sales with null for missing weeks
        $weeklySales = Product::trendWithGapFilling(
            'created_at',
            'week',
            null,
            52,
            'sales',
            'sum'
        );
    }

    /**
     * Example 28: Multi-dimensional Aggregation
     * Aggregate by multiple fields at once.
     */
    public function example28(): void
    {
        // Sales by customer and product
        $stats = Order::aggregateBy(
            ['customer_id', 'product_id'],
            ['count' => '*', 'sum' => 'total', 'avg' => 'total']
        );
        
        /*
        Returns:
        [
            [
                'customer_id' => 1,
                'product_id' => 5,
                'count' => 10,
                'sum' => 1000,
                'avg' => 100
            ],
            ...
        ]
        */
        
        // Sales by region and category
        $regionalStats = Order::aggregateBy(
            ['region', 'category_id'],
            ['count' => '*', 'sum' => 'total']
        );
    }

    /**
     * Example 29: Percentage Share Analysis
     * Calculate distribution and market share.
     */
    public function example29(): void
    {
        // Sales share by category
        $categoryShare = Product::percentageShare('category_id', 'sales');
        
        /*
        Returns:
        [
            ['category_id' => 1, 'value' => 5000, 'percentage' => 35.5, 'rank' => 1],
            ['category_id' => 3, 'value' => 3500, 'percentage' => 24.8, 'rank' => 2],
            ['category_id' => 2, 'value' => 3000, 'percentage' => 21.3, 'rank' => 3],
            ['category_id' => 4, 'value' => 2600, 'percentage' => 18.4, 'rank' => 4],
        ]
        */
        
        // Revenue share by customer
        $customerShare = Order::percentageShare('customer_id', 'total', 'sum');
        
        // Product count share by supplier
        $supplierShare = Product::percentageShare('supplier_id', '*', 'count');
    }

    /**
     * Example 30: Year-over-Year Comparison
     * Compare current year with previous year.
     */
    public function example30(): void
    {
        // Revenue YoY comparison
        $yoy = Order::yearOverYear('total', 'sum');
        
        /*
        Returns:
        [
            'current_year' => 2024,
            'current_value' => 150000.00,
            'previous_year' => 2023,
            'previous_value' => 120000.00,
            'change' => 30000.00,
            'change_percent' => 25.0,
            'trend' => 'up'
        ]
        */
        
        // Average order value YoY
        $avgYoY = Order::yearOverYear('total', 'avg');
        
        // Customer count YoY
        $customerYoY = Order::yearOverYear('customer_id', 'count');
    }

    /**
     * Example 31: Complete Dashboard Analytics
     * Comprehensive analytics for a dashboard.
     */
    public function example31(): void
    {
        // Current period metrics
        $current = Order::currentPeriod('created_at', 'month')
            ->aggregate(['count' => '*', 'sum' => 'total', 'avg' => 'total']);
        
        // Comparison with previous period
        $comparison = Order::compareWithPreviousPeriod('total', 'sum', 'month');
        
        // Growth rate trend
        $growth = Order::growthRate('total', 'month', 6);
        
        // Daily trend for current month
        $dailyTrend = Order::query()
            ->currentPeriod('created_at', 'month')
            ->get()
            ->pipe(function ($orders) {
                return Order::trendWithGapFilling('created_at', 'day', 0, 30);
            });
        
        // Top performers
        $topProducts = Product::topN('sales', 10);
        $topCustomers = Order::aggregateBy(['customer_id'], ['sum' => 'total'])
            ->sortByDesc('sum')
            ->take(10);
        
        // Distribution analysis
        $categoryShare = Product::percentageShare('category_id', 'sales');
        
        // Year-over-year
        $yoy = Order::yearOverYear('total', 'sum');
        
        $dashboardData = [
            'current' => $current,
            'comparison' => $comparison,
            'growth' => $growth,
            'daily_trend' => $dailyTrend,
            'top_products' => $topProducts,
            'top_customers' => $topCustomers,
            'category_distribution' => $categoryShare,
            'yoy_comparison' => $yoy,
        ];
        
        // Example usage: response()->json($dashboardData);
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
use App\Models\Product;
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
            
            'comparison' => Order::compareWithPreviousPeriod('total', 'sum', 'month'),
            
            'growth' => Order::growthRate('total', 'month', 6),
            
            'trend' => Order::trendWithGapFilling('created_at', 'day', 0, 30),
            
            'stats' => Order::statisticalSummary('total'),
            
            'top_products' => Product::topN('sales', 10),
            
            'yoy' => Order::yearOverYear('total', 'sum'),
        ];
        
        return view('dashboard', compact('analytics'));
    }
    
    public function revenue()
    {
        return response()->json([
            'daily' => Order::trendWithGapFilling('created_at', 'day', 0, 30, 'total', 'sum'),
            'monthly' => Order::trend('created_at', 'month', 'total', 'sum'),
            'by_category' => Product::percentageShare('category_id', 'sales'),
            'growth_rate' => Order::growthRate('total', 'month', 12),
        ]);
    }
    
    public function performance()
    {
        return response()->json([
            'top_performers' => Product::topN('sales', 20),
            'bottom_performers' => Product::bottomN('sales', 10),
            'ranked_by_category' => Product::rankBy('sales', 'category_id'),
            'running_totals' => Order::runningTotal('total', 'created_at'),
        ]);
    }
}
*/


