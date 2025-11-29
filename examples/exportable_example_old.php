<?php

/**
 * Exportable Trait Examples
 * 
 * This file demonstrates how to use the Exportable trait
 * for data import/export operations.
 */

namespace App\Examples;

use App\Models\Product;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class ExportableExamples
{
    /**
     * Example 1: Basic CSV Export
     * Export data to CSV file.
     */
    public function example1(): void
    {
        // Export all active products to CSV
        $path = Product::where('status', 'active')
            ->exportToCsv();
        
        // Returns: "exports/product_export_2024-11-28_10-30-45.csv"
        
        // Export with specific columns
        $path = Product::where('price', '>', 100)
            ->exportToCsv(['id', 'name', 'price', 'category']);
        
        // Export with custom filename
        $path = Product::where('status', 'active')
            ->exportToCsv(['id', 'name', 'price'], 'active_products.csv');
    }

    /**
     * Example 2: JSON Export
     * Export data to JSON format.
     */
    public function example2(): void
    {
        // Export to JSON
        $path = Product::where('status', 'active')
            ->exportToJson();
        
        // Returns: "exports/product_export_2024-11-28_10-30-45.json"
        
        // Export specific columns
        $path = Order::where('status', 'completed')
            ->exportToJson(['id', 'order_number', 'total', 'customer_name']);
        
        // Custom filename
        $path = User::where('role', 'admin')
            ->exportToJson(['id', 'name', 'email'], 'admins.json');
    }

    /**
     * Example 3: Excel Export
     * Export data to Excel format.
     */
    public function example3(): void
    {
        // Export to Excel (CSV compatible)
        $path = Product::where('category_id', 1)
            ->exportToExcel();
        
        // Returns: "exports/product_export_2024-11-28_10-30-45.csv"
        
        // With specific columns
        $path = Order::where('created_at', '>', now()->subMonth())
            ->exportToExcel(['order_number', 'customer', 'total', 'status']);
    }

    /**
     * Example 4: Stream Export (Large Datasets)
     * Stream export directly to browser download.
     */
    public function example4(): void
    {
        // In your controller
        /*
        public function export(Request $request)
        {
            // Stream CSV export - no memory issues with large datasets
            return Product::where('status', 'active')
                ->streamExport('csv');
        }
        */
        
        // Stream JSON export
        /*
        public function exportJson()
        {
            return Product::query()
                ->streamExport('json');
        }
        */
        
        // With custom transformation
        /*
        public function exportWithTransform()
        {
            return Product::query()->streamExport('csv', function ($product) {
                // Transform each record before export
                $product->price = '$' . number_format($product->price, 2);
                return $product;
            });
        }
        */
    }

    /**
     * Example 5: Export with Relationships
     * Include related data in exports.
     */
    public function example5(): void
    {
        // Export products with category name
        $path = Product::with('category')
            ->where('status', 'active')
            ->exportToCsv([
                'id',
                'name',
                'price',
                'category.name',  // Nested relationship
                'category.slug'
            ]);
        
        // Export orders with customer info
        $path = Order::with('customer')
            ->where('status', 'completed')
            ->exportToJson([
                'id',
                'order_number',
                'total',
                'customer.name',
                'customer.email',
                'customer.phone'
            ]);
    }

    /**
     * Example 6: Configure Export Settings
     * Customize export behavior.
     */
    public function example6(): void
    {
        // Configure export settings
        $path = Product::query()
            ->configureExport([
                'chunk_size' => 500,        // Process 500 records at a time
                'disk' => 's3',             // Store on S3
                'path' => 'custom/exports', // Custom path
            ])
            ->exportToCsv();
        
        // Chain with query
        $path = Order::where('total', '>', 1000)
            ->configureExport(['chunk_size' => 100])
            ->exportToJson();
    }

    /**
     * Example 7: Export Statistics
     * Get export size estimates before exporting.
     */
    public function example7(): void
    {
        // Get export statistics
        $stats = Product::where('status', 'active')
            ->getExportStats();
        
        /*
        Returns:
        [
            'model' => 'Product',
            'total_records' => 15420,
            'estimated_csv_size' => '1.47 MB',
            'estimated_json_size' => '2.94 MB',
            'recommended_chunk_size' => 1000
        ]
        */
        
        // Use stats to decide export format
        if ($stats['total_records'] > 50000) {
            // Use streaming for large datasets
            return Product::query()->streamExport('csv');
        } else {
            // Regular export for smaller datasets
            return Product::query()->exportToCsv();
        }
    }

    /**
     * Example 8: Import from CSV
     * Import data from CSV file.
     */
    public function example8(): void
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
     * Example 9: Import from JSON
     * Import data from JSON file.
     */
    public function example9(): void
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
     * Example 10: Import with Error Handling
     * Handle errors during import.
     */
    public function example10(): void
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
     * Example 11: Update Existing Records on Import
     * Upsert behavior during import.
     */
    public function example11(): void
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
     * Example 12: Exportable Columns Whitelist
     * Define exportable columns in model.
     */
    public function example12(): void
    {
        /*
        // In your model
        class Product extends Model
        {
            use Exportable;
            
            protected $exportable = [
                'id',
                'name',
                'sku',
                'price',
                'category',
                'status',
            ];
            
            // These columns will be exported by default
        }
        */
        
        // Export uses $exportable columns automatically
        $path = Product::query()->exportToCsv();
        
        // Override with specific columns
        $path = Product::query()->exportToCsv(['id', 'name', 'price']);
    }

    /**
     * Example 13: API Endpoint for Export
     * Export data from API endpoint.
     */
    public function example13(): void
    {
        /*
        // In your controller
        public function export(Request $request)
        {
            $format = $request->query('format', 'csv'); // csv, json, xlsx
            
            $query = Product::query();
            
            // Apply filters from request
            if ($status = $request->query('status')) {
                $query->where('status', $status);
            }
            
            if ($category = $request->query('category')) {
                $query->where('category_id', $category);
            }
            
            // Stream export for better performance
            return $query->streamExport($format);
        }
        */
        
        // API Usage:
        // GET /api/products/export?format=csv&status=active
        // GET /api/products/export?format=json&category=1
        // GET /api/products/export?format=xlsx
    }

    /**
     * Example 14: API Endpoint for Import
     * Import data via API.
     */
    public function example14(): void
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
     * Example 15: Scheduled Export
     * Export data on schedule (daily, weekly, etc.).
     */
    public function example15(): void
    {
        /*
        // In app/Console/Kernel.php
        protected function schedule(Schedule $schedule)
        {
            // Daily product export
            $schedule->call(function () {
                $path = Product::where('status', 'active')
                    ->configureExport([
                        'disk' => 's3',
                        'path' => 'daily-exports'
                    ])
                    ->exportToCsv();
                
                // Optionally send notification
                // Mail::to('admin@example.com')->send(new ExportCompleted($path));
            })->daily();
            
            // Weekly sales report
            $schedule->call(function () {
                $path = Order::whereBetween('created_at', [
                        now()->subWeek(),
                        now()
                    ])
                    ->exportToJson(['order_number', 'total', 'customer', 'status']);
            })->weekly();
        }
        */
    }

    /**
     * Example 16: Export with Filtering
     * Combine with Searchable trait for filtered exports.
     */
    public function example16(): void
    {
        /*
        // If using Searchable trait
        class Product extends Model
        {
            use Searchable, Exportable;
        }
        */
        
        // Export search results
        $path = Product::search('laptop')
            ->exportToCsv();
        
        // Export filtered results
        $path = Product::filterQueryString('price:GT(100);status:EQ(active)')
            ->exportToJson();
    }

    /**
     * Example 17: Export with Pagination
     * Export specific page of results.
     */
    public function example17(): void
    {
        // Export first 1000 records
        $path = Product::query()
            ->take(1000)
            ->exportToCsv();
        
        // Export specific range
        $path = Product::query()
            ->skip(1000)
            ->take(1000)
            ->exportToJson();
    }

    /**
     * Example 18: Verify Export File
     * Check exported file after creation.
     */
    public function example18(): void
    {
        // Export to file
        $path = Product::where('status', 'active')
            ->exportToCsv();
        
        // Verify file exists
        if (Storage::disk('local')->exists($path)) {
            $size = Storage::disk('local')->size($path);
            echo "Export successful! File size: " . $this->formatBytes($size);
            
            // Get file URL
            $url = Storage::disk('local')->url($path);
            
            // Download file
            return Storage::disk('local')->download($path);
        }
    }

    /**
     * Example 19: Batch Import with Progress
     * Track import progress.
     */
    public function example19(): void
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
     * Example 20: Export to Cloud Storage
     * Export directly to S3, Google Cloud, etc.
     */
    public function example20(): void
    {
        // Export to S3
        $path = Product::query()
            ->configureExport([
                'disk' => 's3',
                'path' => 'exports/products',
            ])
            ->exportToCsv();
        
        // Get public URL
        $url = Storage::disk('s3')->url($path);
        
        // Generate temporary download link (S3)
        $downloadUrl = Storage::disk('s3')->temporaryUrl(
            $path,
            now()->addMinutes(30)
        );
    }

    /**
     * Example 21: Preview Import Data
     * Preview import data before actually importing.
     */
    public function example21(): void
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
     * Example 22: Preview JSON Import
     * Preview JSON file before import.
     */
    public function example22(): void
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
     * Example 23: Validate Import File
     * Validate file before importing.
     */
    public function example23(): void
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
     * Example 24: Get Import Recommendations
     * Get recommendations for import settings.
     */
    public function example24(): void
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
     * Example 25: Import with Preview in Controller
     * Real-world controller implementation.
     */
    public function example25(): void
    {
        
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
                    'update_existing' => $request->input('update_existing', false),
                    'unique_field' => $request->input('unique_field', 'sku'),
                ]);
            } else {
                $imported = Product::importFromJson($fullPath, $mapping, [
                    'chunk_size' => $recommendations['chunk_size'],
                    'skip_errors' => true,
                ]);
            }
            
            // Clean up temp file
            Storage::delete($tempPath);
            
            return redirect()->back()->with('success', "Successfully imported {$imported} products");
        }
        
    }
}

