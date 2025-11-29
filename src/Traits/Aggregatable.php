<?php

namespace Litepie\Database\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Trait Aggregatable
 *
 * Provides advanced aggregation and statistical analysis capabilities for Eloquent models.
 * This trait enables complex data analysis operations including statistical calculations,
 * trend analysis, pivot tables, and more.
 *
 * @package Litepie\Database\Traits
 */
trait Aggregatable
{
    /**
     * Perform multiple aggregation operations in one call.
     *
     * Supports: count, sum, avg/average, min, max, median, mode, variance, stddev
     *
     * Example:
     * ```php
     * $results = Product::aggregate([
     *     'count' => '*',
     *     'sum' => 'price',
     *     'avg' => 'price',
     *     'min' => 'price',
     *     'max' => 'price',
     *     'median' => 'price',
     *     'stddev' => 'price'
     * ]);
     * ```
     *
     * @param array $operations Associative array of operation => field
     * @return array Results keyed by operation name
     */
    public static function aggregate(array $operations): array
    {
        $query = static::query();
        $results = [];

        foreach ($operations as $operation => $field) {
            switch (strtolower($operation)) {
                case 'count':
                    $results[$operation] = $query->count($field);
                    break;
                case 'sum':
                    $results[$operation] = $query->sum($field);
                    break;
                case 'avg':
                case 'average':
                    $results[$operation] = $query->avg($field);
                    break;
                case 'min':
                    $results[$operation] = $query->min($field);
                    break;
                case 'max':
                    $results[$operation] = $query->max($field);
                    break;
                case 'median':
                    $results[$operation] = static::calculateMedian($field);
                    break;
                case 'mode':
                    $results[$operation] = static::calculateMode($field);
                    break;
                case 'variance':
                    $results[$operation] = static::calculateVariance($field);
                    break;
                case 'stddev':
                    $results[$operation] = static::calculateStandardDeviation($field);
                    break;
            }
        }

        return $results;
    }

    /**
     * Group by a field with aggregations.
     *
     * Example:
     * ```php
     * $stats = Product::groupByWithAggregations('category_id', [
     *     'count' => '*',
     *     'sum' => 'price',
     *     'avg' => 'price'
     * ]);
     * ```
     *
     * @param string $field Field to group by
     * @param array $aggregations Aggregation operations
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function groupByWithAggregations(string $field, array $aggregations = ['count' => '*']): Collection
    {
        $query = static::query()->groupBy($field)->select($field);

        foreach ($aggregations as $operation => $aggField) {
            switch (strtolower($operation)) {
                case 'count':
                    $query->selectRaw("COUNT({$aggField}) as {$operation}");
                    break;
                case 'sum':
                    $query->selectRaw("SUM({$aggField}) as {$operation}");
                    break;
                case 'avg':
                    $query->selectRaw("AVG({$aggField}) as {$operation}");
                    break;
                case 'min':
                    $query->selectRaw("MIN({$aggField}) as {$operation}");
                    break;
                case 'max':
                    $query->selectRaw("MAX({$aggField}) as {$operation}");
                    break;
            }
        }

        return $query->get();
    }

    /**
     * Create a pivot table from data.
     *
     * Example:
     * ```php
     * $pivot = Order::pivot('customer_id', 'month', 'total', 'sum');
     * // Returns: ['data' => [...], 'columns' => [...], 'rows' => [...]]
     * ```
     *
     * @param string $rows Field for rows
     * @param string $cols Field for columns
     * @param string $values Field for values
     * @param string $aggregation Aggregation type (sum, avg, count, min, max)
     * @return array Pivot table structure
     */
    public static function pivot(string $rows, string $cols, string $values, string $aggregation = 'sum'): array
    {
        $data = static::query()
            ->select($rows, $cols, $values)
            ->get()
            ->groupBy($rows);

        $pivot = [];
        $columns = $data->flatten(1)->pluck($cols)->unique()->sort()->values();

        foreach ($data as $rowKey => $rowData) {
            $pivot[$rowKey] = [];

            foreach ($columns as $col) {
                $filtered = $rowData->where($cols, $col);

                switch ($aggregation) {
                    case 'sum':
                        $pivot[$rowKey][$col] = $filtered->sum($values);
                        break;
                    case 'avg':
                        $pivot[$rowKey][$col] = $filtered->avg($values);
                        break;
                    case 'count':
                        $pivot[$rowKey][$col] = $filtered->count();
                        break;
                    case 'max':
                        $pivot[$rowKey][$col] = $filtered->max($values);
                        break;
                    case 'min':
                        $pivot[$rowKey][$col] = $filtered->min($values);
                        break;
                    default:
                        $pivot[$rowKey][$col] = $filtered->sum($values);
                }
            }
        }

        return [
            'data' => $pivot,
            'columns' => $columns->toArray(),
            'rows' => array_keys($pivot)
        ];
    }

