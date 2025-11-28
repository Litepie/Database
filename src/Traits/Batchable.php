<?php

namespace Litepie\Database\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;

/**
 * Trait Batchable
 *
 * Provides efficient bulk and batch operation capabilities for Eloquent models.
 * This trait enables high-performance data operations including bulk inserts,
 * updates, deletes, and memory-efficient batch processing.
 *
 * @package Litepie\Database\Traits
 */
trait Batchable
{
    /**
     * Bulk insert multiple records efficiently.
     *
     * Automatically adds timestamps and processes in chunks to prevent memory issues.
     *
     * Example:
     * ```php
     * Product::bulkInsert([
     *     ['name' => 'Product 1', 'price' => 10.00],
     *     ['name' => 'Product 2', 'price' => 20.00],
     *     // ... thousands of records
     * ], 1000);
     * ```
     *
     * @param array $data Array of records to insert
     * @param int $chunkSize Number of records per chunk
     * @return int Number of records inserted
     */
    public static function bulkInsert(array $data, int $chunkSize = 1000): int
    {
        if (empty($data)) {
            return 0;
        }

        $inserted = 0;

        try {
            DB::transaction(function () use ($data, $chunkSize, &$inserted) {
                $chunks = array_chunk($data, $chunkSize);

                foreach ($chunks as $chunk) {
                    // Add timestamps if not present
                    $chunk = array_map(function ($item) {
                        if (!isset($item['created_at'])) {
                            $item['created_at'] = now();
                        }
                        if (!isset($item['updated_at'])) {
                            $item['updated_at'] = now();
                        }
                        return $item;
                    }, $chunk);

                    static::insert($chunk);
                    $inserted += count($chunk);
                }
            });

            return $inserted;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Bulk update multiple records by key.
     *
     * Example:
     * ```php
     * Product::bulkUpdate([
     *     ['id' => 1, 'price' => 15.00, 'status' => 'active'],
     *     ['id' => 2, 'price' => 25.00, 'status' => 'active'],
     *     ['id' => 3, 'price' => 35.00, 'status' => 'active'],
     * ], 'id', 500);
     * ```
     *
     * @param array $data Array of records to update
     * @param string $key Key field to match records
     * @param int $chunkSize Number of records per chunk
     * @return int Number of records updated
     */
    public static function bulkUpdate(array $data, string $key = 'id', int $chunkSize = 1000): int
    {
        if (empty($data)) {
            return 0;
        }

        $updated = 0;

        try {
            DB::transaction(function () use ($data, $key, $chunkSize, &$updated) {
                $chunks = array_chunk($data, $chunkSize);

                foreach ($chunks as $chunk) {
                    foreach ($chunk as $item) {
                        if (!isset($item[$key])) {
                            continue;
                        }

                        $keyValue = $item[$key];
                        unset($item[$key]);

                        // Add updated_at timestamp
                        $item['updated_at'] = now();

                        $affected = static::where($key, $keyValue)->update($item);
                        $updated += $affected;
                    }
                }
            });

            return $updated;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Bulk delete records by IDs or custom key.
     *
     * Example:
     * ```php
     * Product::bulkDelete([1, 2, 3, 4, 5]);
     * Product::bulkDelete(['SKU001', 'SKU002', 'SKU003'], 'sku');
     * ```
     *
     * @param array $ids Array of IDs to delete
     * @param string $key Key field to match records
     * @param int $chunkSize Number of records per chunk
     * @return int Number of records deleted
     */
    public static function bulkDelete(array $ids, string $key = 'id', int $chunkSize = 1000): int
    {
        if (empty($ids)) {
            return 0;
        }

        $deleted = 0;

        try {
            DB::transaction(function () use ($ids, $key, $chunkSize, &$deleted) {
                $chunks = array_chunk($ids, $chunkSize);

                foreach ($chunks as $chunk) {
                    $affected = static::whereIn($key, $chunk)->delete();
                    $deleted += $affected;
                }
            });

            return $deleted;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Bulk upsert (insert or update) records.
     *
     * Uses Laravel's native upsert method for optimal performance.
     *
     * Example:
     * ```php
     * Product::bulkUpsert([
     *     ['sku' => 'ABC123', 'name' => 'Product 1', 'price' => 10.00],
     *     ['sku' => 'ABC124', 'name' => 'Product 2', 'price' => 20.00],
     * ], ['sku'], ['name', 'price']);
     * ```
     *
     * @param array $data Array of records
     * @param array $uniqueBy Columns to determine uniqueness
     * @param array|null $update Columns to update (null = all except uniqueBy)
     * @param int $chunkSize Number of records per chunk
     * @return int Number of records affected
     */
    public static function bulkUpsert(array $data, array $uniqueBy, array $update = null, int $chunkSize = 1000): int
    {
        if (empty($data)) {
            return 0;
        }

        $affected = 0;

        try {
            DB::transaction(function () use ($data, $uniqueBy, $update, $chunkSize, &$affected) {
                $chunks = array_chunk($data, $chunkSize);

                foreach ($chunks as $chunk) {
                    // Add timestamps if not present
                    $chunk = array_map(function ($item) {
                        if (!isset($item['created_at'])) {
                            $item['created_at'] = now();
                        }
                        if (!isset($item['updated_at'])) {
                            $item['updated_at'] = now();
                        }
                        return $item;
                    }, $chunk);

                    // Determine update fields
                    $updateFields = $update ?? array_keys($chunk[0] ?? []);
                    $updateFields = array_filter($updateFields, function ($field) use ($uniqueBy) {
                        return !in_array($field, $uniqueBy) && $field !== 'created_at';
                    });

                    $affected += static::upsert($chunk, $uniqueBy, $updateFields);
                }
            });

            return $affected;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Process records in batches with a callback.
     *
     * Memory-efficient processing of large datasets.
     *
     * Example:
     * ```php
     * Product::batchProcess(function ($products) {
     *     foreach ($products as $product) {
     *         // Process each product
     *         $product->calculateMetrics();
     *     }
     * }, 1000);
     * ```
     *
     * @param callable $callback Function to process each batch
     * @param int $chunkSize Number of records per batch
     * @param array $columns Columns to retrieve
     * @return int Total records processed
     */
    public static function batchProcess(callable $callback, int $chunkSize = 1000, array $columns = ['*']): int
    {
        $processed = 0;

        static::query()->chunk($chunkSize, function (Collection $records) use ($callback, &$processed) {
            $callback($records);
            $processed += $records->count();
        });

        return $processed;
    }

    /**
     * Batch update records with a callback.
     *
     * Only saves records that were actually modified.
     *
     * Example:
     * ```php
     * Product::batchUpdate(function ($product) {
     *     $product->price = $product->price * 1.1; // 10% increase
     * }, 500);
     * ```
     *
     * @param callable $callback Function to modify each record
     * @param int $chunkSize Number of records per batch
     * @param array $columns Columns to retrieve
     * @return int Number of records updated
     */
    public static function batchUpdate(callable $callback, int $chunkSize = 1000, array $columns = ['*']): int
    {
        $updated = 0;

        static::batchProcess(function (Collection $records) use ($callback, &$updated) {
            foreach ($records as $record) {
                $callback($record);

                if ($record->isDirty()) {
                    $record->save();
                    $updated++;
                }
            }
        }, $chunkSize, $columns);

        return $updated;
    }

    /**
     * Get optimal chunk size based on available memory.
     *
     * Example:
     * ```php
     * $chunkSize = Product::getOptimalChunkSize();
     * Product::bulkInsert($data, $chunkSize);
     * ```
     *
     * @return int Recommended chunk size
     */
    public static function getOptimalChunkSize(): int
    {
        $availableMemory = static::getAvailableMemory();
        $estimatedRowSize = 1024; // 1KB per row estimate

        return min(
            max(100, intval($availableMemory * 0.1 / $estimatedRowSize)),
            10000
        );
    }

    /**
     * Get batch operation statistics and recommendations.
     *
     * Example:
     * ```php
     * $stats = Product::getBatchStats();
     * // Returns: ['optimal_chunk_size' => 1000, 'table_size' => 50000, ...]
     * ```
     *
     * @return array Statistics and recommendations
     */
    public static function getBatchStats(): array
    {
        $tableSize = static::count();

        return [
            'table_size' => $tableSize,
            'optimal_chunk_size' => static::getOptimalChunkSize(),
            'recommended_batch_size' => static::getRecommendedBatchSize($tableSize),
            'estimated_memory_per_1000' => static::estimateMemoryUsage(1000),
            'available_memory' => static::formatBytes(static::getAvailableMemory()),
        ];
    }

    /**
     * Estimate memory usage for a given number of records.
     *
     * @param int $records Number of records
     * @return string Formatted memory size
     */
    protected static function estimateMemoryUsage(int $records = 1000): string
    {
        $estimatedRowSize = 1024; // 1KB per row
        $totalSize = $records * $estimatedRowSize;

        return static::formatBytes($totalSize);
    }

    /**
     * Get recommended batch size based on table size.
     *
     * @param int $tableSize Number of records in table
     * @return int Recommended batch size
     */
    protected static function getRecommendedBatchSize(int $tableSize = null): int
    {
        $tableSize = $tableSize ?? static::count();

        if ($tableSize < 10000) {
            return 500;
        } elseif ($tableSize < 100000) {
            return 1000;
        } elseif ($tableSize < 1000000) {
            return 2000;
        } else {
            return 5000;
        }
    }

    /**
     * Get available memory in bytes.
     *
     * @return int Available memory in bytes
     */
    protected static function getAvailableMemory(): int
    {
        $memoryLimit = ini_get('memory_limit');

        if ($memoryLimit === '-1') {
            return 1024 * 1024 * 1024; // 1GB default
        }

        return static::parseMemoryLimit($memoryLimit);
    }

    /**
     * Parse memory limit string to bytes.
     *
     * @param string $limit Memory limit string
     * @return int Memory in bytes
     */
    protected static function parseMemoryLimit(string $limit): int
    {
        $unit = strtolower(substr($limit, -1));
        $value = (int) substr($limit, 0, -1);

        switch ($unit) {
            case 'g':
                return $value * 1024 * 1024 * 1024;
            case 'm':
                return $value * 1024 * 1024;
            case 'k':
                return $value * 1024;
            default:
                return (int) $limit;
        }
    }

    /**
     * Format bytes to human-readable string.
     *
     * @param int $bytes Bytes to format
     * @return string Formatted string
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
     * Truncate table (delete all records).
     *
     * WARNING: This will delete all data and reset auto-increment.
     *
     * Example:
     * ```php
     * Product::truncate();
     * ```
     *
     * @return void
     */
    public static function truncateTable(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        static::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Batch insert with duplicate detection.
     *
     * Example:
     * ```php
     * $result = Product::bulkInsertIgnoreDuplicates([
     *     ['sku' => 'ABC123', 'name' => 'Product 1'],
     *     ['sku' => 'ABC123', 'name' => 'Product 1'], // Duplicate, will be ignored
     * ], ['sku']);
     * ```
     *
     * @param array $data Records to insert
     * @param array $uniqueFields Fields to check for duplicates
     * @param int $chunkSize Chunk size
     * @return array Results with counts
     */
    public static function bulkInsertIgnoreDuplicates(array $data, array $uniqueFields = ['id'], int $chunkSize = 1000): array
    {
        $results = [
            'inserted' => 0,
            'duplicates' => 0,
        ];

        if (empty($data)) {
            return $results;
        }

        try {
            DB::transaction(function () use ($data, $uniqueFields, $chunkSize, &$results) {
                $chunks = array_chunk($data, $chunkSize);

                foreach ($chunks as $chunk) {
                    foreach ($chunk as $item) {
                        // Build where clause for unique fields
                        $whereClause = [];
                        foreach ($uniqueFields as $field) {
                            if (isset($item[$field])) {
                                $whereClause[$field] = $item[$field];
                            }
                        }

                        // Check if record exists
                        $exists = static::where($whereClause)->exists();

                        if (!$exists) {
                            // Add timestamps
                            if (!isset($item['created_at'])) {
                                $item['created_at'] = now();
                            }
                            if (!isset($item['updated_at'])) {
                                $item['updated_at'] = now();
                            }

                            static::create($item);
                            $results['inserted']++;
                        } else {
                            $results['duplicates']++;
                        }
                    }
                }
            });

            return $results;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
