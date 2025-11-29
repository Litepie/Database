<?php

/**
 * Importable Trait Examples
 * 
 * This file demonstrates how to use the Importable trait
 * for data import operations with preview and validation.
 */

namespace App\Examples;

use App\Models\Product;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

class ImportableExamples
{
    /**
     * Example 1: Basic CSV Import
     * Import data from CSV file.
     */
    public function example1(): void
    {
        // Basic CSV import
        $imported = Product::importFromCsv('products.csv');
        // Returns: 1500 (number of imported records)
        
        // Import with column mapping
        $imported = Product::importFromCsv('products.csv', [
            'Product Name' => 'name',
            'Product Price' => 'price',
            'Category' => 'category_id',
        ]);
        
        // Import with options
        $imported = Product::importFromCsv('products.csv', [], [
            'has_header' => true,
            'chunk_size' => 500,
            'skip_errors' => true,
            'update_existing' => true,
            'unique_field' => 'sku',
        ]);
    }

    /**
     * Example 2: Import from JSON
     * Import data from JSON file.
     */
    public function example2(): void
    {
        // Basic JSON import
        $imported = User::importFromJson('users.json');
        
        // With column mapping
        $imported = Order::importFromJson('orders.json', [
            'order_id' => 'order_number',
            'customer_name' => 'customer',
            'order_total' => 'total',
        ]);
        
        // With options
        $imported = Product::importFromJson('products.json', [], [
            'chunk_size' => 1000,
            'skip_errors' => false,
            'update_existing' => true,
            'unique_field' => 'id',
        ]);
    }

    /**
     * Example 3: Import with Error Handling
     * Handle errors during import.
     */
    public function example3(): void
    {
        try {
            // Skip errors and continue import
            $imported = Product::importFromCsv('products.csv', [], [
                'skip_errors' => true,
            ]);
            
            echo "Successfully imported {$imported} products";
        } catch (\Exception $e) {
            echo "Import failed: " . $e->getMessage();
        }
        
        // Validate before import
        if (!file_exists('products.csv')) {
            throw new \Exception('CSV file not found');
        }
        
        $imported = Product::importFromCsv('products.csv');
    }

    /**
     * Example 4: Update Existing Records on Import
     * Upsert behavior during import.
     */
    public function example4(): void
    {
        // Update existing products based on SKU
        $imported = Product::importFromCsv('products.csv', [], [
            'update_existing' => true,
            'unique_field' => 'sku',
        ]);
        
        // Update users based on email
        $imported = User::importFromJson('users.json', [], [
            'update_existing' => true,
            'unique_field' => 'email',
        ]);
    }

    /**
     * Example 5: Import from Excel
     * Import Excel files.
     */
    public function example5(): void
    {
        // Import from Excel (uses CSV reader)
        $imported = Product::importFromExcel('products.xlsx', [
            'Product Name' => 'name',
            'SKU' => 'sku',
            'Price' => 'price',
        ]);
        
        // With options
        $imported = Product::importFromExcel('inventory.xlsx', [], [
            'has_header' => true,
            'chunk_size' => 500,
            'update_existing' => true,
            'unique_field' => 'sku',
        ]);
    }

    /**
     * Example 6: Preview Import Data
     * Preview import data before actually importing.
     */
    public function example6(): void
    {
        // Preview CSV import
        $preview = Product::previewImport(
            'products.csv',
            'csv',
            [
                'Product Name' => 'name',
                'SKU' => 'sku',
                'Price' => 'price',
            ],
            10 // Preview 10 rows
        );
        
        /*
        Returns:
        [
            'file_info' => [
                'name' => 'products.csv',
                'size' => '2.5 MB',
                'format' => 'csv'
            ],
            'headers' => ['Product Name', 'SKU', 'Price', 'Stock'],
            'sample_data' => [
                ['Product Name' => 'Widget', 'SKU' => 'WID001', 'Price' => '10.00', 'Stock' => '50'],
                ['Product Name' => 'Gadget', 'SKU' => 'GAD001', 'Price' => '20.00', 'Stock' => '30'],
                // ... 8 more rows
            ],
            'mapped_preview' => [
                ['name' => 'Widget', 'sku' => 'WID001', 'price' => '10.00'],
                ['name' => 'Gadget', 'sku' => 'GAD001', 'price' => '20.00'],
                // ... 8 more rows
            ],
            'statistics' => [
                'total_rows' => 1500,
                'preview_rows' => 10,
                'columns_count' => 4
            ],
            'validation' => [
                'errors' => [],
                'warnings' => ['Unmapped columns: Stock'],
                'mapping_status' => 'valid'
            ]
        ]
        */
        
        // Check validation results
        if (!empty($preview['validation']['errors'])) {
            echo "Import cannot proceed. Errors found:\n";
            foreach ($preview['validation']['errors'] as $error) {
                echo "- {$error}\n";
            }
            return;
        }
        
        // Show warnings
        if (!empty($preview['validation']['warnings'])) {
            echo "Warnings:\n";
            foreach ($preview['validation']['warnings'] as $warning) {
                echo "- {$warning}\n";
            }
        }
        
        // Display preview to user
        echo "Preview of import:\n";
        echo "Total rows: {$preview['statistics']['total_rows']}\n";
        echo "File size: {$preview['file_info']['size']}\n";
    }