    /**
     * Get trend data over time.
     *
     * Example:
     * ```php
     * // Daily order count
     * $trend = Order::trend('created_at', 'day');
     *
     * // Monthly revenue
     * $trend = Order::trend('created_at', 'month', 'total', 'sum');
     * ```
     *
     * @param string $dateField Date field to analyze
     * @param string $interval Interval (minute, hour, day, week, month, quarter, year)
     * @param string|null $valueField Field to aggregate
     * @param string $aggregation Aggregation type (count, sum, avg, min, max)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function trend(string $dateField, string $interval = 'day', string $valueField = null, string $aggregation = 'count'): Collection
    {
        $format = static::getDateFormat($interval);
        $selectRaw = "DATE_FORMAT({$dateField}, '{$format}') as period";

        $query = static::query()
            ->selectRaw($selectRaw)
            ->groupBy('period')
            ->orderBy('period');

        if ($valueField && $aggregation !== 'count') {
            $query->selectRaw(strtoupper($aggregation) . "({$valueField}) as value");
        } else {
            $query->selectRaw("COUNT(*) as value");
        }

        return $query->get();
    }

    /**
     * Calculate percentiles for a field.
     *
     * Example:
     * ```php
     * $percentiles = Product::percentiles('price', [25, 50, 75, 90, 95, 99]);
     * // Returns: ['p25' => 10.50, 'p50' => 25.00, 'p75' => 45.00, ...]
     * ```
     *
     * @param string $field Field to calculate percentiles for
     * @param array $percentiles Array of percentile values to calculate
     * @return array Percentile values keyed by 'p{percentile}'
     */
    public static function percentiles(string $field, array $percentiles = [25, 50, 75, 90, 95, 99]): array
    {
        $values = static::query()->pluck($field)->sort()->values();
        $count = $values->count();

        if ($count === 0) {
            return [];
        }

        $results = [];

        foreach ($percentiles as $percentile) {
            $index = ($percentile / 100) * ($count - 1);
            $lower = floor($index);
            $upper = ceil($index);

            if ($lower === $upper) {
                $results["p{$percentile}"] = $values[$lower];
            } else {
                $lowerValue = $values[$lower];
                $upperValue = $values[$upper];
                $fraction = $index - $lower;
                $results["p{$percentile}"] = $lowerValue + ($fraction * ($upperValue - $lowerValue));
            }
        }

        return $results;
    }

