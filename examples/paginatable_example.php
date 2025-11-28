<?php

/**
 * Paginatable Trait Examples
 * 
 * This file demonstrates how to use the Paginatable trait
 * for handling large datasets efficiently with various pagination strategies.
 */

namespace App\Examples;

use App\Models\Article;
use App\Models\Product;
use App\Models\Order;
use App\Models\LogEntry;

class OptimizedPaginationExamples
{
    /**
     * Example 1: Standard vs Fast Pagination
     * 
     * Fast pagination doesn't count total records, making it much faster
     */
    public function standardVsFastPagination()
    {
        // Standard pagination (counts total records)
        $standardPagination = Product::where('active', true)
            ->paginate(20);
        // Returns: total count, last_page, has more pages
        
        // Fast pagination (no count query, uses LIMIT + 1)
        $fastPagination = Product::where('active', true)
            ->fastPaginate(20);
        // Returns: has more pages (no total count)
        
        // Use fast pagination when you don't need total count
        // Typical use: infinite scroll, "Load More" buttons
        
        return $fastPagination;
    }

    /**
     * Example 2: Cursor Pagination for Large Datasets
     * 
     * Cursor pagination is much faster than offset pagination for large datasets
     */
    public function cursorPagination()
    {
        // Basic cursor pagination
        $products = Product::where('active', true)
            ->cursorPaginate(50);
        
        // Cursor pagination with custom order
        $articles = Article::where('status', 'published')
            ->orderBy('created_at', 'desc')
            ->cursorPaginate(25);
        
        // In your view, use the cursor:
        // Next page: ?cursor=eyJpZCI6MTAwLCJfcG9pbn...
        // Previous page: ?cursor=eyJpZCI6NTAsIl9wb2lu...
        
        return $articles;
    }

    /**
     * Example 3: Seek Pagination for Real-time Feeds
     * 
     * Perfect for social feeds, activity streams, chat messages
     */
    public function seekPagination()
    {
        // Get first page (most recent)
        $firstPage = Article::where('status', 'published')
            ->seekPaginate(limit: 20);
        
        // Get next page after ID 100
        $nextPage = Article::where('status', 'published')
            ->seekPaginate(
                limit: 20, 
                lastId: 100, 
                direction: 'next'
            );
        
        // Get previous page before ID 100
        $previousPage = Article::where('status', 'published')
            ->seekPaginate(
                limit: 20, 
                lastId: 100, 
                direction: 'prev'
            );
        
        // Seek by custom column (e.g., timestamp)
        $articles = Article::where('status', 'published')
            ->seekPaginate(
                limit: 20,
                lastId: '2024-01-01 12:00:00',
                direction: 'next',
                orderColumn: 'published_at'
            );
        
        return $firstPage;
    }

    /**
     * Example 4: Optimized Pagination with Approximate Count
     * 
     * Uses approximate count for tables with > 1M rows
     */
    public function optimizedPagination()
    {
        // Automatically uses approximate count for large tables
        $orders = Order::where('status', 'completed')
            ->optimizedPaginate(20);
        
        // Force approximate count
        $logs = LogEntry::optimizedPaginate(
            perPage: 50,
            useApproximateCount: true
        );
        
        // Disable approximate count
        $products = Product::optimizedPaginate(
            perPage: 20,
            useApproximateCount: false
        );
        
        return $orders;
    }

    /**
     * Example 5: Cached Pagination
     * 
     * Cache the total count for expensive queries
     */
    public function cachedPagination()
    {
        // Cache count for 5 minutes (300 seconds)
        $products = Product::with(['category', 'brand'])
            ->where('active', true)
            ->cachedPaginate(
                perPage: 20,
                cacheTtl: 300
            );
        
        // Cache for 1 hour
        $articles = Article::where('status', 'published')
            ->whereHas('tags', function ($query) {
                $query->where('name', 'Laravel');
            })
            ->cachedPaginate(
                perPage: 15,
                cacheTtl: 3600
            );
        
        return $products;
    }

    /**
     * Example 6: Window Pagination
     * 
     * Uses ROW_NUMBER() window function to avoid OFFSET performance issues
     */
    public function windowPagination()
    {
        // Better performance than OFFSET for deep pagination
        $articles = Article::where('status', 'published')
            ->windowPaginate(
                perPage: 20,
                page: 100 // Fast even on page 100+
            );
        
        return $articles;
    }

    /**
     * Example 7: Parallel Pagination
     * 
     * For extremely large datasets, split into parallel queries
     */
    public function parallelPagination()
    {
        // Split query into 4 parallel queries
        $logs = LogEntry::where('level', 'error')
            ->parallelPaginate(
                perPage: 100,
                page: 1,
                parallelQueries: 4
            );
        
        return $logs;
    }