    /**
     * Example 7: Preview JSON Import
     * Preview JSON file before import.
     */
    public function example7(): void
    {
        $preview = Product::previewImport(
            'products.json',
            'json',
            [
                'product_name' => 'name',
                'product_sku' => 'sku',
                'unit_price' => 'price',
            ],
            5
        );
        
        // Show original vs mapped data
        foreach ($preview['sample_data'] as $index => $original) {
            echo "Original: " . json_encode($original) . "\n";
            echo "Mapped: " . json_encode($preview['mapped_preview'][$index]) . "\n";
            echo "---\n";
        }
    }

    /**
     * Example 8: Validate Import File
     * Validate file before importing.
     */
    public function example8(): void
    {
        $validation = Product::validateImportFile(
            'products.csv',
            'csv',
            [
                'Product Name' => 'name',
                'SKU' => 'sku',
                'Price' => 'price',
            ]
        );
        
        /*
        Returns:
        [
            'is_valid' => true,
            'errors' => [],
            'warnings' => ['Unmapped columns: Description'],
            'total_rows' => 1500,
            'file_size' => '2.5 MB'
        ]
        */
        
        if (!$validation['is_valid']) {
            echo "File is invalid!\n";
            foreach ($validation['errors'] as $error) {
                echo "Error: {$error}\n";
            }
            return;
        }
        
        echo "File is valid. Ready to import {$validation['total_rows']} rows.\n";
    }

    /**
     * Example 9: Get Import Recommendations
     * Get recommendations for import settings.
     */
    public function example9(): void
    {
        $recommendations = Product::getImportRecommendations('large_products.csv', 'csv');
        
        /*
        Returns:
        [
            'chunk_size' => 2000,
            'estimated_time' => '5-10 minutes',
            'memory_usage' => 'High',
            'should_use_queue' => true,
            'tips' => [
                'Consider using queue for background processing',
                'Large dataset detected - processing may take several minutes'
            ]
        ]
        */
        
        echo "Recommended chunk size: {$recommendations['chunk_size']}\n";
        echo "Estimated time: {$recommendations['estimated_time']}\n";
        echo "Memory usage: {$recommendations['memory_usage']}\n";
        
        if ($recommendations['should_use_queue']) {
            echo "⚠️ This import should be queued for background processing\n";
        }
        
        foreach ($recommendations['tips'] as $tip) {
            echo "💡 {$tip}\n";
        }
    }

    /**
     * Example 10: Complete Import Flow with Preview
     * Real-world controller implementation.
     */
    public function example10(): void
    {
        /*
        // In your controller
        
        public function showImportPreview(Request $request)
        {
            $request->validate([
                'file' => 'required|file|mimes:csv,json',
            ]);
            
            $file = $request->file('file');
            $tempPath = $file->storeAs('temp', 'preview_' . time() . '.' . $file->extension());
            $fullPath = storage_path('app/' . $tempPath);
            
            // Detect format
            $format = $file->extension();
            
            // Get mapping from request or use defaults
            $mapping = $request->input('mapping', []);
            
            // Preview import
            $preview = Product::previewImport($fullPath, $format, $mapping, 20);
            
            // Get recommendations
            $recommendations = Product::getImportRecommendations($fullPath, $format);
            
            return view('imports.preview', [
                'preview' => $preview,
                'recommendations' => $recommendations,
                'tempPath' => $tempPath,
            ]);
        }
        
        public function confirmImport(Request $request)
        {
            $tempPath = $request->input('temp_path');
            $fullPath = storage_path('app/' . $tempPath);
            $mapping = $request->input('mapping', []);
            $format = $request->input('format', 'csv');
            
            // Validate first
            $validation = Product::validateImportFile($fullPath, $format, $mapping);
            
            if (!$validation['is_valid']) {
                return back()->withErrors($validation['errors']);
            }
            
            // Get recommendations for settings
            $recommendations = Product::getImportRecommendations($fullPath, $format);
            
            // Import based on format
            if ($format === 'csv') {
                $imported = Product::importFromCsv($fullPath, $mapping, [
                    'chunk_size' => $recommendations['chunk_size'],
                    'skip_errors' => true,
                    'update_existing' => true,
                    'unique_field' => 'sku',
                ]);
            } else {
                $imported = Product::importFromJson($fullPath, $mapping, [
                    'chunk_size' => $recommendations['chunk_size'],
                    'skip_errors' => true,
                ]);
            }
            
            // Clean up temp file
            Storage::delete($tempPath);
            
            return redirect()->route('products.index')
                ->with('success', "Successfully imported {$imported} products");
        }
        */
    }

