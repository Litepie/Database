<?php

/**
 * Batchable Trait Examples
 * 
 * This file demonstrates how to use the Batchable trait
 * for efficient bulk and batch operations.
 */

namespace App\Examples;

use App\Models\Product;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BatchableExamples
{
    /**
     * Example 1: Basic Bulk Insert
     * Insert thousands of records efficiently.
     */
    public function example1(): void
    {
        // Prepare data
        $products = [];
        for ($i = 1; $i <= 10000; $i++) {
            $products[] = [
                'name' => "Product {$i}",
                'sku' => "SKU{$i}",
                'price' => rand(10, 1000),
                'stock' => rand(0, 100),
            ];
        }
        
        // Bulk insert with automatic chunking
        $inserted = Product::bulkInsert($products, 1000);
        // Returns: 10000
        
        // Timestamps are automatically added
        // Processes in chunks of 1000 to prevent memory issues
    }

    /**
     * Example 2: Bulk Update
     * Update multiple records efficiently.
     */
    public function example2(): void
    {
        // Update prices for multiple products
        $updates = [
            ['id' => 1, 'price' => 15.00, 'status' => 'active'],
            ['id' => 2, 'price' => 25.00, 'status' => 'active'],
            ['id' => 3, 'price' => 35.00, 'status' => 'active'],
            // ... thousands more
        ];
        
        $updated = Product::bulkUpdate($updates, 'id', 500);
        // Returns: 3 (number of records updated)
        
        // Update by custom key (e.g., SKU)
        $updates = [
            ['sku' => 'ABC123', 'price' => 20.00],
            ['sku' => 'ABC124', 'price' => 30.00],
        ];
        
        $updated = Product::bulkUpdate($updates, 'sku');
    }

    /**
     * Example 3: Bulk Delete
     * Delete multiple records by IDs.
     */
    public function example3(): void
    {
        // Delete by IDs
        $idsToDelete = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
        $deleted = Product::bulkDelete($idsToDelete);
        // Returns: 10
        
        // Delete by custom key
        $skusToDelete = ['SKU001', 'SKU002', 'SKU003'];
        $deleted = Product::bulkDelete($skusToDelete, 'sku');
        
        // Delete with chunking for large datasets
        $manyIds = range(1, 10000);
        $deleted = Product::bulkDelete($manyIds, 'id', 1000);
    }

    /**
     * Example 4: Bulk Upsert (Insert or Update)
     * Insert new records or update existing ones.
     */
    public function example4(): void
    {
        // Upsert products by SKU
        $products = [
            ['sku' => 'ABC123', 'name' => 'Product 1', 'price' => 10.00],
            ['sku' => 'ABC124', 'name' => 'Product 2', 'price' => 20.00],
            ['sku' => 'ABC125', 'name' => 'Product 3', 'price' => 30.00],
        ];
        
        // Will insert if SKU doesn't exist, update if it does
        $affected = Product::bulkUpsert(
            $products,
            ['sku'],                    // Unique identifier
            ['name', 'price']          // Fields to update
        );
        
        // Upsert with all fields except unique key
        $affected = Product::bulkUpsert($products, ['sku']);
        
        // Multiple unique fields
        $orders = [
            ['order_number' => 'ORD001', 'customer_id' => 1, 'total' => 100],
            ['order_number' => 'ORD002', 'customer_id' => 2, 'total' => 200],
        ];
        
        $affected = Order::bulkUpsert(
            $orders,
            ['order_number', 'customer_id'],
            ['total']
        );
    }

    /**
     * Example 5: Batch Processing
     * Process large datasets without memory issues.
     */
    public function example5(): void
    {
        // Process all products in batches
        $processed = Product::batchProcess(function ($products) {
            foreach ($products as $product) {
                // Process each product
                echo "Processing: {$product->name}\n";
                
                // Perform operations
                $product->calculateMetrics();
                $product->updateCache();
            }
        }, 1000);
        
        echo "Processed {$processed} products";
    }

    /**
     * Example 6: Batch Update with Callback
     * Update records in batches with custom logic.
     */
    public function example6(): void
    {
        // Increase all prices by 10%
        $updated = Product::batchUpdate(function ($product) {
            $product->price = $product->price * 1.1;
        }, 500);
        
        echo "Updated {$updated} products";
        
        // Only saves records that were actually modified
        $updated = Product::where('status', 'draft')
            ->batchUpdate(function ($product) {
                if ($product->price > 100) {
                    $product->status = 'premium';
                }
            });
    }

    /**
     * Example 7: Import CSV Data
     * Bulk insert data from CSV file.
     */
    public function example7(): void
    {
        $csvFile = storage_path('imports/products.csv');
        $products = [];
        
        // Read CSV
        if (($handle = fopen($csvFile, 'r')) !== false) {
            $header = fgetcsv($handle);
            
            while (($row = fgetcsv($handle)) !== false) {
                $products[] = [
                    'name' => $row[0],
                    'sku' => $row[1],
                    'price' => $row[2],
                    'stock' => $row[3],
                ];
                
                // Insert in batches of 1000
                if (count($products) >= 1000) {
                    Product::bulkInsert($products);
                    $products = [];
                }
            }
            
            // Insert remaining records
            if (!empty($products)) {
                Product::bulkInsert($products);
            }
            
            fclose($handle);
        }
    }

    /**
     * Example 8: Bulk Upsert from External API
     * Sync data from external source.
     */
    public function example8(): void
    {
        // Fetch data from API
        $apiProducts = [
            ['external_id' => 'EXT001', 'name' => 'Product A', 'price' => 50],
            ['external_id' => 'EXT002', 'name' => 'Product B', 'price' => 75],
            ['external_id' => 'EXT003', 'name' => 'Product C', 'price' => 100],
        ];
        
        // Upsert based on external_id
        $synced = Product::bulkUpsert(
            $apiProducts,
            ['external_id'],
            ['name', 'price']
        );
        
        echo "Synced {$synced} products from API";
    }

    /**
     * Example 9: Optimal Chunk Size
     * Use optimal chunk size based on available memory.
     */
    public function example9(): void
    {
        // Get optimal chunk size
        $chunkSize = Product::getOptimalChunkSize();
        // Returns: 1000-10000 depending on available memory
        
        // Use it for bulk operations
        $products = $this->generateProducts(50000);
        $inserted = Product::bulkInsert($products, $chunkSize);
        
        // Get batch statistics
        $stats = Product::getBatchStats();
        /*
        Returns:
        [
            'table_size' => 50000,
            'optimal_chunk_size' => 2000,
            'recommended_batch_size' => 1000,
            'estimated_memory_per_1000' => '1.00 MB',
            'available_memory' => '512.00 MB'
        ]
        */
    }

    /**
     * Example 10: Bulk Insert with Duplicate Detection
     * Insert records while ignoring duplicates.
     */
    public function example10(): void
    {
        $products = [
            ['sku' => 'ABC123', 'name' => 'Product 1', 'price' => 10.00],
            ['sku' => 'ABC124', 'name' => 'Product 2', 'price' => 20.00],
            ['sku' => 'ABC123', 'name' => 'Product 1', 'price' => 10.00], // Duplicate
            ['sku' => 'ABC125', 'name' => 'Product 3', 'price' => 30.00],
        ];
        
        $result = Product::bulkInsertIgnoreDuplicates($products, ['sku']);
        
        /*
        Returns:
        [
            'inserted' => 3,
            'duplicates' => 1
        ]
        */
        
        echo "Inserted: {$result['inserted']}, Skipped: {$result['duplicates']}";
    }

    /**
     * Example 11: Mass Price Update
     * Update prices for categories.
     */
    public function example11(): void
    {
        // Get all products in a category
        $products = Product::where('category_id', 5)->get();
        
        // Prepare bulk update data
        $updates = $products->map(function ($product) {
            return [
                'id' => $product->id,
                'price' => $product->price * 0.9, // 10% discount
                'sale_price' => $product->price * 0.9,
            ];
        })->toArray();
        
        // Bulk update
        $updated = Product::bulkUpdate($updates);
        
        echo "Applied discount to {$updated} products";
    }

    /**
     * Example 12: Cleanup Old Records
     * Delete old records in batches.
     */
    public function example12(): void
    {
        // Get IDs of old orders
        $oldOrderIds = Order::where('created_at', '<', now()->subYears(2))
            ->pluck('id')
            ->toArray();
        
        // Delete in batches
        $deleted = Order::bulkDelete($oldOrderIds, 'id', 500);
        
        echo "Deleted {$deleted} old orders";
    }

    /**
     * Example 13: Database Migration
     * Migrate data between tables.
     */
    public function example13(): void
    {
        // Read from old table
        $oldProducts = DB::table('old_products')->get();
        
        // Transform and prepare for new table
        $newProducts = $oldProducts->map(function ($old) {
            return [
                'name' => $old->product_name,
                'sku' => $old->product_code,
                'price' => $old->unit_price,
                'description' => $old->product_desc,
            ];
        })->toArray();
        
        // Bulk insert into new table
        $inserted = Product::bulkInsert($newProducts, 1000);
        
        echo "Migrated {$inserted} products";
    }

    /**
     * Example 14: Batch Status Update
     * Update status based on conditions.
     */
    public function example14(): void
    {
        // Update status for out-of-stock products
        $updated = Product::batchUpdate(function ($product) {
            if ($product->stock <= 0) {
                $product->status = 'out_of_stock';
            } elseif ($product->stock < 10) {
                $product->status = 'low_stock';
            } else {
                $product->status = 'in_stock';
            }
        }, 1000);
        
        echo "Updated status for {$updated} products";
    }

    /**
     * Example 15: Bulk Create with Relationships
     * Create records with related data.
     */
    public function example15(): void
    {
        // Prepare orders with line items
        $ordersData = [];
        
        for ($i = 1; $i <= 1000; $i++) {
            $ordersData[] = [
                'order_number' => "ORD{$i}",
                'customer_id' => rand(1, 100),
                'total' => rand(50, 500),
                'status' => 'pending',
            ];
        }
        
        // Bulk insert orders
        $inserted = Order::bulkInsert($ordersData);
        
        echo "Created {$inserted} orders";
    }

    /**
     * Example 16: Synchronize Inventory
     * Sync inventory from warehouse system.
     */
    public function example16(): void
    {
        // Get inventory data from warehouse API
        $inventoryUpdates = [
            ['sku' => 'ABC123', 'stock' => 50, 'warehouse' => 'WH1'],
            ['sku' => 'ABC124', 'stock' => 75, 'warehouse' => 'WH1'],
            ['sku' => 'ABC125', 'stock' => 0, 'warehouse' => 'WH1'],
        ];
        
        // Upsert inventory
        $synced = Product::bulkUpsert(
            $inventoryUpdates,
            ['sku', 'warehouse'],
            ['stock']
        );
        
        echo "Synced inventory for {$synced} products";
    }

    /**
     * Example 17: Performance Comparison
     * Compare bulk operations vs individual saves.
     */
    public function example17(): void
    {
        $products = $this->generateProducts(1000);
        
        // Method 1: Individual inserts (SLOW)
        $start = microtime(true);
        foreach ($products as $product) {
            Product::create($product);
        }
        $slowTime = microtime(true) - $start;
        
        // Method 2: Bulk insert (FAST)
        $start = microtime(true);
        Product::bulkInsert($products);
        $fastTime = microtime(true) - $start;
        
        echo "Individual: {$slowTime}s\n";
        echo "Bulk: {$fastTime}s\n";
        echo "Speedup: " . round($slowTime / $fastTime, 2) . "x faster\n";
    }

    /**
     * Example 18: Conditional Bulk Updates
     * Update different fields based on conditions.
     */
    public function example18(): void
    {
        // Get products and prepare updates
        $products = Product::all();
        $updates = [];
        
        foreach ($products as $product) {
            $update = ['id' => $product->id];
            
            // Apply different pricing tiers
            if ($product->category_id === 1) {
                $update['price'] = $product->cost * 1.5; // 50% markup
            } elseif ($product->category_id === 2) {
                $update['price'] = $product->cost * 1.3; // 30% markup
            } else {
                $update['price'] = $product->cost * 1.2; // 20% markup
            }
            
            $updates[] = $update;
        }
        
        // Bulk update
        $updated = Product::bulkUpdate($updates);
    }

    /**
     * Example 19: Export and Re-import
     * Export, modify, and re-import data.
     */
    public function example19(): void
    {
        // Export products
        $products = Product::all()->map(function ($product) {
            return [
                'sku' => $product->sku,
                'name' => $product->name,
                'price' => $product->price,
            ];
        })->toArray();
        
        // Modify data (e.g., apply discount)
        $modified = array_map(function ($product) {
            $product['price'] = $product['price'] * 0.9;
            return $product;
        }, $products);
        
        // Re-import with upsert
        $updated = Product::bulkUpsert($modified, ['sku'], ['price']);
        
        echo "Updated {$updated} products";
    }

    /**
     * Example 20: Memory-Efficient Large Dataset Processing
     * Process millions of records without memory issues.
     */
    public function example20(): void
    {
        // Get statistics first
        $stats = Product::getBatchStats();
        $chunkSize = $stats['recommended_batch_size'];
        
        echo "Processing with chunk size: {$chunkSize}\n";
        echo "Available memory: {$stats['available_memory']}\n";
        
        // Process in batches
        $totalProcessed = Product::batchProcess(function ($products) {
            foreach ($products as $product) {
                // Complex processing
                $product->calculateMetrics();
                $product->updateSearchIndex();
                $product->generateThumbnails();
            }
        }, $chunkSize);
        
        echo "Processed {$totalProcessed} products\n";
        
        // Memory usage stays constant regardless of dataset size
        echo "Memory used: " . memory_get_peak_usage(true) / 1024 / 1024 . " MB\n";
    }

    /**
     * Helper: Generate sample products
     */
    private function generateProducts(int $count): array
    {
        $products = [];
        for ($i = 1; $i <= $count; $i++) {
            $products[] = [
                'name' => "Product {$i}",
                'sku' => "SKU" . str_pad($i, 6, '0', STR_PAD_LEFT),
                'price' => rand(10, 1000),
                'stock' => rand(0, 100),
                'category_id' => rand(1, 10),
            ];
        }
        return $products;
    }
}