    /**
     * Calculate moving average.
     *
     * Example:
     * ```php
     * $ma = Product::movingAverage('price', 7, 'created_at');
     * // Returns collection with period, value, and moving_average
     * ```
     *
     * @param string $field Field to calculate moving average for
     * @param int $window Window size (number of periods)
     * @param string $orderBy Field to order by
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function movingAverage(string $field, int $window = 7, string $orderBy = 'created_at'): Collection
    {
        $data = static::query()->orderBy($orderBy)->get();
        $results = collect();

        for ($i = $window - 1; $i < $data->count(); $i++) {
            $windowData = $data->slice($i - $window + 1, $window);
            $average = $windowData->avg($field);

            $results->push([
                'period' => $data[$i]->$orderBy,
                'value' => $data[$i]->$field,
                'moving_average' => round($average, 2),
                'window_size' => $window
            ]);
        }

        return $results;
    }

    /**
     * Generate histogram data.
     *
     * Example:
     * ```php
     * $histogram = Product::histogram('price', 10);
     * // Returns array of bins with range, count, and percentage
     * ```
     *
     * @param string $field Field to create histogram for
     * @param int $bins Number of bins
     * @return array Histogram data
     */
    public static function histogram(string $field, int $bins = 10): array
    {
        $min = static::query()->min($field);
        $max = static::query()->max($field);
        $range = $max - $min;
        $binWidth = $range / $bins;

        $histogram = [];

        for ($i = 0; $i < $bins; $i++) {
            $start = $min + ($i * $binWidth);
            $end = $start + $binWidth;

            $count = static::query()
                ->where($field, '>=', $start)
                ->where($field, $i === $bins - 1 ? '<=' : '<', $end)
                ->count();

            $histogram[] = [
                'bin' => $i + 1,
                'range' => round($start, 2) . '-' . round($end, 2),
                'start' => round($start, 2),
                'end' => round($end, 2),
                'count' => $count,
                'percentage' => 0 // Will be calculated below
            ];
        }

        $total = array_sum(array_column($histogram, 'count'));

        foreach ($histogram as &$bin) {
            $bin['percentage'] = $total > 0 ? round(($bin['count'] / $total) * 100, 2) : 0;
        }

        return $histogram;
    }

    /**
     * Calculate correlation between two fields.
     *
     * Returns a value between -1 and 1:
     * - 1 = perfect positive correlation
     * - 0 = no correlation
     * - -1 = perfect negative correlation
     *
     * Example:
     * ```php
     * $correlation = Product::correlation('price', 'sales');
     * ```
     *
     * @param string $field1 First field
     * @param string $field2 Second field
     * @return float Correlation coefficient
     */
    public static function correlation(string $field1, string $field2): float
    {
        $data = static::query()->select($field1, $field2)->get();

        if ($data->count() < 2) {
            return 0;
        }

        $x = $data->pluck($field1);
        $y = $data->pluck($field2);

        $meanX = $x->avg();
        $meanY = $y->avg();

        $numerator = 0;
        $sumXSquared = 0;
        $sumYSquared = 0;

        for ($i = 0; $i < $data->count(); $i++) {
            $xDiff = $x[$i] - $meanX;
            $yDiff = $y[$i] - $meanY;

            $numerator += $xDiff * $yDiff;
            $sumXSquared += $xDiff * $xDiff;
            $sumYSquared += $yDiff * $yDiff;
        }

        $denominator = sqrt($sumXSquared * $sumYSquared);

        return $denominator > 0 ? round($numerator / $denominator, 4) : 0;
    }

    /**
     * Get comprehensive statistical summary.
     *
     * Example:
     * ```php
     * $summary = Product::statisticalSummary('price');
     * // Returns: count, sum, avg, min, max, median, mode, variance, std_dev, percentiles
     * ```
     *
     * @param string $field Field to analyze
     * @return array Statistical summary
     */
    public static function statisticalSummary(string $field): array
    {
        return [
            'count' => static::query()->count(),
            'sum' => static::query()->sum($field),
            'avg' => round(static::query()->avg($field), 2),
            'min' => static::query()->min($field),
            'max' => static::query()->max($field),
            'median' => static::calculateMedian($field),
            'mode' => static::calculateMode($field),
            'variance' => round(static::calculateVariance($field), 4),
            'std_dev' => round(static::calculateStandardDeviation($field), 4),
            'percentiles' => static::percentiles($field)
        ];
    }

    /**
     * Calculate median value for a field.
     *
     * @param string $field Field to calculate median for
     * @return float Median value
     */
    protected static function calculateMedian(string $field): float
    {
        $values = static::query()->pluck($field)->sort()->values();
        $count = $values->count();

        if ($count === 0) {
            return 0;
        }

        if ($count % 2 === 0) {
            $mid1 = $values[($count / 2) - 1];
            $mid2 = $values[$count / 2];
            return ($mid1 + $mid2) / 2;
        }

        return $values[floor($count / 2)];
    }

    /**
     * Calculate mode (most frequent value) for a field.
     *
     * @param string $field Field to calculate mode for
     * @return mixed Mode value
     */
    protected static function calculateMode(string $field)
    {
        $values = static::query()->pluck($field);
        $frequencies = $values->countBy();

        return $frequencies->sortDesc()->keys()->first();
    }

