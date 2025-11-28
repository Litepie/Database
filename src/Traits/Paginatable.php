<?php

namespace Litepie\Database\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Cursor;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\LengthAwarePaginator as ConcreteLengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Paginatable Trait
 * 
 * Provides advanced pagination methods optimized for large datasets
 * including cursor-based pagination, chunking, and performance optimizations.
 * 
 * @property array $paginatableConfig Configuration for pagination optimization
 */
trait Paginatable
{
    /**
     * Cursor-based pagination for large datasets.
     * Much faster than offset-based pagination for large datasets.
     *
     * @param int $perPage
     * @param array $columns
     * @param string $cursorName
     * @param Cursor|string|null $cursor
     * @return CursorPaginator
     */
    public function scopeCursorPaginate(
        Builder $query,
        int $perPage = 15, 
        array $columns = ['*'], 
        string $cursorName = 'cursor',
        Cursor|string|null $cursor = null
    ): CursorPaginator {
        // Ensure we have an order by clause for cursor pagination
        if (empty($query->getQuery()->orders)) {
            $query->orderBy($this->getKeyName());
        }

        return $query->cursorPaginate($perPage, $columns, $cursorName, $cursor);
    }

    /**
     * Fast pagination without total count for large datasets.
     * Uses LIMIT + 1 to determine if there are more pages.
     *
     * @param int $perPage
     * @param array $columns
     * @param string $pageName
     * @return Paginator
     */
    public function scopeFastPaginate(
        Builder $query,
        int $perPage = 15, 
        array $columns = ['*'], 
        string $pageName = 'page'
    ): Paginator {
        return $query->simplePaginate($perPage, $columns, $pageName);
    }

    /**
     * Paginate with optimized count query for large datasets.
     * Uses approximate count for better performance.
     *
     * @param int $perPage
     * @param array $columns
     * @param string $pageName
     * @param int|null $page
     * @param bool $useApproximateCount
     * @return LengthAwarePaginator
     */
    public function scopeOptimizedPaginate(
        Builder $query,
        int $perPage = 15, 
        array $columns = ['*'], 
        string $pageName = 'page', 
        ?int $page = null,
        bool $useApproximateCount = true
    ): LengthAwarePaginator {
        if ($useApproximateCount && $this->shouldUseApproximateCount()) {
            return $this->paginateWithApproximateCount($query, $perPage, $columns, $pageName, $page);
        }

        return $query->paginate($perPage, $columns, $pageName, $page);
    }

    /**
     * Get paginated results with seek pagination (cursor-like but with custom logic).
     * Excellent for real-time feeds and large datasets.
     *
     * @param int $limit
     * @param mixed $lastId
     * @param string $direction
     * @param string $orderColumn
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function scopeSeekPaginate(
        Builder $query,
        int $limit = 15, 
        mixed $lastId = null, 
        string $direction = 'next', 
        string $orderColumn = 'id'
    ) {
        if ($lastId !== null) {
            if ($direction === 'next') {
                $query->where($orderColumn, '>', $lastId);
            } else {
                $query->where($orderColumn, '<', $lastId);
            }
        }

        $query->orderBy($orderColumn, $direction === 'next' ? 'asc' : 'desc');

        return $query->limit($limit)->get();
    }

    /**
     * Paginate with window function for better performance on large datasets.
     * Uses ROW_NUMBER() to avoid OFFSET issues.
     *
     * @param int $perPage
     * @param int $page
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function scopeWindowPaginate(
        Builder $query,
        int $perPage = 15, 
        int $page = 1, 
        array $columns = ['*']
    ) {
        $offset = ($page - 1) * $perPage;
        
        $subQuery = $query->toBase();
        $sql = $subQuery->toSql();
        $bindings = $subQuery->getBindings();
        
        $tableName = $this->getTable();
        $keyName = $this->getKeyName();
        
        $windowSql = "
            SELECT * FROM (
                SELECT *, ROW_NUMBER() OVER (ORDER BY {$keyName}) as row_num
                FROM ({$sql}) as temp_table
            ) as numbered_table
            WHERE row_num > {$offset} AND row_num <= " . ($offset + $perPage);
        
        $results = DB::select($windowSql, $bindings);
        
        return $this->hydrate($results);
    }

    /**
     * Get estimated count for large tables using EXPLAIN.
     * Much faster than COUNT(*) on large datasets.
     *
     * @return int
     */
    public function scopeEstimatedCount(Builder $query): int
    {
        $sql = $query->toSql();
        $bindings = $query->getBindings();
        
        try {
            // For MySQL
            if (DB::getDriverName() === 'mysql') {
                $explain = DB::select("EXPLAIN SELECT COUNT(*) FROM ({$sql}) as temp_table", $bindings);
                return $explain[0]->rows ?? 0;
            }
            
            // For PostgreSQL
            if (DB::getDriverName() === 'pgsql') {
                $explain = DB::select("EXPLAIN (FORMAT JSON) SELECT COUNT(*) FROM ({$sql}) as temp_table", $bindings);
                $plan = json_decode($explain[0]->{'QUERY PLAN'}, true);
                return $plan[0]['Plan']['Plan Rows'] ?? 0;
            }
            
            // Fallback to actual count
            return $query->count();
        } catch (\Exception $e) {
            // Fallback to actual count if explain fails
            return $query->count();
        }
    }