/**
 * USAGE IN MODELS
 */

/*
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Litepie\Database\Traits\Batchable;

class Product extends Model
{
    use Batchable;
    
    protected $fillable = ['name', 'sku', 'price', 'stock', 'category_id'];
}

class Order extends Model
{
    use Batchable;
    
    protected $fillable = ['order_number', 'customer_id', 'total', 'status'];
}
*/

/**
 * ARTISAN COMMAND EXAMPLE
 */

/*
namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class ImportProductsCommand extends Command
{
    protected $signature = 'products:import {file}';
    protected $description = 'Import products from CSV file';

    public function handle()
    {
        $file = $this->argument('file');
        
        if (!file_exists($file)) {
            $this->error("File not found: {$file}");
            return 1;
        }
        
        $products = [];
        $handle = fopen($file, 'r');
        $header = fgetcsv($handle);
        
        while (($row = fgetcsv($handle)) !== false) {
            $products[] = [
                'sku' => $row[0],
                'name' => $row[1],
                'price' => $row[2],
                'stock' => $row[3],
            ];
            
            // Insert in batches
            if (count($products) >= 1000) {
                Product::bulkInsert($products);
                $this->info("Imported " . count($products) . " products");
                $products = [];
            }
        }
        
        // Insert remaining
        if (!empty($products)) {
            Product::bulkInsert($products);
            $this->info("Imported " . count($products) . " products");
        }
        
        fclose($handle);
        $this->info("Import completed!");
        
        return 0;
    }
}
*/

/**
 * SCHEDULED TASK EXAMPLE
 */

/*
// In app/Console/Kernel.php

protected function schedule(Schedule $schedule)
{
    // Cleanup old records daily
    $schedule->call(function () {
        $oldIds = Order::where('created_at', '<', now()->subMonths(6))
            ->where('status', 'completed')
            ->pluck('id')
            ->toArray();
        
        $deleted = Order::bulkDelete($oldIds, 'id', 1000);
        
        Log::info("Cleaned up {$deleted} old orders");
    })->daily();
    
    // Update product status
    $schedule->call(function () {
        $updated = Product::batchUpdate(function ($product) {
            if ($product->stock <= 0) {
                $product->status = 'out_of_stock';
            }
        }, 500);
        
        Log::info("Updated {$updated} product statuses");
    })->hourly();
}
*/
