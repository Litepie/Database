<?php

namespace Litepie\Database\Traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Trait Exportable
 *
 * Provides data export capabilities for Eloquent models.
 * Supports multiple formats including CSV, JSON, and Excel with streaming for large datasets.
 *
 * Usage:
 * ```php
 * class Product extends Model
 * {
 *     use Exportable;
 *
 *     protected $exportable = ['id', 'name', 'price', 'category'];
 * }
 *
 * // Export to CSV
 * $path = Product::query()->where('status', 'active')->exportToCsv();
 *
 * // Stream large export
 * return Product::query()->streamExport('csv');
 *
 * // Configure export options
 * Product::configureExport([
 *     'chunk_size' => 2000,
 *     'include_headers' => true,
 * ]);
 * ```
 */
trait Exportable
{
    /**
     * Export configuration.
     */
    protected array $exportConfig = [
        'chunk_size' => 1000,
        'memory_limit' => '512M',
        'include_headers' => true,
        'date_format' => 'Y-m-d H:i:s',
        'disk' => 'local',
        'directory' => 'exports',
    ];

    /**
     * Export query results to CSV file.
     *
     * @param string|null $filename Output filename
     * @param array $columns Columns to export (empty = all)
     * @param array $headers Custom column headers
     * @return string Path to exported file
     */
    public function exportToCsv(?string $filename = null, array $columns = [], array $headers = []): string
    {
        $filename = $filename ?? $this->generateExportFilename('csv');
        $query = $this instanceof \Illuminate\Database\Eloquent\Builder ? $this : static::query();
        
        $handle = fopen('php://temp', 'w+');
        $firstRow = true;
        
        $query->chunk($this->exportConfig['chunk_size'], function ($records) use ($handle, $columns, $headers, &$firstRow) {
            foreach ($records as $record) {
                if ($firstRow && $this->exportConfig['include_headers']) {
                    $headerRow = !empty($headers) ? $headers : $this->getExportHeaders($record, $columns);
                    fputcsv($handle, $headerRow);
                    $firstRow = false;
                }
                
                $row = [];
                $exportColumns = !empty($columns) ? $columns : ($record->exportable ?? array_keys($record->toArray()));
                
                foreach ($exportColumns as $column) {
                    $row[] = $this->getColumnValue($record, $column);
                }
                
                fputcsv($handle, $row);
            }
        });
        
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);
        