    /**
     * Calculate variance for a field.
     *
     * @param string $field Field to calculate variance for
     * @return float Variance value
     */
    protected static function calculateVariance(string $field): float
    {
        $values = static::query()->pluck($field);
        $mean = $values->avg();

        $squaredDifferences = $values->map(function ($value) use ($mean) {
            return pow($value - $mean, 2);
        });

        return $squaredDifferences->avg();
    }

    /**
     * Calculate standard deviation for a field.
     *
     * @param string $field Field to calculate standard deviation for
     * @return float Standard deviation value
     */
    protected static function calculateStandardDeviation(string $field): float
    {
        return sqrt(static::calculateVariance($field));
    }

    /**
     * Get date format string for SQL DATE_FORMAT function.
     *
     * @param string $interval Interval type
     * @return string SQL date format string
     */
    protected static function getDateFormat(string $interval): string
    {
        switch ($interval) {
            case 'minute':
                return '%Y-%m-%d %H:%i';
            case 'hour':
                return '%Y-%m-%d %H';
            case 'day':
                return '%Y-%m-%d';
            case 'week':
                return '%Y-%u';
            case 'month':
                return '%Y-%m';
            case 'quarter':
                return '%Y-Q%q';
            case 'year':
                return '%Y';
            default:
                return '%Y-%m-%d';
        }
    }

    /**
     * Scope: Filter by date range for trend analysis.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $field Date field
     * @param string|\Carbon\Carbon $start Start date
     * @param string|\Carbon\Carbon $end End date
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeDateRange($query, string $field, $start, $end)
    {
        return $query->whereBetween($field, [$start, $end]);
    }

    /**
     * Scope: Filter records for the current period.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $field Date field
     * @param string $period Period (day, week, month, year)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCurrentPeriod($query, string $field = 'created_at', string $period = 'month')
    {
        switch ($period) {
            case 'day':
                return $query->whereDate($field, Carbon::today());
            case 'week':
                return $query->whereBetween($field, [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
            case 'month':
                return $query->whereMonth($field, Carbon::now()->month)
                    ->whereYear($field, Carbon::now()->year);
            case 'year':
                return $query->whereYear($field, Carbon::now()->year);
            default:
                return $query;
        }
    }

    /**
     * Scope: Filter records for the previous period.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $field Date field
     * @param string $period Period (day, week, month, year)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePreviousPeriod($query, string $field = 'created_at', string $period = 'month')
    {
        switch ($period) {
            case 'day':
                return $query->whereDate($field, Carbon::yesterday());
            case 'week':
                return $query->whereBetween($field, [
                    Carbon::now()->subWeek()->startOfWeek(),
                    Carbon::now()->subWeek()->endOfWeek()
                ]);
            case 'month':
                return $query->whereMonth($field, Carbon::now()->subMonth()->month)
                    ->whereYear($field, Carbon::now()->subMonth()->year);
            case 'year':
                return $query->whereYear($field, Carbon::now()->subYear()->year);
            default:
                return $query;
        }
    }

    /**
     * Compare metrics between current and previous period.
     *
     * Returns comparison data including current value, previous value,
     * absolute change, percentage change, and trend direction.
     *
     * Example:
     * ```php
     * $comparison = Order::compareWithPreviousPeriod('total', 'sum', 'month');
     * // Returns:
     * // [
     * //     'current' => 15000,
     * //     'previous' => 12000,
     * //     'change' => 3000,
     * //     'change_percent' => 25.0,
     * //     'trend' => 'up'
     * // ]
     * ```
     *
     * @param string $field Field to analyze
     * @param string $metric Metric type (sum, avg, count, min, max)
     * @param string $period Period (day, week, month, year)
     * @param string $dateField Date field to use for filtering
     * @return array Comparison data
     */
    public static function compareWithPreviousPeriod(string $field, string $metric = 'sum', string $period = 'month', string $dateField = 'created_at'): array
    {
        // Get current period value
        $currentQuery = static::query()->currentPeriod($dateField, $period);
        $current = static::applyMetric($currentQuery, $field, $metric);

        // Get previous period value
        $previousQuery = static::query()->previousPeriod($dateField, $period);
        $previous = static::applyMetric($previousQuery, $field, $metric);

        // Calculate changes
        $change = $current - $previous;
        $changePercent = $previous > 0 ? round(($change / $previous) * 100, 2) : 0;
        $trend = $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable');

        return [
            'current' => round($current, 2),
            'previous' => round($previous, 2),
            'change' => round($change, 2),
            'change_percent' => $changePercent,
            'trend' => $trend,
            'period' => $period,
        ];
    }

