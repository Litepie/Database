<?php

/**
 * Exportable Trait Examples
 * 
 * This file demonstrates how to use the Exportable trait
 * for data export operations.
 */

namespace App\Examples;

use App\Models\Product;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

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
            ->exportToCsv(null, ['id', 'name', 'price', 'category']);
        
        // Export with custom filename
        $path = Product::where('status', 'active')
            ->exportToCsv('active_products.csv', ['id', 'name', 'price']);
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
            ->exportToJson(null, ['id', 'order_number', 'total', 'customer_name']);
        
        // Custom filename
        $path = User::where('role', 'admin')
            ->exportToJson('admins.json', ['id', 'name', 'email']);
        
        // Pretty print JSON
        $path = Product::query()
            ->exportToJson('products.json', [], true);
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
        
        // Returns: "exports/product_export_2024-11-28_10-30-45.xlsx"
        
        // With specific columns
        $path = Order::where('created_at', '>', now()->subMonth())
            ->exportToExcel(null, ['order_number', 'customer', 'total', 'status']);
        
        // With custom headers
        $path = Product::query()->exportToExcel(
            'products.xlsx',
            ['id', 'name', 'price'],
            ['ID', 'Product Name', 'Unit Price']
        );
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
        
        // Stream Excel export
        /*
        public function exportExcel()
        {
            return Product::query()
                ->streamExport('excel', 'products.xlsx');
        }
        */
        
        // Stream with specific columns
        /*
        public function exportSpecific()
        {
            return Product::query()
                ->streamExport('csv', 'products.csv', ['id', 'name', 'price']);
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
            ->exportToCsv(null, [
                'id',
                'name',
                'price',
                'category.name',  // Nested relationship
                'category.slug'
            ]);
        
        // Export orders with customer info
        $path = Order::with('customer')
            ->where('status', 'completed')
            ->exportToJson(null, [
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
        Product::query()
            ->configureExport([
                'chunk_size' => 500,        // Process 500 records at a time
                'disk' => 's3',             // Store on S3
                'directory' => 'custom/exports', // Custom directory
                'include_headers' => true,  // Include column headers
                'date_format' => 'Y-m-d',   // Date format
            ]);
        
        $path = Product::query()->exportToCsv();
        
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
            'total_records' => 15420,
            'estimated_size' => '1.47 MB',
            'estimated_size_bytes' => 1542000,
            'recommended_chunk_size' => 1000,
            'current_chunk_size' => 1000,
            'estimated_memory' => '2.21 MB'
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
     * Example 8: Exportable Columns Whitelist
     * Define exportable columns in model.
     */
    public function example8(): void
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
        $path = Product::query()->exportToCsv(null, ['id', 'name', 'price']);
    }

    /**
     * Example 9: API Endpoint for Export
     * Export data from API endpoint.
     */
    public function example9(): void
    {
        /*
        // In your controller
        public function export(Request $request)
        {
            $format = $request->query('format', 'csv'); // csv, json, excel
            
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
        // GET /api/products/export?format=excel
    }

    /**
     * Example 10: Scheduled Export
     * Export data on schedule (daily, weekly, etc.).
     */
    public function example10(): void
    {
        /*
        // In app/Console/Kernel.php
        protected function schedule(Schedule $schedule)
        {
            // Daily product export
            $schedule->call(function () {
                Product::query()
                    ->configureExport([
                        'disk' => 's3',
                        'directory' => 'daily-exports'
                    ]);
                
                $path = Product::where('status', 'active')
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
                    ->exportToJson(null, ['order_number', 'total', 'customer', 'status']);
            })->weekly();
        }
        */
    }

    /**
     * Example 11: Export with Filtering
     * Combine with Searchable trait for filtered exports.
     */
    public function example11(): void
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
     * Example 12: Export with Pagination
     * Export specific page of results.
     */
    public function example12(): void
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
     * Example 13: Verify Export File
     * Check exported file after creation.
     */
    public function example13(): void
    {
        // Export to file
        $path = Product::where('status', 'active')
            ->exportToCsv();
        
        // Verify file exists
        if (Storage::disk('local')->exists($path)) {
            $size = Storage::disk('local')->size($path);
            echo "Export successful! File size: " . round($size / 1024, 2) . " KB\n";
            
            // Get file URL
            $url = Storage::disk('local')->url($path);
            
            // Download file
            return Storage::disk('local')->download($path);
        }
    }

    /**
     * Example 14: Export to Cloud Storage
     * Export directly to S3, Google Cloud, etc.
     */
    public function example14(): void
    {
        // Configure for S3
        Product::query()
            ->configureExport([
                'disk' => 's3',
                'directory' => 'exports/products',
            ]);
        
        // Export to S3
        $path = Product::query()->exportToCsv();
        
        // Get public URL
        $url = Storage::disk('s3')->url($path);
        
        // Generate temporary download link (S3)
        $downloadUrl = Storage::disk('s3')->temporaryUrl(
            $path,
            now()->addMinutes(30)
        );
    }

    /**
     * Example 15: Export Multiple Models
     * Export different models in one operation.
     */
    public function example15(): void
    {
        // Export products
        $productsPath = Product::where('status', 'active')
            ->exportToCsv('active_products.csv');
        
        // Export orders
        $ordersPath = Order::where('status', 'completed')
            ->exportToCsv('completed_orders.csv');
        
        // Export users
        $usersPath = User::where('role', 'customer')
            ->exportToCsv('customers.csv');
        
        // Create ZIP archive
        /*
        $zip = new \ZipArchive();
        $zipPath = storage_path('app/exports/export_bundle.zip');
        
        if ($zip->open($zipPath, \ZipArchive::CREATE) === true) {
            $zip->addFile(storage_path('app/' . $productsPath), 'products.csv');
            $zip->addFile(storage_path('app/' . $ordersPath), 'orders.csv');
            $zip->addFile(storage_path('app/' . $usersPath), 'users.csv');
            $zip->close();
        }
        
        return response()->download($zipPath);
        */
    }

    /**
     * Example 16: Export with Custom Formatting
     * Format data during export.
     */
    public function example16(): void
    {
        /*
        // In your model
        class Product extends Model
        {
            use Exportable;
            
            protected $exportable = ['id', 'name', 'price', 'formatted_price'];
            
            public function getFormattedPriceAttribute()
            {
                return '$' . number_format($this->price, 2);
            }
        }
        */
        
        // Export will include formatted price
        $path = Product::query()->exportToCsv();
    }

    /**
     * Example 17: Export with Date Ranges
     * Export records within date ranges.
     */
    public function example17(): void
    {
        // Export last month's orders
        $path = Order::whereBetween('created_at', [
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth()
            ])
            ->exportToCsv('last_month_orders.csv');
        
        // Export this year's products
        $path = Product::whereYear('created_at', now()->year)
            ->exportToJson('this_year_products.json');
    }

    /**
     * Example 18: Export with Aggregations
     * Export aggregated data.
     */
    public function example18(): void
    {
        /*
        // Using Aggregatable trait
        class Order extends Model
        {
            use Exportable, Aggregatable;
        }
        */
        
        // Export aggregated sales by month
        /*
        $salesByMonth = Order::aggregate('total', 'sum')
            ->groupBy('month')
            ->get();
        
        // Convert to exportable format
        $data = $salesByMonth->map(function ($item) {
            return [
                'month' => $item->month,
                'total_sales' => $item->total_sum,
                'order_count' => $item->count,
            ];
        });
        
        // Save to JSON
        Storage::put('exports/sales_by_month.json', $data->toJson());
        */
    }

    /**
     * Example 19: Export Progress Tracking
     * Track export progress for large datasets.
     */
    public function example19(): void
    {
        /*
        // In your controller
        public function exportWithProgress(Request $request)
        {
            $stats = Product::query()->getExportStats();
            
            if ($stats['total_records'] > 10000) {
                // Queue the export for background processing
                ExportProductsJob::dispatch(auth()->user());
                
                return response()->json([
                    'message' => 'Export queued. You will be notified when complete.',
                    'estimated_time' => $this->estimateTime($stats['total_records']),
                ]);
            }
            
            // Small export - do it synchronously
            return Product::query()->streamExport('csv');
        }
        
        private function estimateTime(int $records): string
        {
            $seconds = ceil($records / 1000); // Rough estimate
            return $seconds > 60 ? ceil($seconds / 60) . ' minutes' : $seconds . ' seconds';
        }
        */
    }

    /**
     * Example 20: Export with Permissions
     * Control what columns users can export.
     */
    public function example20(): void
    {
        /*
        // In your controller
        public function export(Request $request)
        {
            $user = auth()->user();
            
            // Define allowed columns based on user role
            $allowedColumns = match($user->role) {
                'admin' => ['id', 'name', 'price', 'cost', 'profit', 'supplier'],
                'manager' => ['id', 'name', 'price', 'stock'],
                'viewer' => ['id', 'name', 'price'],
                default => ['id', 'name'],
            };
            
            // Export only allowed columns
            return Product::query()
                ->streamExport('csv', 'products.csv', $allowedColumns);
        }
        */
    }
}
