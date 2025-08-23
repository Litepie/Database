# Advanced Usage Examples

This document provides comprehensive examples of using the Litepie Database package features.

## Table of Contents

1. [Archivable Examples](#archivable-examples)
2. [Searchable Examples](#searchable-examples)
3. [Cacheable Examples](#cacheable-examples)
4. [Sluggable Examples](#sluggable-examples)
5. [Sortable Examples](#sortable-examples)
6. [Casts Examples](#casts-examples)
7. [Model Macros Examples](#model-macros-examples)
8. [Advanced Queries](#advanced-queries)

## Archivable Examples

### Basic Usage

```php
<?php

use App\Models\Post;

// Archive a single post
$post = Post::find(1);
$post->archive('Content is outdated', auth()->user());

// Archive multiple posts
Post::whereIn('id', [1, 2, 3])->get()->each(function ($post) {
    $post->archive('Bulk cleanup', auth()->user());
});

// Or use the bulk method
Post::archiveByIds([1, 2, 3], 'Bulk cleanup', auth()->user());
```

### Querying Archived Records

```php
// Get only archived posts
$archived = Post::onlyArchived()->get();

// Get all posts including archived
$all = Post::withArchived()->get();

// Get posts archived in the last 30 days
$recent = Post::recentlyArchived(30)->get();

// Get posts archived between dates
$range = Post::archivedBetween(['2023-01-01', '2023-12-31'])->get();

// Get posts archived by specific user
$byUser = Post::onlyArchived()
    ->where('archived_by', auth()->id())
    ->get();
```

### Archive Events

```php
<?php

namespace App\Models;

use Litepie\Database\Traits\Archivable;

class Post extends Model
{
    use Archivable;

    protected static function booted()
    {
        // Before archiving
        static::archiving(function ($post) {
            // Send notification
            Notification::send($post->author, new PostArchivingNotification($post));
        });

        // After archiving
        static::archived(function ($post) {
            // Log the action
            Log::info("Post {$post->id} archived", [
                'reason' => $post->getArchiveReason(),
                'by' => $post->getArchivedBy(),
            ]);
        });
    }
}
```

## Searchable Examples

### Basic Search

```php
<?php

use App\Models\Article;

// Simple search across all searchable fields
$articles = Article::search('Laravel framework')->get();

// Search specific fields
$articles = Article::search('PHP', ['title', 'content'])->get();

// Search with pagination
$articles = Article::search('database')
    ->with('author')
    ->paginate(15);
```

### Advanced Search Strategies

```php
// Full-text search (requires FULLTEXT indexes)
$articles = Article::fullTextSearch('Laravel framework')->get();

// Weighted search with relevance scoring
$articles = Article::weightedSearch('Laravel')
    ->orderByDesc('search_relevance')
    ->limit(10)
    ->get();

// Boolean search
$articles = Article::booleanSearch('+Laravel -WordPress')->get();

// Fuzzy search (finds similar words)
$articles = Article::fuzzySearch('Laravle', threshold: 2)->get(); // Finds "Laravel"

// Advanced search with operators
$articles = Article::advancedSearch('Laravel AND (framework OR PHP)')->get();
```

### Search Configuration

```php
<?php

namespace App\Models;

use Litepie\Database\Traits\Searchable;

class Article extends Model
{
    use Searchable;

    protected array $searchable = [
        'title',
        'content',
        'excerpt',
        'author.name',        // Search in relationship
        'category.name',      // Search in category name
        'tags.name',         // Search in tag names
    ];

    protected array $fullTextSearchable = [
        'title',
        'content',
    ];

    protected array $searchWeights = [
        'title' => 10,       // Title has highest weight
        'excerpt' => 8,
        'content' => 5,
        'author.name' => 3,
        'category.name' => 2,
        'tags.name' => 1,
    ];
}
```

## Cacheable Examples

### Basic Caching

```php
<?php

use App\Models\Product;

// Cache query results for 60 minutes
$products = Product::where('featured', true)
    ->cacheFor(60)
    ->get();

// Cache with custom key
$popular = Product::where('views', '>', 1000)
    ->cacheFor(120, 'popular-products')
    ->get();

// Cache forever (until manually cleared)
$categories = Category::cacheForever('all-categories');
```

### Advanced Caching

```php
// Cache with tags for easy invalidation
$products = Product::where('category_id', 1)
    ->cacheWithTags(['products', 'category-1'], 60)
    ->get();

// Smart caching with dependencies
$products = Product::with('category', 'reviews')
    ->smartCache(60, ['categories', 'reviews'])
    ->get();

// Cache paginated results
$products = Product::active()
    ->cachePaginate(15, 30); // 15 per page, cache for 30 minutes

// Cache on specific store
$products = Product::featured()
    ->cacheOnStore('redis', 60)
    ->get();
```

### Cache Management

```php
// Clear all cache for a model
Product::clearModelCache();

// Clear specific cache entry
$product = Product::find(1);
$product->clearInstanceCache();

// Warm up cache
Product::warmUpCache([
    [
        'query' => Product::featured(),
        'key' => 'featured-products',
        'ttl' => 60,
    ],
    [
        'query' => Product::popular(),
        'key' => 'popular-products', 
        'ttl' => 120,
    ],
]);

// Get cache statistics
$stats = Product::find(1)->getCacheStats();
```

## Sluggable Examples

### Basic Slug Generation

```php
<?php

namespace App\Models;

use Litepie\Database\Traits\Sluggable;

class Post extends Model
{
    use Sluggable;

    protected array $slugs = [
        'slug' => 'title',                    // Single source
        'meta_slug' => ['title', 'category.name'], // Multiple sources
    ];

    protected array $slugConfig = [
        'separator' => '-',
        'max_length' => 255,
        'unique' => true,
        'on_update' => false,
        'ascii_only' => true,
    ];
}

// Usage
$post = Post::create([
    'title' => 'My Amazing Blog Post!',
    // slug will be automatically set to 'my-amazing-blog-post'
]);
```

### Finding by Slug

```php
// Find by slug
$post = Post::findBySlug('my-amazing-blog-post');

// Find by slug or fail
$post = Post::findBySlugOrFail('my-amazing-blog-post');

// Query by slug
$posts = Post::whereSlug('my-amazing-blog-post')->get();

// Route model binding
Route::get('/posts/{post:slug}', [PostController::class, 'show']);
```

### Advanced Slug Features

```php
// Regenerate slug
$post->regenerateSlugs();

// Custom configuration per instance
$post->setSlugConfig([
    'separator' => '_',
    'max_length' => 100,
    'on_update' => true,
]);

// Get slug variations
$variations = $post->getSlugVariations('slug', 'my-post', 10);
// Returns: ['my-post', 'my-post-2', 'my-post-3', ...]
```

## Sortable Examples

### Basic Sorting

```php
<?php

use App\Models\Product;

// Sort by single field
$products = Product::sortBy('name')->get();
$products = Product::sortBy('price', 'desc')->get();

// Sort by multiple fields
$products = Product::sortByMultiple([
    'category_id' => 'asc',
    'created_at' => 'desc',
])->get();

// Sort by position
$products = Product::sortByPosition()->get();

// Sort by popularity
$products = Product::sortByPopularity('views')->get();

// Sort by relationship count
$products = Product::sortByCount('reviews', 'desc')->get();

// Random sorting
$products = Product::sortRandom()->limit(10)->get();
```

### Custom Sorting

```php
// Custom order
$products = Product::sortByCustomOrder('status', [
    'featured', 'active', 'inactive', 'draft'
])->get();

// Sort from request parameters
$products = Product::sortFromRequest(request()->all())->get();
// Supports: ?sort_by=name&sort_direction=desc
```

### Position Management

```php
$product = Product::find(1);

// Move to specific position
$product->moveToPosition(5);

// Move up/down
$product->moveUp();
$product->moveDown();

// Move to top/bottom
$product->moveToTop();
$product->moveToBottom();

// Reorder all products
Product::reorder();
```

## Casts Examples

### JSON Cast with Schema

```php
<?php

namespace App\Models;

use Litepie\Database\Casts\JsonCast;

class User extends Model
{
    protected $casts = [
        'preferences' => JsonCast::withSchema([
            'theme' => ['type' => 'string', 'required' => true],
            'language' => ['type' => 'string', 'required' => true],
            'notifications' => ['type' => 'boolean', 'required' => false],
        ]),
        'metadata' => JsonCast::withDefault(['created_via' => 'web']),
        'config' => JsonCast::asObject(), // Returns object instead of array
    ];
}

// Usage
$user = new User();
$user->preferences = [
    'theme' => 'dark',
    'language' => 'en',
    'notifications' => true,
];

// Access
echo $user->preferences['theme']; // 'dark'
```

### Money Cast

```php
<?php

namespace App\Models;

use Litepie\Database\Casts\MoneyCast;

class Order extends Model
{
    protected $casts = [
        'total' => MoneyCast::class,                    // Default USD
        'tax' => MoneyCast::currency('USD'),            // Specific currency
        'shipping' => MoneyCast::asDecimal('EUR', 2),   // Store as decimal
        'crypto_amount' => MoneyCast::crypto('BTC'),    // Cryptocurrency
    ];
}

// Usage
$order = new Order();
$order->total = 99.99;
// or
$order->total = ['amount' => 99.99, 'currency' => 'USD'];

// Access
echo $order->total['amount'];     // 99.99
echo $order->total['formatted'];  // $99.99
echo $order->total['cents'];      // 9999
echo $order->total['currency'];   // USD
```

## Model Macros Examples

### Adding Macros

```php
<?php

use Litepie\Database\Facades\ModelMacro;
use App\Models\User;
use App\Models\Post;

// Add macro to specific models
ModelMacro::addMacro([User::class, Post::class], 'popular', function () {
    return $this->where('views', '>', 1000);
});

// Add global macro (all models)
ModelMacro::addGlobalMacro('recent', function (int $days = 30) {
    return $this->where('created_at', '>=', now()->subDays($days));
});

// Complex macro with parameters
ModelMacro::addMacro(Post::class, 'withAuthorAndCategory', function () {
    return $this->with(['author', 'category'])
                ->select(['id', 'title', 'author_id', 'category_id']);
});
```

### Using Macros

```php
// Use model-specific macro
$popularUsers = User::popular()->get();
$popularPosts = Post::popular()->get();

// Use global macro
$recentUsers = User::recent(7)->get();
$recentPosts = Post::recent(14)->get();

// Chain with other methods
$posts = Post::popular()
    ->withAuthorAndCategory()
    ->recent(30)
    ->paginate(15);
```

### Macro Management

```php
// Check if macro exists
if (ModelMacro::modelHasMacro(User::class, 'popular')) {
    // Macro exists
}

// Get models that implement a macro
$models = ModelMacro::modelsThatImplement('popular');

// Get all macros for a model
$macros = ModelMacro::macrosForModel(User::class);

// Remove macro
ModelMacro::removeMacro(User::class, 'popular');

// Get statistics
$stats = ModelMacro::getStatistics();
```

## Advanced Queries

### Complex Filtering

```php
use App\Models\Product;

// Advanced filtering with operators
$products = Product::filter([
    'status' => 'active',           // Exact match
    'price:>' => 100,               // Greater than
    'created_at:between' => ['2023-01-01', '2023-12-31'],
    'category_id' => [1, 2, 3],     // In array
    'brand.name:like' => '%nike%',  // Relationship with LIKE
])->get();

// Combine multiple features
$products = Product::search('laptop')
    ->filter(['status' => 'active'])
    ->sortBy('price', 'desc')
    ->cacheFor(60)
    ->paginate(15);
```

### Batch Processing

```php
// Process large datasets efficiently
User::where('active', false)
    ->batch(100, function ($users) {
        foreach ($users as $user) {
            $user->sendDeactivationEmail();
        }
    });

// Bulk operations with progress tracking
$processed = 0;
Product::chunk(500, function ($products) use (&$processed) {
    foreach ($products as $product) {
        $product->updateSearchIndex();
        $processed++;
        
        if ($processed % 100 === 0) {
            Log::info("Processed {$processed} products");
        }
    }
});
```

### Combining All Features

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Litepie\Database\Traits\{
    Archivable, Cacheable, Searchable, Sluggable, Sortable
};

class Article extends Model
{
    use Archivable, Cacheable, Searchable, Sluggable, Sortable;

    // Configuration for all traits...

    /**
     * Get trending articles with full feature utilization.
     */
    public static function getTrendingArticles($limit = 10)
    {
        return static::search(request('q'))              // Search
            ->filter(request()->only(['category', 'status'])) // Filter
            ->sortFromRequest(request()->all())          // Sort
            ->with(['author', 'category'])               // Eager load
            ->cacheWithTags(['articles', 'trending'], 30) // Cache
            ->paginate($limit);                          // Paginate
    }

    /**
     * Archive old unpopular articles.
     */
    public static function archiveUnpopularArticles()
    {
        return static::where('views', '<', 100)
            ->where('created_at', '<', now()->subMonths(6))
            ->get()
            ->each(function ($article) {
                $article->archive('Low engagement', 'system');
            });
    }
}

// Usage in controller
class ArticleController extends Controller
{
    public function index()
    {
        $articles = Article::getTrendingArticles(15);
        return view('articles.index', compact('articles'));
    }
    
    public function cleanup()
    {
        $archived = Article::archiveUnpopularArticles();
        return response()->json(['archived' => $archived->count()]);
    }
}
```

This demonstrates the power of combining multiple traits and features for sophisticated data management in your Laravel applications.