    /**
     * Calculate growth rate over a period.
     *
     * Example:
     * ```php
     * $growth = Order::growthRate('total', 'month', 6);
     * // Returns: ['period' => 'month', 'periods' => 6, 'growth_rate' => 15.5, 'data' => [...]]
     * ```
     *
     * @param string $field Field to calculate growth for
     * @param string $period Period interval (day, week, month, year)
     * @param int $periods Number of periods to analyze
     * @param string $dateField Date field to use
     * @return array Growth rate data
     */
    public static function growthRate(string $field, string $period = 'month', int $periods = 6, string $dateField = 'created_at'): array
    {
        $data = [];
        $values = [];

        for ($i = $periods - 1; $i >= 0; $i--) {
            $start = static::getPeriodStart($period, $i);
            $end = static::getPeriodEnd($period, $i);

            $value = static::query()
                ->whereBetween($dateField, [$start, $end])
                ->sum($field);

            $data[] = [
                'period' => $start->format('Y-m-d'),
                'value' => round($value, 2),
            ];

            $values[] = $value;
        }

        // Calculate average growth rate
        $growthRates = [];
        for ($i = 1; $i < count($values); $i++) {
            if ($values[$i - 1] > 0) {
                $growthRates[] = (($values[$i] - $values[$i - 1]) / $values[$i - 1]) * 100;
            }
        }

        $avgGrowthRate = count($growthRates) > 0 ? round(array_sum($growthRates) / count($growthRates), 2) : 0;

        return [
            'period' => $period,
            'periods' => $periods,
            'growth_rate' => $avgGrowthRate,
            'data' => $data,
        ];
    }

