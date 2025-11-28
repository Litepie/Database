# Paginatable Trait

The `Paginatable` trait provides advanced pagination methods optimized for handling large datasets efficiently with various pagination strategies.

## Table of Contents

- [Installation](#installation)
- [Quick Start](#quick-start)
- [Pagination Methods](#pagination-methods)
  - [Cursor Pagination](#cursor-pagination)
  - [Fast Pagination](#fast-pagination)
  - [Seek Pagination](#seek-pagination)
  - [Optimized Pagination](#optimized-pagination)
  - [Cached Pagination](#cached-pagination)
  - [Window Pagination](#window-pagination)
  - [Parallel Pagination](#parallel-pagination)
- [Performance Utilities](#performance-utilities)
- [Best Practices](#best-practices)
- [Database Optimization](#database-optimization)

---

## Installation

Add the trait to your Eloquent model:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Litepie\Database\Traits\Paginatable;

class Product extends Model
{
    use Paginatable;
}
```

### Optional Configuration

```php
class Product extends Model
{
    use Paginatable;

    protected array $paginatableConfig = [
        'cache_ttl' => 300,                      // 5 minutes
        'use_approximate_count' => true,          // Use approximate count for large tables
        'approximate_count_threshold' => 1000000, // 1 million rows threshold
        'cursor_pagination_default' => false,     // Use cursor by default
    ];
}
```

---

## Quick Start

```php
// Cursor pagination (best for large datasets)
$products = Product::cursorPaginate(50);

// Fast pagination (no total count)
$products = Product::fastPaginate(20);

// Cached pagination (cache total count)
$products = Product::cachedPaginate(20, cacheTtl: 300);

// Seek pagination (real-time feeds)
$articles = Article::seekPaginate(limit: 20, lastId: 100);
```

---

## Pagination Methods

### Cursor Pagination

**Best for:** Large datasets, infinite scroll, real-time data

Cursor pagination is much faster than offset-based pagination for large datasets because it doesn't use OFFSET.

```php
// Basic cursor pagination
$products = Product::where('active', true)
    ->cursorPaginate(50);

// With custom cursor name
$products = Product::cursorPaginate(
    perPage: 50,
    columns: ['*'],
    cursorName: 'cursor',
    cursor: request()->input('cursor')
);

// API Response
return response()->json([
    'data' => $products->items(),
    'next_cursor' => $products->nextCursor()?->encode(),
    'prev_cursor' => $products->previousCursor()?->encode(),
    'has_more' => $products->hasMorePages(),
]);
```

**URL Structure:**
```
/api/products?cursor=eyJpZCI6MTAwLCJfcG9pbnRzVG9OZXh0SXRlbXMiOnRydWV9
```

**Pros:**
- ✅ Very fast for large datasets
- ✅ Consistent performance regardless of page depth
- ✅ Works well with infinite scroll
- ✅ Stateless

**Cons:**
- ❌ No page numbers
- ❌ Can't jump to specific page
- ❌ Requires ordering column

---

### Fast Pagination

**Best for:** Infinite scroll, "Load More" buttons, don't need total count

Fast pagination uses `simplePaginate` which doesn't count total records (uses LIMIT + 1).

```php
// Basic fast pagination
$products = Product::where('active', true)
    ->fastPaginate(20);

// With custom page name
$products = Product::fastPaginate(
    perPage: 20,
    columns: ['*'],
    pageName: 'page'
);

// Check if more pages exist
if ($products->hasMorePages()) {
    // Show "Load More" button
}
```

**Pros:**
- ✅ Faster than standard pagination
- ✅ No COUNT(*) query
- ✅ Simple to use
- ✅ Good for "Load More" pattern

**Cons:**
- ❌ No total count
- ❌ No last page info
- ❌ Slower than cursor for very large datasets

---

### Seek Pagination

**Best for:** Real-time feeds, activity streams, social media-style pagination

Seek pagination is perfect for time-based or ID-based feeds where you want to load newer or older items.

```php
// Get first page (most recent)
$articles = Article::where('status', 'published')
    ->seekPaginate(limit: 20);

// Get next page after ID 100
$articles = Article::seekPaginate(
    limit: 20,
    lastId: 100,
    direction: 'next'
);

// Get previous page
$articles = Article::seekPaginate(
    limit: 20,
    lastId: 100,
    direction: 'prev'
);

// Custom order column (timestamp)
$articles = Article::seekPaginate(
    limit: 20,
    lastId: '2024-01-01 12:00:00',
    direction: 'next',
    orderColumn: 'published_at'
);
```

**Use Cases:**
- Social media feeds (Twitter, Facebook)
- Activity logs
- Chat messages
- Real-time notifications
- Live streams

**Pros:**
- ✅ Fastest pagination method
- ✅ Perfect for real-time feeds
- ✅ Simple WHERE clause
- ✅ No OFFSET overhead

**Cons:**
- ❌ Can't jump to specific page
- ❌ No page numbers
- ❌ Requires sortable column

---

### Optimized Pagination

**Best for:** Very large tables (> 1M rows), need total count

Uses approximate count from database statistics for tables larger than 1 million rows.

```php
// Automatic - uses approximate count for large tables
$orders = Order::where('status', 'completed')
    ->optimizedPaginate(50);

// Force approximate count
$orders = Order::optimizedPaginate(
    perPage: 50,
    useApproximateCount: true
);

// Disable approximate count (exact count)
$orders = Order::optimizedPaginate(
    perPage: 50,
    useApproximateCount: false
);
```

**How it works:**
1. Checks table size using `information_schema` (MySQL) or `pg_stat_user_tables` (PostgreSQL)
2. If rows > 1M, uses `EXPLAIN` to get approximate count
3. Otherwise uses regular `COUNT(*)`

**Pros:**
- ✅ Much faster for large tables
- ✅ Still provides page numbers
- ✅ Good enough accuracy for most cases
- ✅ Automatic optimization

**Cons:**
- ❌ Count is approximate (not exact)
- ❌ May show wrong last page number
- ❌ Database-specific

---

### Cached Pagination

**Best for:** Expensive queries with joins/aggregations, high-traffic pages

Caches the total count to avoid expensive COUNT queries on every request.

```php
// Cache for 5 minutes (300 seconds)
$products = Product::with(['category', 'brand'])
    ->where('active', true)
    ->cachedPaginate(
        perPage: 20,
        cacheTtl: 300
    );

// Cache for 1 hour
$products = Product::cachedPaginate(
    perPage: 20,
    columns: ['*'],
    pageName: 'page',
    page: null,
    cacheTtl: 3600
);

// Custom cache key prefix
// Cache key format: pagination_{md5(sql+bindings)}_count
```

**When to use:**
- Heavy JOINs
- Complex WHERE clauses
- Aggregations
- High-traffic pages
- Rarely changing data

**Cache Invalidation:**
```php
// Manual cache clear
Cache::tags(['pagination'])->flush();

// Or specific query
$cacheKey = 'pagination_' . md5($query->toSql() . serialize($query->getBindings())) . '_count';
Cache::forget($cacheKey);
```

**Pros:**
- ✅ Dramatically faster for complex queries
- ✅ Reduces database load
- ✅ Automatic cache key generation
- ✅ Configurable TTL

**Cons:**
- ❌ Count may be stale
- ❌ Requires cache storage
- ❌ Need cache invalidation strategy

---

### Window Pagination

**Best for:** Deep pagination (page 1000+), large datasets with page numbers

Uses SQL window functions (ROW_NUMBER) to avoid slow OFFSET.

```php
// Fast even on page 1000
$logs = LogEntry::windowPaginate(
    perPage: 50,
    page: 1000
);

// With specific columns
$logs = LogEntry::windowPaginate(
    perPage: 50,
    page: 100,
    columns: ['id', 'level', 'message', 'created_at']
);
```

**Performance Comparison:**
```
Standard OFFSET (page 10000):  ~5000ms
Window Function (page 10000):  ~50ms
```

**Pros:**
- ✅ Fast for deep pagination
- ✅ Supports page numbers
- ✅ Much better than OFFSET
- ✅ Consistent performance

**Cons:**
- ❌ Database-specific (MySQL 8+, PostgreSQL)
- ❌ More complex query
- ❌ Still slower than cursor for sequential access

---

### Parallel Pagination

**Best for:** Extremely large datasets, data processing, batch operations

Splits query into multiple parallel queries for faster execution.

```php
// Split into 4 parallel queries
$logs = LogEntry::where('level', 'error')
    ->parallelPaginate(
        perPage: 100,
        page: 1,
        parallelQueries: 4
    );

// Each parallel query fetches 25 records (100 / 4)
```

**Warning:** This is a simplified implementation. For true parallel execution, use Laravel's queue system or async processing.

**Pros:**
- ✅ Can speed up large result sets
- ✅ Distributes load
- ✅ Good for batch processing

**Cons:**
- ❌ Complexity overhead
- ❌ Not true parallelism (sequential in PHP)
- ❌ Better solutions exist (queues)

---

## Performance Utilities

### Estimated Count

Get fast approximate count using EXPLAIN:

```php
// Much faster than COUNT(*) on large tables
$count = Product::where('active', true)->estimatedCount();

// Comparison
$exact = Product::where('active', true)->count();     // 2.5 seconds
$approx = Product::where('active', true)->estimatedCount(); // 0.005 seconds
```

### Pagination Performance Report

Compare all pagination methods:

```php
$report = Product::where('active', true)
    ->paginationPerformanceReport(perPage: 20, page: 1);

/*
Returns:
[
    'total_time' => 0.245,
    'table' => 'products',
    'estimated_rows' => 1500000,
    'methods' => [
        'standard' => [
            'time' => 0.120,
            'memory' => 4194304,
            'count' => 1500000
        ],
        'fast' => [
            'time' => 0.045,
            'memory' => 2097152,
            'count' => 20
        ],
        'cursor' => [
            'time' => 0.038,
            'memory' => 2097152,
            'count' => 20
        ],
    ],
    'recommendation' => 'For 1500000 rows: Use fast pagination or cursor pagination'
]
*/
```

---

## Best Practices

### Decision Tree

```
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
```

### Use Case Examples

#### Admin Dashboard
```php
// Need page numbers and total count
$products = Product::paginate(20);
```

#### Infinite Scroll
```php
// No total needed, fast
$products = Product::cursorPaginate(20);
```

#### Real-time Feed
```php
// Newest first, very fast
$activities = Activity::seekPaginate(
    limit: 20,
    lastId: request('last_id'),
    orderColumn: 'created_at'
);
```

#### Large Dataset (> 1M rows)
```php
// Approximate count, still fast
$orders = Order::optimizedPaginate(50);
```

#### Expensive Query
```php
// Cache the count
$products = Product::with(['category', 'brand', 'reviews'])
    ->whereHas('reviews', fn($q) => $q->where('rating', '>=', 4))
    ->cachedPaginate(20, cacheTtl: 600);
```

#### Deep Pagination
```php
// Page 1000+
$logs = LogEntry::windowPaginate(50, page: 1000);
```

---

## Database Optimization

### Indexes for Cursor Pagination

```sql
-- Add index on ID (usually already exists)
CREATE INDEX idx_products_id ON products(id);

-- Composite index for ordered pagination
CREATE INDEX idx_articles_created_at_id ON articles(created_at DESC, id DESC);
```

### Indexes for Seek Pagination

```sql
-- Index on timestamp column
CREATE INDEX idx_articles_published_at ON articles(published_at);

-- Composite index for filtered seek
CREATE INDEX idx_logs_level_created_at ON logs(level, created_at);
```

### Indexes for Filtered Pagination

```sql
-- Composite index for common filters
CREATE INDEX idx_products_active_created_at ON products(active, created_at);
CREATE INDEX idx_orders_status_user_id ON orders(status, user_id);
```

### Covering Indexes

```sql
-- Include commonly selected columns
CREATE INDEX idx_products_covering ON products(id, name, price, active, created_at);
```

### Full-Text Indexes (MySQL)

```sql
-- For full-text search with pagination
CREATE FULLTEXT INDEX idx_articles_fulltext ON articles(title, content);
```

---

## Performance Comparison

| Method | Dataset | Page | Speed | Memory | Use Case |
|--------|---------|------|-------|--------|----------|
| **Standard** | < 100K | < 100 | ⭐⭐⭐⭐⭐ | Medium | Admin panels |
| **Standard** | > 1M | Any | ⭐ | High | ❌ Avoid |
| **Standard** | Any | > 1000 | ⭐ | High | ❌ Avoid |
| **Fast** | Any | < 100 | ⭐⭐⭐⭐⭐ | Low | Load More |
| **Fast** | > 1M | Any | ⭐⭐⭐ | Low | Infinite scroll |
| **Cursor** | > 100K | Any | ⭐⭐⭐⭐⭐ | Low | Large datasets |
| **Cursor** | > 10M | Any | ⭐⭐⭐⭐⭐ | Low | Very large |
| **Seek** | Any | N/A | ⭐⭐⭐⭐⭐ | Low | Feeds |
| **Optimized** | > 1M | Any | ⭐⭐⭐⭐ | Medium | Analytics |
| **Cached** | Any | Any | ⭐⭐⭐⭐⭐ | Low | Heavy queries |
| **Window** | Any | > 1000 | ⭐⭐⭐⭐ | Medium | Deep pages |

---

## Frontend Integration

### Vue.js - Cursor Pagination

```vue
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
  }
}
</script>
```

### React - Infinite Scroll

```jsx
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
```

---

## Troubleshooting

### Slow COUNT(*) queries

**Problem:** Count query takes > 1 second
**Solution:** Use `cachedPaginate()` or `optimizedPaginate()`

### Deep pagination slow (page 1000+)

**Problem:** OFFSET becomes slow on deep pages
**Solution:** Use `windowPaginate()` or switch to `cursorPaginate()`

### Need exact count on large table

**Problem:** `optimizedPaginate()` gives approximate count
**Solution:** Use `cachedPaginate()` with long TTL

### Cursor pagination not working

**Problem:** Missing ORDER BY clause
**Solution:** Trait auto-adds ORDER BY id if missing, but explicit ordering is better

```php
// ✅ Good - explicit ordering
Product::orderBy('created_at', 'desc')->cursorPaginate(20)

// ⚠️ Works but uses id - might not be desired
Product::cursorPaginate(20)
```

---

## Additional Resources

- [Comprehensive Examples](../examples/paginatable_example.php)
- [Laravel Pagination Docs](https://laravel.com/docs/pagination)
- [Database Performance Guide](https://use-the-index-luke.com/)

---

**Need Help?** Open an issue on [GitHub](https://github.com/litepie/database/issues)