    /**
     * Example 8: Estimated Count
     * 
     * Get fast approximate count for large tables
     */
    public function estimatedCount()
    {
        // Get estimated count using EXPLAIN
        $estimatedTotal = Product::where('active', true)
            ->estimatedCount();
        
        echo "Estimated products: {$estimatedTotal}";
        
        // Much faster than actual count for large tables
        $actualCount = Product::where('active', true)->count(); // Slow
        $fastCount = Product::where('active', true)->estimatedCount(); // Fast
        
        return $estimatedTotal;
    }

    /**
     * Example 9: Pagination Performance Report
     * 
     * Compare different pagination methods
     */
    public function paginationPerformanceReport()
    {
        $report = Product::where('active', true)
            ->paginationPerformanceReport(perPage: 20, page: 1);
        
        /*
        Returns:
        [
            'total_time' => 0.245,
            'table' => 'products',
            'estimated_rows' => 1500000,
            'methods' => [
                'standard' => ['time' => 0.120, 'memory' => 4194304, 'count' => 1500000],
                'fast' => ['time' => 0.045, 'memory' => 2097152, 'count' => 20],
                'cursor' => ['time' => 0.038, 'memory' => 2097152, 'count' => 20],
            ],
            'recommendation' => 'For 1500000 rows: Use fast pagination or cursor pagination'
        ]
        */
        
        return $report;
    }

    /**
     * Example 10: Choosing the Right Pagination Method
     * 
     * Decision guide based on your use case
     */
    public function choosePaginationMethod()
    {
        // Use Case 1: Admin Dashboard (need total count, page numbers)
        $adminProducts = Product::paginate(20);
        
        // Use Case 2: Infinite Scroll (no total needed)
        $infiniteScroll = Product::fastPaginate(20);
        
        // Use Case 3: Large Dataset (> 1M rows)
        $largeDataset = Order::cursorPaginate(50);
        
        // Use Case 4: Real-time Feed (newest first)
        $activityFeed = Article::seekPaginate(
            limit: 20,
            orderColumn: 'created_at'
        );
        
        // Use Case 5: Deep Pagination (page 1000+)
        $deepPages = LogEntry::windowPaginate(
            perPage: 50,
            page: 1000
        );
        
        // Use Case 6: Expensive Query with Count
        $expensiveQuery = Product::with(['category', 'brand', 'reviews'])
            ->whereHas('reviews', function ($query) {
                $query->where('rating', '>=', 4);
            })
            ->cachedPaginate(20, cacheTtl: 600);
        
        return $activityFeed;
    }

    /**
     * Example 11: Controller Implementation - Infinite Scroll
     */
    public function infiniteScrollController()
    {
        // API endpoint for infinite scroll
        $products = Product::where('active', true)
            ->orderBy('created_at', 'desc')
            ->cursorPaginate(20);
        
        return response()->json([
            'data' => $products->items(),
            'next_cursor' => $products->nextCursor()?->encode(),
            'has_more_pages' => $products->hasMorePages(),
        ]);
    }