    /**
     * Example 11: API Endpoint for Import
     * Import data via API.
     */
    public function example11(): void
    {
        /*
        // In your controller
        public function import(Request $request)
        {
            $request->validate([
                'file' => 'required|file|mimes:csv,json',
            ]);
            
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();
            $tempPath = $file->storeAs('temp', 'import.' . $extension);
            $fullPath = storage_path('app/' . $tempPath);
            
            $imported = 0;
            
            if ($extension === 'csv') {
                $imported = Product::importFromCsv($fullPath, [], [
                    'skip_errors' => true,
                    'update_existing' => true,
                    'unique_field' => 'sku',
                ]);
            } elseif ($extension === 'json') {
                $imported = Product::importFromJson($fullPath, [], [
                    'skip_errors' => true,
                ]);
            }
            
            // Clean up temp file
            Storage::delete($tempPath);
            
            return response()->json([
                'success' => true,
                'message' => "Successfully imported {$imported} products",
                'imported' => $imported,
            ]);
        }
        */
    }

    /**
     * Example 12: Batch Import with Progress
     * Track import progress.
     */
    public function example12(): void
    {
        /*
        // In your controller
        public function importWithProgress(Request $request)
        {
            $file = $request->file('file');
            $tempPath = $file->storeAs('temp', 'import.csv');
            $fullPath = storage_path('app/' . $tempPath);
            
            // Count total lines
            $totalLines = count(file($fullPath)) - 1; // Exclude header
            
            // Process with progress tracking
            $chunkSize = 100;
            $processed = 0;
            
            $imported = Product::importFromCsv($fullPath, [], [
                'chunk_size' => $chunkSize,
                'skip_errors' => true,
            ]);
            
            return response()->json([
                'total' => $totalLines,
                'imported' => $imported,
                'percentage' => ($imported / $totalLines) * 100,
            ]);
        }
        */
    }

    /**
     * Example 13: Import with Data Transformation
     * Transform data during import.
     */
    public function example13(): void
    {
        /*
        // In your model
        class Product extends Model
        {
            use Importable;
            
            protected static function bootImportable()
            {
                // Transform price before saving
                static::importing(function ($data) {
                    if (isset($data['price'])) {
                        $data['price'] = str_replace(['$', ','], '', $data['price']);
                    }
                    return $data;
                });
            }
        }
        */
        
        // Import will automatically transform data
        $imported = Product::importFromCsv('products.csv');
    }

    /**
     * Example 14: Import from Multiple Files
     * Import from several files sequentially.
     */
    public function example14(): void
    {
        $files = ['products_part1.csv', 'products_part2.csv', 'products_part3.csv'];
        $totalImported = 0;
        
        foreach ($files as $file) {
            $imported = Product::importFromCsv($file, [], [
                'skip_errors' => true,
                'update_existing' => true,
                'unique_field' => 'sku',
            ]);
            
            $totalImported += $imported;
            echo "Imported {$imported} from {$file}\n";
        }
        
        echo "Total imported: {$totalImported}\n";
    }

    /**
     * Example 15: Import with Validation Rules
     * Validate data before importing.
     */
    public function example15(): void
    {
        // First, validate the file structure
        $validation = Product::validateImportFile('products.csv', 'csv', [
            'Product Name' => 'name',
            'SKU' => 'sku',
            'Price' => 'price',
        ]);
        
        if (!$validation['is_valid']) {
            throw new \Exception('Invalid file structure');
        }
        
        // Preview to check data quality
        $preview = Product::previewImport('products.csv', 'csv', [
            'Product Name' => 'name',
            'SKU' => 'sku',
            'Price' => 'price',
        ], 100);
        
        // Check for required field warnings
        $hasWarnings = !empty($preview['validation']['warnings']);
        
        if ($hasWarnings) {
            echo "Import has warnings. Proceed with caution.\n";
        }
        
        // Import with strict settings
        $imported = Product::importFromCsv('products.csv', [
            'Product Name' => 'name',
            'SKU' => 'sku',
            'Price' => 'price',
        ], [
            'skip_errors' => false, // Fail on any error
            'update_existing' => false, // Only insert new records
        ]);
    }