    /**
     * Get top N records by a field.
     *
     * Example:
     * ```php
     * $topProducts = Product::topN('sales', 10, 'DESC');
     * // Returns top 10 products by sales
     * ```
     *
     * @param string $field Field to rank by
     * @param int $n Number of records to return
     * @param string $direction Sort direction (ASC or DESC)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function topN(string $field, int $n = 10, string $direction = 'DESC'): Collection
    {
        return static::query()
            ->orderBy($field, $direction)
            ->limit($n)
            ->get();
    }

    /**
     * Get bottom N records by a field.
     *
     * Example:
     * ```php
     * $bottomProducts = Product::bottomN('sales', 10);
     * // Returns bottom 10 products by sales
     * ```
     *
     * @param string $field Field to rank by
     * @param int $n Number of records to return
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function bottomN(string $field, int $n = 10): Collection
    {
        return static::topN($field, $n, 'ASC');
    }

    /**
     * Rank records by a field with optional partitioning.
     *
     * Example:
     * ```php
     * $ranked = Product::rankBy('sales', 'category_id');
     * // Returns products ranked by sales within each category
     * ```
     *
     * @param string $field Field to rank by
     * @param string|null $partitionBy Field to partition by
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function rankBy(string $field, ?string $partitionBy = null): Collection
    {
        $table = (new static())->getTable();
        
        if ($partitionBy) {
            $sql = "ROW_NUMBER() OVER (PARTITION BY {$partitionBy} ORDER BY {$field} DESC) as rank";
        } else {
            $sql = "ROW_NUMBER() OVER (ORDER BY {$field} DESC) as rank";
        }

        return static::query()
            ->selectRaw("{$table}.*, {$sql}")
            ->get();
    }

    /**
     * Calculate running total for a field.
     *
     * Example:
     * ```php
     * $runningTotal = Order::runningTotal('total', 'created_at');
     * // Returns collection with original data plus running_total field
     * ```
     *
     * @param string $field Field to calculate running total for
     * @param string $orderBy Field to order by
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function runningTotal(string $field, string $orderBy = 'created_at'): Collection
    {
        $data = static::query()->orderBy($orderBy)->get();
        $runningTotal = 0;

        return $data->map(function ($item) use ($field, &$runningTotal) {
            $runningTotal += $item->$field;
            $item->running_total = round($runningTotal, 2);
            return $item;
        });
    }

    /**
     * Calculate cumulative average for a field.
     *
     * Example:
     * ```php
     * $cumulativeAvg = Order::cumulativeAverage('total', 'created_at');
     * // Returns collection with original data plus cumulative_average field
     * ```
     *
     * @param string $field Field to calculate cumulative average for
     * @param string $orderBy Field to order by
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function cumulativeAverage(string $field, string $orderBy = 'created_at'): Collection
    {
        $data = static::query()->orderBy($orderBy)->get();
        $sum = 0;
        $count = 0;

        return $data->map(function ($item) use ($field, &$sum, &$count) {
            $sum += $item->$field;
            $count++;
            $item->cumulative_average = round($sum / $count, 2);
            return $item;
        });
    }

    /**
     * Get trend data with gap filling for missing periods.
     *
     * Ensures all time periods are represented even if no data exists.
     *
     * Example:
     * ```php
     * $trend = Order::trendWithGapFilling('created_at', 'day', 0, 30);
     * // Returns 30 days of data, filling gaps with 0
     * ```
     *
     * @param string $dateField Date field to analyze
     * @param string $interval Interval (day, week, month, year)
     * @param mixed $fillValue Value to use for missing periods
     * @param int $periods Number of periods to include
     * @param string|null $valueField Field to aggregate
     * @param string $aggregation Aggregation type
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function trendWithGapFilling(
        string $dateField,
        string $interval = 'day',
        $fillValue = 0,
        int $periods = 30,
        ?string $valueField = null,
        string $aggregation = 'count'
    ): Collection {
        // Generate all expected periods
        $expectedPeriods = static::generatePeriods($interval, $periods);
        
        // Get actual data
        $actualData = static::trend($dateField, $interval, $valueField, $aggregation);
        $actualDataKeyed = $actualData->keyBy('period');

        // Fill gaps
        $results = collect();
        foreach ($expectedPeriods as $period) {
            if (isset($actualDataKeyed[$period])) {
                $results->push($actualDataKeyed[$period]);
            } else {
                $results->push([
                    'period' => $period,
                    'value' => $fillValue,
                ]);
            }
        }

        return $results;
    }

    /**
     * Aggregate by multiple dimensions.
     *
     * Example:
     * ```php
     * $stats = Order::aggregateBy(
     *     ['customer_id', 'product_id'],
     *     ['count' => '*', 'sum' => 'total', 'avg' => 'total']
     * );
     * ```
     *
     * @param array $groupFields Fields to group by
     * @param array $metrics Aggregation metrics
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function aggregateBy(array $groupFields, array $metrics): Collection
    {
        $query = static::query()->groupBy($groupFields)->select($groupFields);

        foreach ($metrics as $operation => $field) {
            switch (strtolower($operation)) {
                case 'count':
                    $query->selectRaw("COUNT({$field}) as count");
                    break;
                case 'sum':
                    $query->selectRaw("SUM({$field}) as sum");
                    break;
                case 'avg':
                    $query->selectRaw("AVG({$field}) as avg");
                    break;
                case 'min':
                    $query->selectRaw("MIN({$field}) as min");
                    break;
                case 'max':
                    $query->selectRaw("MAX({$field}) as max");
                    break;
            }
        }

        return $query->get();
    }

    /**
     * Calculate percentage share/distribution by group.
     *
     * Example:
     * ```php
     * $shares = Product::percentageShare('category_id', 'sales');
     * // Returns: [
     * //     ['category_id' => 1, 'value' => 5000, 'percentage' => 35.5, 'rank' => 1],
     * //     ['category_id' => 2, 'value' => 3000, 'percentage' => 21.3, 'rank' => 2],
     * //     ...
     * // ]
     * ```
     *
     * @param string $groupBy Field to group by
     * @param string $field Field to calculate share for
     * @param string $aggregation Aggregation type (sum, count, avg)
     * @return array Share data with percentages and ranking
     */
    public static function percentageShare(string $groupBy, string $field, string $aggregation = 'sum'): array
    {
        $data = static::groupByWithAggregations($groupBy, [$aggregation => $field]);
        $total = $data->sum($aggregation);

        $results = [];
        $rank = 1;

        foreach ($data->sortByDesc($aggregation) as $item) {
            $value = $item->$aggregation;
            $percentage = $total > 0 ? round(($value / $total) * 100, 2) : 0;

            $results[] = [
                $groupBy => $item->$groupBy,
                'value' => round($value, 2),
                'percentage' => $percentage,
                'rank' => $rank++,
            ];
        }

        return $results;
    }