        return $this->saveExportFile($filename, $content);
    }

    /**
     * Export query results to JSON file.
     *
     * @param string|null $filename Output filename
     * @param array $columns Columns to export (empty = all)
     * @param bool $pretty Pretty print JSON
     * @return string Path to exported file
     */
    public function exportToJson(?string $filename = null, array $columns = [], bool $pretty = true): string
    {
        $filename = $filename ?? $this->generateExportFilename('json');
        $query = $this instanceof \Illuminate\Database\Eloquent\Builder ? $this : static::query();
        
        $data = [];
        
        $query->chunk($this->exportConfig['chunk_size'], function ($records) use (&$data, $columns) {
            foreach ($records as $record) {
                $exportColumns = !empty($columns) ? $columns : ($record->exportable ?? array_keys($record->toArray()));
                $row = [];
                
                foreach ($exportColumns as $column) {
                    $row[$column] = $this->getColumnValue($record, $column);
                }
                
                $data[] = $row;
            }
        });
        
        $options = $pretty ? JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE : JSON_UNESCAPED_UNICODE;
        $content = json_encode($data, $options);
        
        return $this->saveExportFile($filename, $content);
    }

    /**
     * Export query results to Excel-compatible CSV file.
     *
     * @param string|null $filename Output filename
     * @param array $columns Columns to export (empty = all)
     * @param array $headers Custom column headers
     * @return string Path to exported file
     */
    public function exportToExcel(?string $filename = null, array $columns = [], array $headers = []): string
    {
        $filename = $filename ?? $this->generateExportFilename('xlsx');
        $query = $this instanceof \Illuminate\Database\Eloquent\Builder ? $this : static::query();
        
        $handle = fopen('php://temp', 'w+');
        
        // Add BOM for Excel UTF-8 recognition
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
        
        $firstRow = true;
        
        $query->chunk($this->exportConfig['chunk_size'], function ($records) use ($handle, $columns, $headers, &$firstRow) {
            foreach ($records as $record) {
                if ($firstRow && $this->exportConfig['include_headers']) {
                    $headerRow = !empty($headers) ? $headers : $this->getExportHeaders($record, $columns);
                    $this->arrayToCsv($handle, $headerRow);
                    $firstRow = false;
                }
                
                $row = [];
                $exportColumns = !empty($columns) ? $columns : ($record->exportable ?? array_keys($record->toArray()));
                
                foreach ($exportColumns as $column) {
                    $row[] = $this->getColumnValue($record, $column);
                }
                
                $this->arrayToCsv($handle, $row);
            }
        });
        
        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);
        
        return $this->saveExportFile($filename, $content);
    }

    /**
     * Stream export directly to browser for large datasets.
     *
     * @param string $format Export format (csv, json, excel)
     * @param string|null $filename Download filename
     * @param array $columns Columns to export
     * @return Response
     */
    public function streamExport(string $format = 'csv', ?string $filename = null, array $columns = []): Response
    {
        $filename = $filename ?? $this->generateExportFilename($format);
        $query = $this instanceof \Illuminate\Database\Eloquent\Builder ? $this : static::query();
        
        $headers = [
            'Content-Type' => $this->getContentType($format),
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];
        
        return response()->stream(function () use ($query, $format, $columns) {
            $handle = fopen('php://output', 'w');
            
            if ($format === 'excel') {
                fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel
            }
            
            $firstRow = true;
            
            if ($format === 'json') {
                echo '[';
            }
            
            $query->chunk($this->exportConfig['chunk_size'], function ($records) use ($handle, $format, $columns, &$firstRow) {
                static $recordCount = 0;
                
                foreach ($records as $record) {
                    if ($format === 'csv' || $format === 'excel') {
                        if ($firstRow && $this->exportConfig['include_headers']) {
                            $headerRow = $this->getExportHeaders($record, $columns);
                            fputcsv($handle, $headerRow);
                            $firstRow = false;
                        }
                        
                        $row = [];
                        $exportColumns = !empty($columns) ? $columns : ($record->exportable ?? array_keys($record->toArray()));
                        
                        foreach ($exportColumns as $column) {
                            $row[] = $this->getColumnValue($record, $column);
                        }
                        
                        fputcsv($handle, $row);
                    } elseif ($format === 'json') {
                        if ($recordCount > 0) {
                            echo ',';
                        }
                        
                        $exportColumns = !empty($columns) ? $columns : ($record->exportable ?? array_keys($record->toArray()));
                        $row = [];
                        
                        foreach ($exportColumns as $column) {
                            $row[$column] = $this->getColumnValue($record, $column);
                        }
                        
                        echo json_encode($row, JSON_UNESCAPED_UNICODE);
                        $recordCount++;
                    }
                }
            });
            
            if ($format === 'json') {
                echo ']';
            }
            
            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Configure export settings at runtime.
     *
     * @param array $config Configuration options
     * @return void
     */
    public function configureExport(array $config): void
    {
        $this->exportConfig = array_merge($this->exportConfig, $config);
    }

    /**
     * Get export statistics and recommendations.
     *
     * @return array
     */
    public function getExportStats(): array
    {
        $query = $this instanceof \Illuminate\Database\Eloquent\Builder ? $this : static::query();
        $count = $query->count();
        
        $estimatedSize = $this->estimateExportSize($count);
        $recommendedChunkSize = $this->getRecommendedChunkSize($count);
        
        return [
            'total_records' => $count,
            'estimated_size' => $this->formatBytes($estimatedSize),
            'estimated_size_bytes' => $estimatedSize,
            'recommended_chunk_size' => $recommendedChunkSize,
            'current_chunk_size' => $this->exportConfig['chunk_size'],
            'estimated_memory' => $this->formatBytes($estimatedSize * 1.5),
        ];
    }

    /**
     * Get headers for export based on model.
     *
     * @param mixed $model Model instance
     * @param array $columns Selected columns
     * @return array
     */
    protected function getExportHeaders($model, array $columns = []): array
    {
        $exportColumns = !empty($columns) ? $columns : ($model->exportable ?? array_keys($model->toArray()));
        
        return array_map(function ($column) {
            return ucwords(str_replace('_', ' ', $column));
        }, $exportColumns);
    }

    /**
     * Get column value with proper formatting.
     *
     * @param mixed $record Model instance
     * @param string $column Column name
     * @return mixed
     */
    protected function getColumnValue($record, string $column)
    {
        $value = data_get($record, $column);
        
        if ($value instanceof \DateTimeInterface) {
            return $value->format($this->exportConfig['date_format']);
        }
        
        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }
        
        return $value;
    }

    /**
     * Generate export filename.
     *
     * @param string $extension File extension
     * @return string
     */
    protected function generateExportFilename(string $extension): string
    {
        $model = $this instanceof \Illuminate\Database\Eloquent\Builder ? $this->getModel() : $this;
        $modelName = class_basename($model);
        $timestamp = now()->format('Y-m-d_H-i-s');
        
        return Str::snake($modelName) . "_export_{$timestamp}.{$extension}";
    }

    /**
     * Save export file to storage.
     *
     * @param string $filename Filename
     * @param string $content File content
     * @return string Path to saved file
     */
    protected function saveExportFile(string $filename, string $content): string
    {
        $path = trim($this->exportConfig['directory'], '/') . '/' . $filename;
        
        Storage::disk($this->exportConfig['disk'])->put($path, $content);
        
        return $path;
    }

    /**
     * Get content type for format.
     *
     * @param string $format Export format
     * @return string
     */
    protected function getContentType(string $format): string
    {
        return match ($format) {
            'csv' => 'text/csv',
            'json' => 'application/json',
            'excel' => 'application/vnd.ms-excel',
            default => 'application/octet-stream',
        };
    }

    /**
     * Write array to CSV with custom writer for Excel compatibility.
     *
     * @param resource $handle File handle
     * @param array $fields Fields to write
     * @return void
     */
    protected function createCsvWriter($handle)
    {
        return function (array $fields) use ($handle) {
            fputcsv($handle, $fields, ',', '"', '\\');
        };
    }

    /**
     * Write array to CSV (Excel compatible).
     *
     * @param resource $handle File handle
     * @param array $fields Array fields
     * @return void
     */
    protected function arrayToCsv($handle, array $fields): void
    {
        fputcsv($handle, $fields, ',', '"', '\\');
    }

    /**
     * Estimate export file size.
     *
     * @param int $recordCount Number of records
     * @return int Estimated size in bytes
     */
    protected function estimateExportSize(int $recordCount): int
    {
        // Rough estimation: 200 bytes per record average
        return $recordCount * 200;
    }

    /**
     * Get recommended chunk size based on record count.
     *
     * @param int $recordCount Number of records
     * @return int
     */
    protected function getRecommendedChunkSize(int $recordCount): int
    {
        if ($recordCount > 100000) {
            return 2000;
        } elseif ($recordCount > 10000) {
            return 1000;
        }
        
        return 500;
    }

    /**
     * Format bytes to human-readable format.
     *
     * @param int $bytes Byte count
     * @return string
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unit = 0;
        
        while ($bytes >= 1024 && $unit < count($units) - 1) {
            $bytes /= 1024;
            $unit++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unit];
    }
}
