<?php

namespace Litepie\Database\Traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * Exportable Trait
 * 
 * Provides data import/export capabilities for Eloquent models.
 * Supports CSV, JSON, and Excel formats with chunked processing for large datasets.
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
 * // Import from CSV
 * $imported = Product::importFromCsv('products.csv');
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
        'disk' => 'local',
        'path' => 'exports',
    ];

    /**
     * Exportable columns (whitelist).
     */
    protected array $exportable = [];

    /**
     * Export data to CSV format.
     *
     * @param array $columns Columns to export (empty = all exportable)
     * @param string|null $filename Custom filename
     * @return string File path
     */
    public function exportToCsv(array $columns = [], ?string $filename = null): string
    {
        $filename = $filename ?: $this->generateExportFilename('csv');
        $headers = $this->getExportHeaders($columns);
        
        $csv = $this->createCsvWriter();
        $csv[] = $headers; // Add header row
        
        $exported = 0;
        $chunkSize = $this->exportConfig['chunk_size'];
        
        $this->chunk($chunkSize, function ($records) use (&$csv, $headers, &$exported) {
            foreach ($records as $record) {
                $row = [];
                foreach ($headers as $column) {
                    $row[] = $this->getColumnValue($record, $column);
                }
                $csv[] = $row;
                $exported++;
            }
        });
        
        $content = $this->arrayToCsv($csv);
        $path = $this->saveExportFile($filename, $content);
        
        return $path;
    }

    /**
     * Export data to JSON format.
     *
     * @param array $columns Columns to export
     * @param string|null $filename Custom filename
     * @return string File path
     */
    public function exportToJson(array $columns = [], ?string $filename = null): string
    {
        $filename = $filename ?: $this->generateExportFilename('json');
        $columns = $this->getExportHeaders($columns);
        
        $data = [];
        $chunkSize = $this->exportConfig['chunk_size'];
        
        $this->chunk($chunkSize, function ($records) use (&$data, $columns) {
            foreach ($records as $record) {
                $item = [];
                foreach ($columns as $column) {
                    $item[$column] = $this->getColumnValue($record, $column);
                }
                $data[] = $item;
            }
        });
        
        $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $path = $this->saveExportFile($filename, $content);
        
        return $path;
    }

    /**
     * Export data to Excel format (CSV compatible).
     *
     * @param array $columns Columns to export
     * @param string|null $filename Custom filename
     * @return string File path
     */
    public function exportToExcel(array $columns = [], ?string $filename = null): string
    {
        $filename = $filename ?: $this->generateExportFilename('xlsx');
        // Use CSV format for Excel compatibility
        return $this->exportToCsv($columns, str_replace('.xlsx', '.csv', $filename));
    }

    /**
     * Stream export for large datasets (downloads directly to browser).
     *
     * @param string $format Export format (csv, json)
     * @param callable|null $callback Transform callback for each record
     * @return Response
     */
    public function streamExport(string $format = 'csv', ?callable $callback = null): Response
    {
        $filename = $this->generateExportFilename($format);
        
        $headers = [
            'Content-Type' => $this->getContentType($format),
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ];
        
        return response()->stream(function () use ($format, $callback) {
            $output = fopen('php://output', 'w');
            
            if ($format === 'csv') {
                $headers = $this->getExportHeaders();
                fputcsv($output, $headers);
            } elseif ($format === 'json') {
                fwrite($output, "[\n");
            }
            
            $first = true;
            $chunkSize = $this->exportConfig['chunk_size'];
            
            $this->chunk($chunkSize, function ($records) use ($output, $format, $callback, &$first) {
                foreach ($records as $record) {
                    if ($callback) {
                        $record = $callback($record);
                    }
                    
                    switch ($format) {
                        case 'csv':
                            $row = [];
                            foreach ($this->getExportHeaders() as $column) {
                                $row[] = $this->getColumnValue($record, $column);
                            }
                            fputcsv($output, $row);
                            break;
                            
                        case 'json':
                            if (!$first) {
                                fwrite($output, ",\n");
                            }
                            $first = false;
                            $data = [];
                            foreach ($this->getExportHeaders() as $column) {
                                $data[$column] = $this->getColumnValue($record, $column);
                            }
                            fwrite($output, json_encode($data, JSON_UNESCAPED_UNICODE));
                            break;
                    }
                }
            });
            
            if ($format === 'json') {
                fwrite($output, "\n]");
            }
            
            fclose($output);
        }, 200, $headers);
    }

    /**
     * Import data from CSV file.
     *
     * @param string $filePath Path to CSV file
     * @param array $mapping Column mapping ['csv_column' => 'model_attribute']
     * @param array $options Import options
     * @return int Number of imported records
     */
    public static function importFromCsv(string $filePath, array $mapping = [], array $options = []): int
    {
        $options = array_merge([
            'has_header' => true,
            'chunk_size' => 1000,
            'skip_errors' => false,
            'update_existing' => false,
            'unique_field' => 'id',
        ], $options);
        
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }
        
        $handle = fopen($filePath, 'r');
        $headers = null;
        $imported = 0;
        $errors = [];
        $chunk = [];
        
        if ($options['has_header']) {
            $headers = fgetcsv($handle);
        }
        
        while (($row = fgetcsv($handle)) !== false) {
            try {
                $data = $headers 
                    ? array_combine($headers, $row) 
                    : $row;
                
                $mappedData = static::mapImportData($data, $mapping);
                $chunk[] = $mappedData;
                
                if (count($chunk) >= $options['chunk_size']) {
                    $imported += static::processImportChunk($chunk, $options);
                    $chunk = [];
                }
            } catch (\Exception $e) {
                if (!$options['skip_errors']) {
                    fclose($handle);
                    throw $e;
                }
                
                $errors[] = [
                    'row' => $row,
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        // Process remaining chunk
        if (!empty($chunk)) {
            $imported += static::processImportChunk($chunk, $options);
        }
        
        fclose($handle);
        
        return $imported;
    }

    /**
     * Import data from JSON file.
     *
     * @param string $filePath Path to JSON file
     * @param array $mapping Column mapping
     * @param array $options Import options
     * @return int Number of imported records
     */
    public static function importFromJson(string $filePath, array $mapping = [], array $options = []): int
    {
        $options = array_merge([
            'chunk_size' => 1000,
            'skip_errors' => false,
            'update_existing' => false,
            'unique_field' => 'id',
        ], $options);
        
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File not found: {$filePath}");
        }
        
        $content = file_get_contents($filePath);
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON file: ' . json_last_error_msg());
        }
        
        $imported = 0;
        $errors = [];
        $chunks = array_chunk($data, $options['chunk_size']);
        
        foreach ($chunks as $chunk) {
            try {
                $mappedChunk = [];
                foreach ($chunk as $item) {
                    $mappedChunk[] = static::mapImportData($item, $mapping);
                }
                
                $imported += static::processImportChunk($mappedChunk, $options);
            } catch (\Exception $e) {
                if (!$options['skip_errors']) {
                    throw $e;
                }
                
                $errors[] = [
                    'chunk' => count($errors) + 1,
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return $imported;
    }

    /**
     * Import data from Excel file (CSV format).
     *
     * @param string $filePath Path to Excel file
     * @param array $mapping Column mapping
     * @param array $options Import options
     * @return int Number of imported records
     */
    public static function importFromExcel(string $filePath, array $mapping = [], array $options = []): int
    {
        // For Excel import, use CSV reader
        return static::importFromCsv($filePath, $mapping, $options);
    }

    /**
     * Get export headers (columns).
     *
     * @param array $columns Requested columns
     * @return array
     */
    protected function getExportHeaders(array $columns = []): array
    {
        if (!empty($columns)) {
            return $columns;
        }
        
        // Use $exportable property if defined
        if (!empty($this->exportable)) {
            return $this->exportable;
        }
        
        // Get model instance
        $model = $this->getModel();
        
        // Use fillable attributes
        if (method_exists($model, 'getFillable') && !empty($model->getFillable())) {
            return $model->getFillable();
        }
        
        // Fallback to common columns
        return ['id', 'created_at', 'updated_at'];
    }

    /**
     * Get column value from model/record.
     *
     * @param mixed $record Model instance or array
     * @param string $column Column name (supports dot notation for relations)
     * @return mixed
     */
    protected function getColumnValue($record, string $column)
    {
        // Handle nested relationships (e.g., 'category.name')
        if (strpos($column, '.') !== false) {
            $parts = explode('.', $column);
            $value = $record;
            
            foreach ($parts as $part) {
                if (is_array($value)) {
                    $value = $value[$part] ?? null;
                } elseif (is_object($value)) {
                    $value = $value->$part ?? null;
                } else {
                    $value = null;
                }
                
                if ($value === null) {
                    break;
                }
            }
            
            return $value;
        }
        
        // Direct column access
        if (is_array($record)) {
            return $record[$column] ?? null;
        }
        
        return $record->$column ?? null;
    }

    /**
     * Generate export filename.
     *
     * @param string $extension File extension
     * @return string
     */
    protected function generateExportFilename(string $extension): string
    {
        $model = $this->getModel();
        $modelName = class_basename($model);
        $timestamp = now()->format('Y-m-d_H-i-s');
        
        return Str::snake($modelName) . "_export_{$timestamp}.{$extension}";
    }

    /**
     * Save export file to storage.
     *
     * @param string $filename Filename
     * @param string $content File content
     * @return string File path
     */
    protected function saveExportFile(string $filename, string $content): string
    {
        $path = $this->exportConfig['path'] . '/' . $filename;
        
        Storage::disk($this->exportConfig['disk'])->put($path, $content);
        
        return $path;
    }

    /**
     * Get content type for format.
     *
     * @param string $format File format
     * @return string
     */
    protected function getContentType(string $format): string
    {
        $types = [
            'csv' => 'text/csv',
            'json' => 'application/json',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xls' => 'application/vnd.ms-excel',
        ];
        
        return $types[$format] ?? 'application/octet-stream';
    }

    /**
     * Map import data using mapping configuration.
     *
     * @param array $data Input data
     * @param array $mapping Column mapping
     * @return array
     */
    protected static function mapImportData(array $data, array $mapping): array
    {
        if (empty($mapping)) {
            return $data;
        }
        
        $mapped = [];
        
        foreach ($mapping as $importField => $modelField) {
            if (isset($data[$importField])) {
                $mapped[$modelField] = $data[$importField];
            }
        }
        
        return $mapped;
    }

    /**
     * Process import chunk.
     *
     * @param array $chunk Data chunk
     * @param array $options Import options
     * @return int Number of imported records
     */
    protected static function processImportChunk(array $chunk, array $options): int
    {
        if ($options['update_existing']) {
            $imported = 0;
            foreach ($chunk as $data) {
                $uniqueField = $options['unique_field'];
                $uniqueValue = $data[$uniqueField] ?? null;
                
                if ($uniqueValue) {
                    static::updateOrCreate(
                        [$uniqueField => $uniqueValue],
                        $data
                    );
                } else {
                    static::create($data);
                }
                $imported++;
            }
            return $imported;
        } else {
            static::insert($chunk);
            return count($chunk);
        }
    }

    /**
     * Create CSV writer array.
     *
     * @return array
     */
    protected function createCsvWriter(): array
    {
        return [];
    }

    /**
     * Convert array to CSV string.
     *
     * @param array $data CSV data
     * @return string
     */
    protected function arrayToCsv(array $data): string
    {
        $output = fopen('php://temp', 'r+');
        
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }

    /**
     * Configure export settings.
     *
     * @param array $config Configuration array
     * @return $this
     */
    public function configureExport(array $config): self
    {
        $this->exportConfig = array_merge($this->exportConfig, $config);
        return $this;
    }

    /**
     * Get export statistics.
     *
     * @return array
     */
    public function getExportStats(): array
    {
        $model = $this->getModel();
        $totalRecords = $this->count();
        
        return [
            'model' => class_basename($model),
            'total_records' => $totalRecords,
            'estimated_csv_size' => $this->estimateExportSize('csv', $totalRecords),
            'estimated_json_size' => $this->estimateExportSize('json', $totalRecords),
            'recommended_chunk_size' => $this->getRecommendedChunkSize($totalRecords),
        ];
    }

    /**
     * Estimate export file size.
     *
     * @param string $format File format
     * @param int $recordCount Number of records
     * @return string
     */
    protected function estimateExportSize(string $format, int $recordCount): string
    {
        $avgRowSize = $format === 'csv' ? 100 : 200; // bytes per record estimate
        $totalSize = $recordCount * $avgRowSize;
        
        return $this->formatBytes($totalSize);
    }

    /**
     * Get recommended chunk size for export.
     *
     * @param int $totalRecords Total number of records
     * @return int
     */
    protected function getRecommendedChunkSize(int $totalRecords): int
    {
        if ($totalRecords < 1000) {
            return 100;
        } elseif ($totalRecords < 10000) {
            return 500;
        } elseif ($totalRecords < 100000) {
            return 1000;
        } else {
            return 2000;
        }
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