    /**
     * Get year-over-year comparison.
     *
     * Example:
     * ```php
     * $yoy = Order::yearOverYear('total', 'sum', 'created_at');
     * ```
     *
     * @param string $field Field to compare
     * @param string $metric Metric type (sum, avg, count)
     * @param string $dateField Date field
     * @return array Year-over-year comparison
     */
    public static function yearOverYear(string $field, string $metric = 'sum', string $dateField = 'created_at'): array
    {
        $currentYear = Carbon::now()->year;
        $previousYear = $currentYear - 1;

        $current = static::applyMetric(
            static::query()->whereYear($dateField, $currentYear),
            $field,
            $metric
        );

        $previous = static::applyMetric(
            static::query()->whereYear($dateField, $previousYear),
            $field,
            $metric
        );

        $change = $current - $previous;
        $changePercent = $previous > 0 ? round(($change / $previous) * 100, 2) : 0;

        return [
            'current_year' => $currentYear,
            'current_value' => round($current, 2),
            'previous_year' => $previousYear,
            'previous_value' => round($previous, 2),
            'change' => round($change, 2),
            'change_percent' => $changePercent,
            'trend' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable'),
        ];
    }

    /**
     * Apply a metric to a query.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $field
     * @param string $metric
     * @return float
     */
    protected static function applyMetric($query, string $field, string $metric): float
    {
        return match (strtolower($metric)) {
            'sum' => $query->sum($field) ?? 0,
            'avg', 'average' => $query->avg($field) ?? 0,
            'count' => $query->count($field),
            'min' => $query->min($field) ?? 0,
            'max' => $query->max($field) ?? 0,
            default => 0,
        };
    }

    /**
     * Get period start date.
     *
     * @param string $period
     * @param int $offset
     * @return Carbon
     */
    protected static function getPeriodStart(string $period, int $offset): Carbon
    {
        return match ($period) {
            'day' => Carbon::now()->subDays($offset)->startOfDay(),
            'week' => Carbon::now()->subWeeks($offset)->startOfWeek(),
            'month' => Carbon::now()->subMonths($offset)->startOfMonth(),
            'year' => Carbon::now()->subYears($offset)->startOfYear(),
            default => Carbon::now()->subDays($offset)->startOfDay(),
        };
    }

    /**
     * Get period end date.
     *
     * @param string $period
     * @param int $offset
     * @return Carbon
     */
    protected static function getPeriodEnd(string $period, int $offset): Carbon
    {
        return match ($period) {
            'day' => Carbon::now()->subDays($offset)->endOfDay(),
            'week' => Carbon::now()->subWeeks($offset)->endOfWeek(),
            'month' => Carbon::now()->subMonths($offset)->endOfMonth(),
            'year' => Carbon::now()->subYears($offset)->endOfYear(),
            default => Carbon::now()->subDays($offset)->endOfDay(),
        };
    }

    /**
     * Generate array of period strings.
     *
     * @param string $interval
     * @param int $periods
     * @return array
     */
    protected static function generatePeriods(string $interval, int $periods): array
    {
        $result = [];
        $format = match ($interval) {
            'day' => 'Y-m-d',
            'week' => 'Y-W',
            'month' => 'Y-m',
            'year' => 'Y',
            default => 'Y-m-d',
        };

        for ($i = $periods - 1; $i >= 0; $i--) {
            $date = static::getPeriodStart($interval, $i);
            $result[] = $date->format($format);
        }

        return $result;
    }
}