    /**
     * Example 12: Controller Implementation - Traditional Pagination
     */
    public function traditionalPaginationController()
    {
        // Standard pagination with caching for performance
        $products = Product::where('active', true)
            ->with(['category', 'brand'])
            ->when(request('category_id'), function ($query, $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when(request('search'), function ($query, $search) {
                $query->search($search);
            })
            ->cachedPaginate(
                perPage: request('per_page', 20),
                cacheTtl: 300
            );
        
        return view('products.index', compact('products'));
    }

    /**
     * Example 13: API Response - Cursor Pagination
     */
    public function apiCursorPagination()
    {
        $articles = Article::where('status', 'published')
            ->orderBy('published_at', 'desc')
            ->cursorPaginate(25);
        
        return response()->json([
            'data' => $articles->items(),
            'meta' => [
                'per_page' => $articles->perPage(),
                'next_cursor' => $articles->nextCursor()?->encode(),
                'prev_cursor' => $articles->previousCursor()?->encode(),
                'has_more_pages' => $articles->hasMorePages(),
            ],
        ]);
    }

    /**
     * Example 14: Combining with Other Traits
     */
    public function combineWithOtherTraits()
    {
        // Combine with Searchable and Cacheable traits
        $products = Product::search('laptop')
            ->filter([
                'price:>=' => 500,
                'rating:>=' => 4.0,
            ])
            ->cursorPaginate(20);
        
        // Cached pagination with search
        $articles = Article::advancedSearch('Laravel AND framework')
            ->where('status', 'published')
            ->cachedPaginate(15, cacheTtl: 600);
        
        // Fast pagination with filters
        $orders = Order::where('status', 'completed')
            ->whereBetween('created_at', [now()->subMonth(), now()])
            ->fastPaginate(30);
        
        return $products;
    }

    /**
     * Example 15: Large Dataset Best Practices
     */
    public function largeDatasetBestPractices()
    {
        // ✅ DO: Use cursor pagination for large datasets
        $goodPagination = LogEntry::orderBy('id')
            ->cursorPaginate(100);
        
        // ❌ DON'T: Use offset pagination on page 10000
        // $badPagination = LogEntry::paginate(100)->page(10000);
        
        // ✅ DO: Use seek pagination for time-based data
        $timeSeries = LogEntry::seekPaginate(
            limit: 100,
            lastId: request('last_timestamp'),
            orderColumn: 'created_at'
        );
        
        // ✅ DO: Cache expensive counts
        $expensiveCount = Product::with(['category', 'brand', 'reviews'])
            ->whereHas('reviews')
            ->cachedPaginate(20, cacheTtl: 3600);
        
        // ✅ DO: Use approximate count for analytics
        $analytics = Order::where('status', 'completed')
            ->optimizedPaginate(50, useApproximateCount: true);
        
        return $goodPagination;
    }
}

/**
 * MODEL SETUP EXAMPLE
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Litepie\Database\Traits\Paginatable;
use Litepie\Database\Traits\Searchable;
use Litepie\Database\Traits\Cacheable;

class Product extends Model
{
    use Paginatable, Searchable, Cacheable;

    /**
     * Optional: Configure pagination behavior
     */
    protected array $paginatableConfig = [
        'cache_ttl' => 300,
        'use_approximate_count' => true,
        'approximate_count_threshold' => 1000000,
        'cursor_pagination_default' => false,
    ];

    protected array $searchable = [
        'name',
        'description',
        'sku',
    ];
}

class LogEntry extends Model
{
    use Paginatable;