/**
 * USAGE IN MODELS
 */


namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Litepie\Database\Traits\Exportable;

class Product extends Model
{
    use Exportable;
    
    // Define exportable columns (optional)
    protected $exportable = [
        'id',
        'name',
        'sku',
        'price',
        'category',
        'status',
        'created_at',
    ];
    
    // Configure export settings (optional)
    protected $exportConfig = [
        'chunk_size' => 1000,
        'disk' => 'local',
        'path' => 'exports',
    ];
}


/**
 * ROUTES
 */

/*
// Export routes
Route::get('/products/export', [ProductController::class, 'export'])->name('products.export');
Route::post('/products/import', [ProductController::class, 'import'])->name('products.import');

// API routes
Route::prefix('api')->group(function () {
    Route::get('/products/export', [ProductController::class, 'exportApi']);
    Route::post('/products/import', [ProductController::class, 'importApi']);
});
*/

/**
 * CONTROLLER EXAMPLE
 */


namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function export(Request $request)
    {
        $format = $request->query('format', 'csv');
        
        return Product::where('status', 'active')
            ->streamExport($format);
    }
    
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,json',
        ]);
        
        $file = $request->file('file');
        $path = $file->storeAs('temp', 'import.csv');
        $fullPath = storage_path('app/' . $path);
        
        $imported = Product::importFromCsv($fullPath, [], [
            'skip_errors' => true,
            'update_existing' => true,
            'unique_field' => 'sku',
        ]);
        
        return redirect()->back()->with('success', "Imported {$imported} products");
    }
}