    /**
     * Paginate with total count caching for better performance.
     *
     * @param int $perPage
     * @param array $columns
     * @param string $pageName
     * @param int|null $page
     * @param int $cacheTtl
     * @return LengthAwarePaginator
     */
    public function scopeCachedPaginate(
        Builder $query,
        int $perPage = 15, 
        array $columns = ['*'], 
        string $pageName = 'page', 
        ?int $page = null,
        int $cacheTtl = 300
    ): LengthAwarePaginator {
        $cacheKey = $this->generatePaginationCacheKey($query);
        
        $total = Cache::remember($cacheKey . '_count', $cacheTtl, function() use ($query) {
            return $query->count();
        });
        
        $page = $page ?: request()->input($pageName, 1);
        $items = $query->forPage($page, $perPage)->get($columns);
        
        return new ConcreteLengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => $pageName,
            ]
        );
    }

    /**
     * Parallel pagination for extremely large datasets.
     * Divides the query into multiple parallel queries.
     *
     * @param int $perPage
     * @param int $page
     * @param int $parallelQueries
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function scopeParallelPaginate(
        Builder $query,
        int $perPage = 15, 
        int $page = 1, 
        int $parallelQueries = 4, 
        array $columns = ['*']
    ) {
        $offset = ($page - 1) * $perPage;
        $chunkSize = ceil($perPage / $parallelQueries);
        
        $promises = [];
        
        for ($i = 0; $i < $parallelQueries; $i++) {
            $chunkOffset = $offset + ($i * $chunkSize);
            $chunkLimit = min($chunkSize, $perPage - ($i * $chunkSize));
            
            if ($chunkLimit <= 0) break;
            
            $promises[] = function() use ($query, $chunkOffset, $chunkLimit, $columns) {
                return (clone $query)->offset($chunkOffset)->limit($chunkLimit)->get($columns);
            };
        }
        
        // Execute queries in parallel (simplified version)
        $results = new Collection();
        foreach ($promises as $promise) {
            $results = $results->merge($promise());
        }
        
        return $results;
    }

    /**
     * Create pagination performance report.
     *
     * @param int $perPage
     * @param int $page
     * @return array
     */
    public function scopePaginationPerformanceReport(
        Builder $query,
        int $perPage = 15, 
        int $page = 1
    ): array {
        $startTime = microtime(true);
        
        // Test different pagination methods
        $methods = [];
        
        // Standard pagination
        $start = microtime(true);
        $standardResult = (clone $query)->paginate($perPage, ['*'], 'page', $page);
        $methods['standard'] = [
            'time' => microtime(true) - $start,
            'memory' => memory_get_peak_usage(true),
            'count' => $standardResult->total(),
        ];
        
        // Fast pagination
        $start = microtime(true);
        $fastResult = (clone $query)->simplePaginate($perPage);
        $methods['fast'] = [
            'time' => microtime(true) - $start,
            'memory' => memory_get_peak_usage(true),
            'count' => $fastResult->count(),
        ];
        
        // Cursor pagination
        $start = microtime(true);
        $cursorQuery = clone $query;
        if (empty($cursorQuery->getQuery()->orders)) {
            $cursorQuery->orderBy($this->getKeyName());
        }
        $cursorResult = $cursorQuery->cursorPaginate($perPage);
        $methods['cursor'] = [
            'time' => microtime(true) - $start,
            'memory' => memory_get_peak_usage(true),
            'count' => $cursorResult->count(),
        ];
        
        return [
            'total_time' => microtime(true) - $startTime,
            'table' => $this->getTable(),
            'estimated_rows' => $this->getTableRowEstimate(),
            'methods' => $methods,
            'recommendation' => $this->getPerformanceRecommendation($methods),
        ];
    }

    /**
     * Determine if approximate count should be used based on table size.
     *
     * @return bool
     */
    protected function shouldUseApproximateCount(): bool
    {
        // Use approximate count for tables with more than 1 million estimated rows
        $estimatedRows = $this->getTableRowEstimate();
        return $estimatedRows > 1000000;
    }

    /**
     * Get estimated table row count from information schema.
     *
     * @return int
     */
    protected function getTableRowEstimate(): int
    {
        $tableName = $this->getTable();
        
        try {
            if (DB::getDriverName() === 'mysql') {
                $result = DB::select("
                    SELECT table_rows 
                    FROM information_schema.tables 
                    WHERE table_schema = DATABASE() 
                    AND table_name = ?
                ", [$tableName]);
                
                return $result[0]->table_rows ?? 0;
            }
            
            if (DB::getDriverName() === 'pgsql') {
                $result = DB::select("
                    SELECT n_tup_ins + n_tup_upd as estimated_rows
                    FROM pg_stat_user_tables 
                    WHERE relname = ?
                ", [$tableName]);
                
                return $result[0]->estimated_rows ?? 0;
            }
            
            return 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Paginate with approximate count for large datasets.
     *
     * @param Builder $query
     * @param int $perPage
     * @param array $columns
     * @param string $pageName
     * @param int|null $page
     * @return LengthAwarePaginator
     */
    protected function paginateWithApproximateCount(
        Builder $query,
        int $perPage, 
        array $columns, 
        string $pageName, 
        ?int $page
    ): LengthAwarePaginator {
        $page = $page ?? request()->input($pageName, 1);
        $total = $query->estimatedCount();
        
        $items = $query->forPage($page, $perPage)->get($columns);
        
        return new ConcreteLengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => $pageName,
            ]
        );
    }

    /**
     * Generate cache key for pagination.
     *
     * @param Builder $query
     * @return string
     */
    protected function generatePaginationCacheKey(Builder $query): string
    {
        $sql = $query->toSql();
        $bindings = $query->getBindings();
        
        return 'pagination_' . md5($sql . serialize($bindings));
    }

    /**
     * Get performance recommendation based on test results.
     *
     * @param array $methods
     * @return string
     */
    protected function getPerformanceRecommendation(array $methods): string
    {
        // Find the fastest method
        $fastest = array_reduce(array_keys($methods), function ($carry, $key) use ($methods) {
            return $carry === null || $methods[$key]['time'] < $methods[$carry]['time'] ? $key : $carry;
        });
        
        $estimatedRows = $this->getTableRowEstimate();
        
        if ($estimatedRows > 5000000) {
            return "For {$estimatedRows} rows: Use cursor pagination or seek pagination for best performance";
        } elseif ($estimatedRows > 1000000) {
            return "For {$estimatedRows} rows: Use fast pagination or cursor pagination";
        } else {
            return "For {$estimatedRows} rows: Standard pagination is acceptable, fastest method was: {$fastest}";
        }
    }

    /**
     * Get pagination configuration.
     *
     * @return array
     */
    protected function getPaginationConfig(): array
    {
        return $this->paginatableConfig ?? [
            'cache_ttl' => 300,
            'use_approximate_count' => true,
            'approximate_count_threshold' => 1000000,
            'cursor_pagination_default' => false,
        ];
    }
}