    /**
     * Example 16: Queue Large Imports
     * Use queues for large import operations.
     */
    public function example16(): void
    {
        /*
        // Create a job for import
        // app/Jobs/ImportProductsJob.php
        
        class ImportProductsJob implements ShouldQueue
        {
            use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
            
            public function __construct(
                protected string $filePath,
                protected array $mapping,
                protected array $options
            ) {}
            
            public function handle()
            {
                $imported = Product::importFromCsv(
                    $this->filePath,
                    $this->mapping,
                    $this->options
                );
                
                // Notify user or log result
                Log::info("Imported {$imported} products from {$this->filePath}");
            }
        }
        
        // In your controller
        public function import(Request $request)
        {
            $file = $request->file('file');
            $path = $file->store('imports');
            
            // Check if should be queued
            $recommendations = Product::getImportRecommendations(storage_path('app/' . $path));
            
            if ($recommendations['should_use_queue']) {
                ImportProductsJob::dispatch($path, $mapping, $options);
                return response()->json(['message' => 'Import queued']);
            } else {
                $imported = Product::importFromCsv(storage_path('app/' . $path));
                return response()->json(['imported' => $imported]);
            }
        }
        */
    }

    /**
     * Example 17: Import with Custom Column Mapping UI
     * Interactive mapping interface.
     */
    public function example17(): void
    {
        /*
        // In your controller - Show mapping interface
        public function showMapping(Request $request)
        {
            $file = $request->file('file');
            $tempPath = $file->store('temp');
            $fullPath = storage_path('app/' . $tempPath);
            
            // Preview without mapping to get headers
            $preview = Product::previewImport($fullPath, 'csv', [], 5);
            
            // Get model fillable fields
            $modelFields = (new Product())->getFillable();
            
            return view('imports.mapping', [
                'headers' => $preview['headers'],
                'modelFields' => $modelFields,
                'sample' => $preview['sample_data'],
                'tempPath' => $tempPath,
            ]);
        }
        
        // User submits mapping from UI
        public function processWithMapping(Request $request)
        {
            $mapping = $request->input('mapping'); // ['CSV Column' => 'model_field']
            $tempPath = $request->input('temp_path');
            
            $imported = Product::importFromCsv(
                storage_path('app/' . $tempPath),
                $mapping
            );
            
            return redirect()->back()->with('success', "Imported {$imported} records");
        }
        */
    }

    /**
     * Example 18: Import with Duplicate Detection
     * Detect and handle duplicates during import.
     */
    public function example18(): void
    {
        // First preview to check for duplicates
        $preview = Product::previewImport('products.csv', 'csv', [
            'SKU' => 'sku',
            'Name' => 'name',
            'Price' => 'price',
        ], 100);
        
        // Check for duplicate SKUs in file
        $skus = array_column($preview['mapped_preview'], 'sku');
        $duplicates = array_diff_assoc($skus, array_unique($skus));
        
        if (!empty($duplicates)) {
            echo "Warning: File contains duplicate SKUs\n";
        }
        
        // Import with update on duplicate
        $imported = Product::importFromCsv('products.csv', [
            'SKU' => 'sku',
            'Name' => 'name',
            'Price' => 'price',
        ], [
            'update_existing' => true,
            'unique_field' => 'sku',
        ]);
    }

    /**
     * Example 19: Import from URL
     * Download and import file from URL.
     */
    public function example19(): void
    {
        // Download file from URL
        $url = 'https://example.com/products.csv';
        $contents = file_get_contents($url);
        $tempFile = storage_path('app/temp/downloaded.csv');
        file_put_contents($tempFile, $contents);
        
        // Preview first
        $preview = Product::previewImport($tempFile, 'csv', [], 10);
        
        echo "File from URL contains {$preview['statistics']['total_rows']} rows\n";
        
        // Import
        $imported = Product::importFromCsv($tempFile, [], [
            'skip_errors' => true,
        ]);
        
        // Clean up
        unlink($tempFile);
    }

    /**
     * Example 20: Import with Logging
     * Log import operations for audit trail.
     */
    public function example20(): void
    {
        /*
        use Illuminate\Support\Facades\Log;
        
        // Before import
        Log::info('Starting product import', [
            'file' => 'products.csv',
            'user' => auth()->id(),
        ]);
        
        try {
            $imported = Product::importFromCsv('products.csv', [], [
                'skip_errors' => true,
                'update_existing' => true,
                'unique_field' => 'sku',
            ]);
            
            // Log success
            Log::info('Product import completed', [
                'imported' => $imported,
                'file' => 'products.csv',
                'user' => auth()->id(),
            ]);
            
        } catch (\Exception $e) {
            // Log error
            Log::error('Product import failed', [
                'file' => 'products.csv',
                'error' => $e->getMessage(),
                'user' => auth()->id(),
            ]);
            
            throw $e;
        }
        */
    }
}
