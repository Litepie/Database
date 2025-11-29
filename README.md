# Litepie Database Package

[![Latest Stable Version](https://poser.pugx.org/litepie/database/v/stable)](https://packagist.org/packages/litepie/database)
[![Total Downloads](https://poser.pugx.org/litepie/database/downloads)](https://packagist.org/packages/litepie/database)
[![License](https://poser.pugx.org/litepie/database/license)](https://packagist.org/packages/litepie/database)

An advanced Laravel database package that provides enhanced Eloquent traits, scopes, casts, and utilities for modern Laravel applications. This package extends Laravel's Eloquent ORM with powerful features for archiving, searching, caching, slugs, and more.

## Features

### Core Traits (14 Available)
- 📦 **Versionable**: Track model version history with rollback capability
- 🏷️ **Metable**: Flexible key-value metadata storage (WordPress-style)
- 🌍 **Translatable**: Multi-language content support with automatic locale handling
- 🔍 **Searchable**: Powerful search with multiple strategies (full-text, fuzzy, weighted, boolean)
- ⚡ **Cacheable**: Smart caching with tags, invalidation, and warm-up strategies
- 🔗 **Sluggable**: Advanced slug generation with multiple strategies
- 📄 **Paginatable**: Cursor, seek, window, and cached pagination for large datasets
- 📊 **Aggregatable**: Statistical analysis and reporting (23 analytics methods)
- 🗃️ **Archivable**: Soft archiving with reasons and user tracking
- 📤 **Exportable**: Export data to CSV, Excel, JSON formats
- 📥 **Importable**: Import and validate data from CSV
- 🔢 **Sortable**: Manual ordering and drag-drop support
- 📋 **Batchable**: Efficient bulk operations for large datasets
- 📏 **Measurable**: Query performance monitoring and optimization

### Additional Features
- 💰 **Money Handling**: Robust money casting with multi-currency support
- 📊 **JSON Enhancement**: Advanced JSON casting with schema validation
- 🔧 **Custom Macros**: Dynamic model macro system for extending Eloquent
- 🎯 **Smart Scopes**: Advanced query scopes for complex filtering

### 🤖 AI-Ready Package
This package is fully optimized for AI-assisted development! Use with GitHub Copilot, Cursor AI, ChatGPT, Claude, and other AI coding tools. See [AI Integration Guide](.ai/README.md) for details.

## Installation

Install the package via Composer:

```bash
composer require litepie/database
```

## Requirements

- PHP 8.2, 8.3, or 8.4
- Laravel 10.x, 11.x, or 12.x
- MySQL 5.7+, PostgreSQL 10+, or SQLite 3.8+

### Laravel Auto-Discovery

The package will automatically register its service provider and facades through Laravel's package auto-discovery feature.

### Manual Registration (Optional)

If you need to manually register the service provider, add it to your `config/app.php`:

```php
'providers' => [
    // ...
    Litepie\Database\DatabaseServiceProvider::class,
],

'aliases' => [
    // ...
    'ModelMacro' => Litepie\Database\Facades\ModelMacro::class,
],
```

### Publishing Configuration

Optionally, publish the configuration file:

```bash
php artisan vendor:publish --tag=litepie-database-config
```

## Quick Start for AI Tools

Ask your AI assistant natural questions like:

```
"How do I track changes to my Post model?"
"I need custom product attributes in Laravel"
"Show me how to add multi-language support"
"How do I create an analytics dashboard?"
```

Your AI will reference this package's comprehensive documentation in the `.ai/` directory to provide accurate, working code.

### Common Use Cases

#### CMS / Blog
```php
class Post extends Model {
    use Versionable, Translatable, Sluggable, Searchable, Cacheable;
    protected array $translatable = ['title', 'content'];
}
```

#### E-commerce Product
```php
class Product extends Model {
    use Translatable, Metable, Searchable, Cacheable, Sluggable;
    protected array $translatable = ['name', 'description'];
}
```

#### Analytics Dashboard
```php
class Order extends Model {
    use Aggregatable, Cacheable, Exportable;
}
```

#### User Preferences
```php
class User extends Model {
    use Metable, Versionable;
}
```

For complete examples and AI integration details, see:
- 📖 [AI Integration Guide](.ai/README.md)
- 🚀 [Quick Reference](.ai/quick-reference.md)
- 💻 [Code Examples](.ai/code-examples.md)
- 📝 [AI Prompts](.ai/ai-prompts.md)

## Usage

### 1. Version Control (Versionable Trait)

Track complete version history with rollback capability.

```php
use Litepie\Database\Traits\Versionable;

class Post extends Model
{
    use Versionable;
    
    protected int $maxVersions = 20;
    protected array $versionableExclude = ['views', 'updated_at'];
}

// Usage
$post->createVersion('Major update', auth()->user());
$post->rollbackToVersion(5);
$history = $post->getVersionHistory();
$comparison = $post->compareVersions(1, 5);
```

See [examples/versionable_example.php](examples/versionable_example.php) for 20 detailed examples.

### 2. Custom Metadata (Metable Trait)

WordPress-style flexible key-value storage.

```php
use Litepie\Database\Traits\Metable;

class Product extends Model
{
    use Metable;
}

// Usage
$product->setMeta('featured', true);
$product->setMeta('warranty_months', 24);
$featured = Product::whereMeta('featured', true)->get();
$product->incrementMeta('view_count');
```

See [examples/metable_example.php](examples/metable_example.php) for 20 detailed examples.

### 3. Multi-Language (Translatable Trait)

Support multiple languages with automatic locale handling.

```php
use Litepie\Database\Traits\Translatable;

class Post extends Model
{
    use Translatable;
    
    protected array $translatable = ['title', 'content'];
}

// Usage
$post->translate('es', ['title' => 'Título', 'content' => 'Contenido']);
$post->setLocale('es');
echo $post->title; // Returns Spanish translation
$completeness = $post->getTranslationCompleteness('es'); // 100%
```

See [examples/translatable_example.php](examples/translatable_example.php) for 20 detailed examples.

### 4. Analytics & Reporting (Aggregatable Trait)

23 powerful methods for statistics and reporting.

```php
use Litepie\Database\Traits\Aggregatable;

class Order extends Model
{
    use Aggregatable;
}

// Usage
$stats = Order::aggregate(['sum' => 'total', 'avg' => 'total']);
$trend = Order::trend('created_at', 'month', 'revenue', 'sum');
$growth = Order::growthRate('total', 'month', 6);
$yoy = Order::yearOverYear('sales', 'sum');
$comparison = Order::compareWithPreviousPeriod('total', 'sum', 'month');
```

See [examples/aggregatable_example.php](examples/aggregatable_example.php) for 31 detailed examples.

### 5. Archivable Trait

The enhanced Archivable trait provides soft archiving functionality with additional features like reason tracking and user auditing.

#### Basic Usage

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Litepie\Database\Traits\Archivable;

class Post extends Model
{
    use Archivable;

    // Optional: Define custom column names
    const ARCHIVED_AT = 'archived_at';
    const ARCHIVED_BY = 'archived_by'; 
    const ARCHIVED_REASON = 'archived_reason';
}
```

#### Migration

Add archive columns to your table:

```php
Schema::table('posts', function (Blueprint $table) {
    $table->archivedAt(); // Adds archived_at timestamp
    $table->string('archived_by')->nullable();
    $table->text('archived_reason')->nullable();
});
```

#### Examples

```php
// Archive a post with reason and user
$post = Post::find(1);
$post->archive('Content outdated', auth()->user());

// Archive multiple posts
Post::archiveByIds([1, 2, 3], 'Bulk cleanup', auth()->user());

// Query archived posts
$archivedPosts = Post::onlyArchived()->get();
$recentlyArchived = Post::recentlyArchived(30)->get(); // Last 30 days

// Restore from archive
$post->unArchive();

// Check if archived
if ($post->isArchived()) {
    echo "Post was archived on: " . $post->archived_at;
    echo "Reason: " . $post->getArchiveReason();
    echo "By: " . $post->getArchivedBy();
}
```

### 6. Advanced Searchable Trait

Powerful search capabilities with multiple strategies.

#### Basic Setup

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Litepie\Database\Traits\Searchable;

class Article extends Model
{
    use Searchable;

    protected array $searchable = ['title', 'content', 'tags'];
    protected array $fullTextSearchable = ['title', 'content'];
    protected array $searchWeights = [
        'title' => 10,
        'content' => 5,
        'tags' => 3,
    ];
}
```

#### Search Examples

```php
// Basic search
$articles = Article::search('Laravel framework')->get();

// Advanced search with operators
$articles = Article::advancedSearch('Laravel AND framework OR PHP')->get();

// Full-text search (MySQL FULLTEXT)
$articles = Article::fullTextSearch('Laravel framework')->get();

// Weighted search with relevance scoring
$articles = Article::weightedSearch('Laravel')
    ->orderByDesc('search_relevance')
    ->get();

// Fuzzy search (with Levenshtein distance)
$articles = Article::fuzzySearch('Laravle', threshold: 2)->get(); // Finds "Laravel"

// Boolean search
$articles = Article::booleanSearch('+Laravel -CodeIgniter')->get();

// Search in relationships
class Article extends Model
{
    use Searchable;
    
    protected array $searchable = ['title', 'author.name', 'category.name'];
}

$articles = Article::search('John Doe')->get(); // Searches in author name
```

### 7. Intelligent Caching Trait

Smart caching with automatic invalidation and warm-up strategies.

#### Setup

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Litepie\Database\Traits\Cacheable;

class Product extends Model
{
    use Cacheable;

    protected int $defaultCacheTtl = 120; // 2 hours
    protected array $cacheTags = ['products', 'catalog'];
    protected string $cachePrefix = 'product';
}
```

#### Caching Examples

```php
// Cache query results for 60 minutes
$products = Product::where('active', true)
    ->cacheFor(60)
    ->get();

// Cache with custom key
$featuredProducts = Product::where('featured', true)
    ->cacheFor(120, 'featured-products')
    ->get();

// Cache with tags for easy invalidation
$products = Product::where('category_id', 1)
    ->cacheWithTags(['category-1'], 60)
    ->get();

// Cache by ID
$product = Product::cacheById(123, 60);

// Cache forever (until manually cleared)
$categories = Category::cacheForever('all-categories');

// Smart cache with dependencies
$products = Product::with('category')
    ->smartCache(60, ['categories'])
    ->get();

// Cache paginated results
$products = Product::cachePaginate(15, 30);

// Clear cache
Product::find(1)->clearInstanceCache();
Product::clearModelCache(); // Clear all product cache

// Warm up cache
Product::warmUpCache([
    ['query' => Product::featured(), 'key' => 'featured', 'ttl' => 60],
    ['query' => Product::popular(), 'key' => 'popular', 'ttl' => 120],
]);
```

### 8. Enhanced Sluggable Trait

Advanced slug generation with multiple strategies and configurations.

#### Setup

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Litepie\Database\Traits\Sluggable;

class Post extends Model
{
    use Sluggable;

    protected array $slugs = [
        'slug' => 'title',
        'meta_slug' => ['title', 'category.name']
    ];

    protected array $slugConfig = [
        'separator' => '-',
        'language' => 'en',
        'max_length' => 255,
        'reserved_words' => ['admin', 'api', 'blog'],
        'unique' => true,
        'on_update' => false,
        'ascii_only' => true,
    ];
}
```

#### Slug Examples

```php
// Automatic slug generation on create
$post = Post::create([
    'title' => 'My Amazing Blog Post!',
    // slug will be automatically set to 'my-amazing-blog-post'
]);

// Find by slug
$post = Post::findBySlug('my-amazing-blog-post');
$post = Post::findBySlugOrFail('my-amazing-blog-post');

// Query by slug
$posts = Post::whereSlug('my-amazing-blog-post')->get();

// Custom route model binding
public function getRouteKeyName()
{
    return 'slug';
}

// Regenerate slugs
$post->regenerateSlugs();

// Custom slug configuration
$post->setSlugConfig([
    'separator' => '_',
    'max_length' => 100,
    'on_update' => true,
]);

// Get slug variations for debugging
$variations = $post->getSlugVariations('slug', 'my-post', 5);
// Returns: ['my-post', 'my-post-2', 'my-post-3', 'my-post-4', 'my-post-5']
```

### 9. Paginatable Trait

Advanced pagination methods optimized for large datasets.

#### Setup

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Litepie\Database\Traits\Paginatable;

class Product extends Model
{
    use Paginatable;

    // Optional: Configure pagination behavior
    protected array $paginatableConfig = [
        'cache_ttl' => 300,
        'use_approximate_count' => true,
        'approximate_count_threshold' => 1000000,
        'cursor_pagination_default' => false,
    ];
}
```

#### Pagination Examples

```php
// Cursor pagination - much faster for large datasets
$products = Product::where('active', true)
    ->cursorPaginate(50);

// Fast pagination - no total count (LIMIT + 1)
$products = Product::where('active', true)
    ->fastPaginate(20);

// Seek pagination - perfect for real-time feeds
$articles = Article::seekPaginate(
    limit: 20,
    lastId: 100,
    direction: 'next',
    orderColumn: 'created_at'
);

// Optimized pagination - uses approximate count for large tables
$orders = Order::where('status', 'completed')
    ->optimizedPaginate(50);

// Cached pagination - cache total count for expensive queries
$products = Product::with(['category', 'brand'])
    ->cachedPaginate(perPage: 20, cacheTtl: 300);

// Window pagination - fast even for deep pagination (page 1000+)
$logs = LogEntry::windowPaginate(perPage: 50, page: 1000);

// Parallel pagination - for extremely large datasets
$logs = LogEntry::where('level', 'error')
    ->parallelPaginate(perPage: 100, parallelQueries: 4);

// Get estimated count (much faster than COUNT(*) on large tables)
$estimatedCount = Product::where('active', true)->estimatedCount();

// Performance comparison report
$report = Product::paginationPerformanceReport(perPage: 20, page: 1);
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
```

#### When to Use Each Method

**Cursor Pagination** (`cursorPaginate`):
- ✅ Large datasets (> 100K rows)
- ✅ Infinite scroll
- ✅ Real-time data
- ❌ Need page numbers

**Fast Pagination** (`fastPaginate`):
- ✅ Don't need total count
- ✅ "Load More" button
- ✅ Better performance than standard
- ❌ Need page numbers

**Seek Pagination** (`seekPaginate`):
- ✅ Real-time feeds (Twitter, Facebook style)
- ✅ Activity streams
- ✅ Best performance
- ❌ Need traditional pagination

**Optimized Pagination** (`optimizedPaginate`):
- ✅ Very large tables (> 1M rows)
- ✅ Need total count
- ✅ Uses approximate count
- ❌ Need exact count

**Cached Pagination** (`cachedPaginate`):
- ✅ Expensive count queries
- ✅ Heavy joins/aggregations
- ✅ Caches total count
- ✅ Any dataset size

**Window Pagination** (`windowPaginate`):
- ✅ Deep pagination (page 1000+)
- ✅ Large datasets
- ✅ Better than OFFSET
- ⚠️ Database-specific (MySQL/PostgreSQL)

### 10. Enhanced JSON and Money Casts

#### JSON Cast with Schema Validation

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Litepie\Database\Casts\JsonCast;

class User extends Model
{
    protected $casts = [
        'preferences' => JsonCast::class,
        'metadata' => JsonCast::withDefault(['theme' => 'light']),
        'settings' => JsonCast::withSchema([
            'theme' => ['type' => 'string', 'required' => true],
            'notifications' => ['type' => 'boolean', 'required' => false],
        ]),
    ];
}

// Usage
$user = new User();
$user->preferences = ['language' => 'en', 'timezone' => 'UTC'];
$user->save();

// Access
echo $user->preferences['language']; // 'en'
```

#### Money Cast with Currency Support

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Litepie\Database\Casts\MoneyCast;

class Order extends Model
{
    protected $casts = [
        'total' => MoneyCast::class, // Default USD
        'tax' => MoneyCast::currency('USD'),
        'shipping' => MoneyCast::asDecimal('EUR', 2),
        'crypto_amount' => MoneyCast::crypto('BTC'),
    ];
}

// Usage
$order = new Order();
$order->total = ['amount' => 99.99, 'currency' => 'USD'];
// or
$order->total = 99.99; // Assumes USD

// Access
echo $order->total['amount']; // 99.99
echo $order->total['formatted']; // $99.99
echo $order->total['cents']; // 9999
echo $order->total['currency']; // USD
```

### 11. Model Macros

Extend Eloquent models with custom macros.

```php
use Litepie\Database\Facades\ModelMacro;

// Add a macro to specific models
ModelMacro::addMacro([User::class, Post::class], 'popular', function () {
    return $this->where('views', '>', 1000);
});

// Add a global macro (applies to all models)
ModelMacro::addGlobalMacro('recent', function (int $days = 30) {
    return $this->where('created_at', '>=', now()->subDays($days));
});

// Usage
$popularUsers = User::popular()->get();
$recentPosts = Post::recent(7)->get();

// Check if model has macro
if (ModelMacro::modelHasMacro(User::class, 'popular')) {
    // Macro exists
}

// Get statistics
$stats = ModelMacro::getStatistics();
```

### 12. Advanced Query Features

#### Filtering and Searching

```php
// Advanced filtering
$users = User::filter([
    'status' => 'active',
    'age:>' => 18,
    'country' => ['US', 'CA', 'UK'],
    'created_at:between' => ['2023-01-01', '2023-12-31'],
])->get();

// Batch processing
User::where('active', false)->batch(100, function ($users) {
    foreach ($users as $user) {
        $user->delete();
    }
});

// Pagination with metadata
$users = User::paginateWithMeta(15);

// Cache with tags
$activeUsers = User::where('active', true)
    ->cacheWithTags(['users', 'active'], 60)
    ->get();
```

#### Migration Macros

The package provides helpful migration macros:

```php
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->slug(); // Adds slug column with index
    $table->status(); // Adds status enum column
    $table->auditColumns(); // Adds created_at, updated_at, deleted_at, archived_at, etc.
    $table->seoColumns(); // Adds meta_title, meta_description, etc.
    $table->position(); // Adds position column for ordering
    $table->uuidPrimary(); // UUID primary key
    $table->jsonWithIndex('metadata', ['type', 'category']); // JSON with virtual indexes
});
```

## Configuration

The package comes with sensible defaults, but you can customize behavior by publishing and modifying the configuration file:

```php
// config/litepie-database.php
return [
    'cache' => [
        'default_ttl' => 60,
        'tags_enabled' => true,
        'warm_up_on_boot' => false,
    ],
    
    'archivable' => [
        'default_reason' => 'Archived by system',
        'track_user' => true,
    ],
    
    'sluggable' => [
        'separator' => '-',
        'max_length' => 255,
        'reserved_words' => ['admin', 'api', 'www'],
    ],
    
    'searchable' => [
        'default_strategy' => 'basic',
        'enable_full_text' => true,
        'fuzzy_threshold' => 2,
    ],
];
```

## Testing

Run the test suite:

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details on how to contribute to this project.

## Security

If you discover any security-related issues, please email security@renfos.com instead of using the issue tracker.

## Credits

- [Renfos Technologies](https://renfos.com)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for more information on what has changed recently.

## Support

- 📧 Email: info@renfos.com
- 🐛 Issues: [GitHub Issues](https://github.com/litepie/database/issues)
- 💬 Discussions: [GitHub Discussions](https://github.com/litepie/database/discussions)

---

## 🏢 About

This package is part of the **Litepie** ecosystem, developed by **Renfos Technologies**. 

### Organization Structure
- **Vendor:** Litepie
- **Framework:** Lavalite
- **Company:** Renfos Technologies

### Links & Resources
- 🌐 **Website:** [https://lavalite.org](https://lavalite.org)
- 📚 **Documentation:** [https://docs.lavalite.org](https://docs.lavalite.org)
- 💼 **Company:** [https://renfos.com](https://renfos.com)
- 📧 **Support:** [support@lavalite.org](mailto:support@lavalite.org)

---

<div align="center">
  <p><strong>Built with ❤️ by Renfos Technologies</strong></p>
  <p><em>Empowering developers with robust Laravel solutions</em></p>
</div>