    /**
     * For very large log tables, always use cursor pagination
     */
    protected array $paginatableConfig = [
        'cursor_pagination_default' => true,
        'use_approximate_count' => true,
    ];
}

/**
 * CONTROLLER EXAMPLES
 */

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Article;
use App\Models\Order;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Infinite scroll API endpoint
     */
    public function infiniteScroll(Request $request)
    {
        $products = Product::where('active', true)
            ->when($request->category_id, function ($query, $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->orderBy('created_at', 'desc')
            ->cursorPaginate(24);
        
        return response()->json([
            'data' => $products->items(),
            'next_cursor' => $products->nextCursor()?->encode(),
            'has_more' => $products->hasMorePages(),
        ]);
    }

    /**
     * Traditional paginated listing
     */
    public function index(Request $request)
    {
        $products = Product::where('active', true)
            ->with(['category', 'brand'])
            ->cachedPaginate($request->input('per_page', 20));
        
        return view('products.index', compact('products'));
    }

    /**
     * Large dataset with optimized pagination
     */
    public function largeDataset(Request $request)
    {
        $orders = Order::where('status', 'completed')
            ->optimizedPaginate($request->input('per_page', 50));
        
        return view('orders.index', compact('orders'));
    }
}

class FeedController extends Controller
{
    /**
     * Activity feed with seek pagination
     */
    public function activityFeed(Request $request)
    {
        $lastId = $request->input('last_id');
        
        $activities = Article::where('status', 'published')
            ->seekPaginate(
                limit: 20,
                lastId: $lastId,
                direction: 'next',
                orderColumn: 'published_at'
            );
        
        return response()->json([
            'data' => $activities,
            'last_id' => $activities->last()?->published_at,
            'has_more' => $activities->count() === 20,
        ]);
    }
}

/**
 * PERFORMANCE COMPARISON
 */

/*
┌─────────────────────────┬──────────────┬─────────────┬──────────────────┐
│ Pagination Method       │ Dataset Size │ Page Depth  │ Performance      │
├─────────────────────────┼──────────────┼─────────────┼──────────────────┤
│ Standard (paginate)     │ < 100K       │ < 100       │ ⭐⭐⭐⭐⭐        │
│                         │ > 1M         │ Any         │ ⭐               │
│                         │ Any          │ > 1000      │ ⭐               │
├─────────────────────────┼──────────────┼─────────────┼──────────────────┤
│ Fast (fastPaginate)     │ Any          │ < 100       │ ⭐⭐⭐⭐⭐        │
│                         │ > 1M         │ Any         │ ⭐⭐⭐           │
├─────────────────────────┼──────────────┼─────────────┼──────────────────┤
│ Cursor (cursorPaginate) │ > 100K       │ Any         │ ⭐⭐⭐⭐⭐        │
│                         │ > 10M        │ Any         │ ⭐⭐⭐⭐⭐        │
├─────────────────────────┼──────────────┼─────────────┼──────────────────┤
│ Seek (seekPaginate)     │ Any          │ N/A         │ ⭐⭐⭐⭐⭐        │
│                         │ Real-time    │ N/A         │ ⭐⭐⭐⭐⭐        │
├─────────────────────────┼──────────────┼─────────────┼──────────────────┤
│ Window (windowPaginate) │ Any          │ > 1000      │ ⭐⭐⭐⭐         │
├─────────────────────────┼──────────────┼─────────────┼──────────────────┤
│ Cached (cachedPaginate) │ Any          │ Any         │ ⭐⭐⭐⭐⭐        │
│                         │ Heavy query  │ Any         │ ⭐⭐⭐⭐⭐        │
└─────────────────────────┴──────────────┴─────────────┴──────────────────┘
*/

/**
 * DECISION TREE
 */

/*
┌─────────────────────────────────────────────────────────────┐
│ Which Pagination Method Should I Use?                       │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ Do you need total count and page numbers?                  │
│   ├─ YES → Do you have > 1M rows?                          │
│   │         ├─ YES → Use optimizedPaginate() or            │
│   │         │        cachedPaginate()                      │
│   │         └─ NO  → Use standard paginate()               │
│   │                                                         │
│   └─ NO  → Do you need infinite scroll?                    │
│             ├─ YES → Use cursorPaginate() or fastPaginate()│
│             │                                               │
│             └─ NO  → Is this a real-time feed?             │
│                      ├─ YES → Use seekPaginate()           │
│                      └─ NO  → Use fastPaginate()           │
│                                                             │
│ Are you on page > 1000?                                    │
│   └─ YES → Use windowPaginate() or cursorPaginate()        │
│                                                             │
│ Is the count query expensive (> 1 second)?                 │
│   └─ YES → Use cachedPaginate()                            │
│                                                             │
└─────────────────────────────────────────────────────────────┘
*/

/**
 * FRONTEND INTEGRATION EXAMPLES
 */

/*
// VUE.JS - Cursor Pagination
<template>
  <div>
    <div v-for="product in products" :key="product.id">
      {{ product.name }}
    </div>
    
    <button @click="loadMore" v-if="hasMore">Load More</button>
  </div>
</template>

<script>
export default {
  data() {
    return {
      products: [],
      nextCursor: null,
      hasMore: true
    }
  },
  methods: {
    async loadMore() {
      const response = await axios.get('/api/products', {
        params: { cursor: this.nextCursor }
      })
      
      this.products.push(...response.data.data)
      this.nextCursor = response.data.next_cursor
      this.hasMore = response.data.has_more
    }
  },
  mounted() {
    this.loadMore()
  }
}
</script>

// REACT - Infinite Scroll
import { useState, useEffect } from 'react'
import InfiniteScroll from 'react-infinite-scroll-component'

function ProductList() {
  const [products, setProducts] = useState([])
  const [cursor, setCursor] = useState(null)
  const [hasMore, setHasMore] = useState(true)

  const fetchProducts = async () => {
    const response = await fetch(`/api/products?cursor=${cursor}`)
    const data = await response.json()
    
    setProducts([...products, ...data.data])
    setCursor(data.next_cursor)
    setHasMore(data.has_more)
  }

  return (
    <InfiniteScroll
      dataLength={products.length}
      next={fetchProducts}
      hasMore={hasMore}
      loader={<h4>Loading...</h4>}
    >
      {products.map(product => (
        <div key={product.id}>{product.name}</div>
      ))}
    </InfiniteScroll>
  )
}

// JAVASCRIPT - Seek Pagination (Activity Feed)
let lastId = null

async function loadMoreActivities() {
  const response = await fetch(`/api/feed?last_id=${lastId}`)
  const data = await response.json()
  
  appendActivities(data.data)
  lastId = data.last_id
  
  if (!data.has_more) {
    hideLoadMoreButton()
  }
}
*/

/**
 * DATABASE OPTIMIZATION TIPS
 */

/*
-- Add indexes for cursor pagination
CREATE INDEX idx_products_id ON products(id);
CREATE INDEX idx_articles_created_at_id ON articles(created_at, id);

-- Add indexes for seek pagination
CREATE INDEX idx_articles_published_at ON articles(published_at);
CREATE INDEX idx_logs_created_at ON logs(created_at);

-- Composite indexes for filtered pagination
CREATE INDEX idx_products_active_created_at ON products(active, created_at);
CREATE INDEX idx_orders_status_user_id ON orders(status, user_id);

-- Covering indexes for better performance
CREATE INDEX idx_products_covering ON products(id, name, price, active);
*/
