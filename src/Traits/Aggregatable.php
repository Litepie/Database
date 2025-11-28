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
}
