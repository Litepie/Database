# Litepie Database Package - Code Examples for AI

## Complete Working Examples

### Example 1: Blog Post with Versioning and Translations

```php
// Model Definition
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Litepie\Database\Traits\Versionable;
use Litepie\Database\Traits\Translatable;
use Litepie\Database\Traits\Sluggable;
use Litepie\Database\Traits\Searchable;
use Litepie\Database\Traits\Cacheable;

class Post extends Model
{
    use Versionable, Translatable, Sluggable, Searchable, Cacheable;
    
    protected $fillable = ['title', 'content', 'excerpt', 'status'];
    
    // Translatable config
    protected array $translatable = ['title', 'content', 'excerpt'];
    
    // Sluggable config
    protected array $slugs = ['slug' => 'title'];
    
    // Searchable config
    protected array $searchable = ['title', 'content', 'excerpt'];
    protected array $searchWeights = ['title' => 10, 'content' => 5];
    
    // Versionable config
    protected int $maxVersions = 20;
    protected array $versionableExclude = ['views', 'updated_at'];
}

// Usage in Controller
class PostController extends Controller
{
    public function store(Request $request)
    {
        // Create post in English
        $post = Post::create([
            'title' => $request->title,
            'content' => $request->content,
            'excerpt' => $request->excerpt,
            'status' => 'draft',
        ]);
        
        // Add Spanish translation
        $post->translate('es', [
            'title' => $request->title_es,
            'content' => $request->content_es,
            'excerpt' => $request->excerpt_es,
        ]);
        
        // Create version
        $post->createVersion('Initial draft', auth()->user());
        
        return response()->json($post);
    }
    
    public function update(Request $request, Post $post)
    {
        // Update post
        $post->update($request->only(['title', 'content', 'excerpt']));
        
        // Auto-versioned on save
        $post->createVersion('Editorial update', auth()->user());
        
        return response()->json($post);
    }
    
    public function rollback(Post $post, int $versionNumber)
    {
        $post->rollbackToVersion($versionNumber);
        return response()->json(['message' => 'Rolled back successfully']);
    }
    
    public function search(Request $request)
    {
        // Search with caching
        $posts = Post::search($request->query)
            ->where('status', 'published')
            ->cacheFor(30)
            ->get();
        
        return response()->json($posts);
    }
    
    public function show($slug)
    {
        // Find by slug with caching
        $post = Post::where('slug', $slug)
            ->where('status', 'published')
            ->cacheFor(60)
            ->firstOrFail();
        
        // Set locale based on user preference
        $locale = request()->header('Accept-Language', 'en');
        $post->setLocale($locale);
        
        return response()->json([
            'title' => $post->title,
            'content' => $post->content,
            'excerpt' => $post->excerpt,
            'locale' => $locale,
            'available_locales' => $post->getAvailableLocales(),
        ]);
    }
}
```

### Example 2: E-commerce Product with Metadata

```php
// Model
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Litepie\Database\Traits\Translatable;
use Litepie\Database\Traits\Metable;
use Litepie\Database\Traits\Searchable;
use Litepie\Database\Traits\Sluggable;
use Litepie\Database\Traits\Sortable;
use Litepie\Database\Traits\Cacheable;

class Product extends Model
{
    use Translatable, Metable, Searchable, Sluggable, Sortable, Cacheable;
    
    protected $fillable = ['name', 'description', 'price', 'sku', 'status'];
    
    protected array $translatable = ['name', 'description', 'features'];
    protected array $slugs = ['slug' => 'name'];
    protected array $searchable = ['name', 'description', 'sku'];
}

// Controller
class ProductController extends Controller
{
    public function store(Request $request)
    {
        $product = Product::create([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'sku' => $request->sku,
            'status' => 'active',
        ]);
        
        // Add custom attributes as meta
        $product->setMultipleMeta([
            'brand' => $request->brand,
            'color' => $request->color,
            'size' => $request->size,
            'weight' => $request->weight,
            'warranty_months' => $request->warranty,
            'is_featured' => $request->featured ?? false,
        ]);
        
        // Add translations
        foreach ($request->translations ?? [] as $locale => $trans) {
            $product->translate($locale, [
                'name' => $trans['name'],
                'description' => $trans['description'],
                'features' => $trans['features'] ?? '',
            ]);
        }
        
        return response()->json($product);
    }
    
    public function filter(Request $request)
    {
        $query = Product::query();
        
        // Filter by meta
        if ($request->brand) {
            $query->whereMeta('brand', $request->brand);
        }
        
        if ($request->featured) {
            $query->whereMeta('is_featured', true);
        }
        
        // Search
        if ($request->search) {
            $query->search($request->search);
        }
        
        // Cache results
        $products = $query->cacheFor(30)->get();
        
        return response()->json($products);
    }
    
    public function show($slug)
    {
        $product = Product::where('slug', $slug)
            ->cacheFor(60)
            ->firstOrFail();
        
        $locale = request()->header('Accept-Language', 'en');
        $product->setLocale($locale);
        
        return response()->json([
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'attributes' => $product->getAllMeta(),
            'locale' => $locale,
        ]);
    }
}
```

### Example 3: Analytics Dashboard

```php
// Model
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Litepie\Database\Traits\Aggregatable;
use Litepie\Database\Traits\Cacheable;
use Litepie\Database\Traits\Exportable;

class Order extends Model
{
    use Aggregatable, Cacheable, Exportable;
    
    protected $fillable = ['customer_id', 'total', 'status', 'created_at'];
}

// Controller
class DashboardController extends Controller
{
    public function analytics(Request $request)
    {
        // Current month stats
        $currentMonth = Order::currentPeriod('created_at', 'month')
            ->aggregate(['count' => '*', 'sum' => 'total', 'avg' => 'total'])
            ->cacheFor(10);
        
        // Compare with previous month
        $comparison = Order::compareWithPreviousPeriod('total', 'sum', 'month');
        
        // 6-month growth rate
        $growth = Order::growthRate('total', 'month', 6);
        
        // Daily trend for current month
        $dailyTrend = Order::trendWithGapFilling('created_at', 'day', 0, 30);
        
        // Top 10 customers
        $topCustomers = Order::aggregateBy(['customer_id'], ['sum' => 'total'])
            ->sortByDesc('sum')
            ->take(10);
        
        // Year over year
        $yoy = Order::yearOverYear('total', 'sum');
        
        return response()->json([
            'current_month' => $currentMonth,
            'comparison' => $comparison,
            'growth' => $growth,
            'daily_trend' => $dailyTrend,
            'top_customers' => $topCustomers,
            'year_over_year' => $yoy,
        ]);
    }
    
    public function export(Request $request)
    {
        $orders = Order::query()
            ->whereBetween('created_at', [$request->start_date, $request->end_date])
            ->with(['customer', 'items']);
        
        // Export to Excel with caching
        return $orders->exportToExcel([
            'id',
            'customer.name as customer',
            'total',
            'status',
            'created_at',
        ], 'orders-report.xlsx');
    }
}
```

### Example 4: User Preferences System

```php
// Model
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Litepie\Database\Traits\Metable;
use Litepie\Database\Traits\Versionable;

class User extends Model
{
    use Metable, Versionable;
    
    protected int $maxVersions = 50;
}

// Controller
class UserPreferencesController extends Controller
{
    public function update(Request $request, User $user)
    {
        // Save preferences as meta
        $user->setMultipleMeta([
            'theme' => $request->theme,
            'language' => $request->language,
            'timezone' => $request->timezone,
            'notifications_email' => $request->notifications['email'] ?? true,
            'notifications_sms' => $request->notifications['sms'] ?? false,
            'per_page' => $request->per_page ?? 20,
        ]);
        
        // Create version for audit
        $user->createVersion('Preferences updated', $user);
        
        return response()->json($user->getAllMeta());
    }
    
    public function get(User $user)
    {
        return response()->json([
            'theme' => $user->getMeta('theme', 'light'),
            'language' => $user->getMeta('language', 'en'),
            'timezone' => $user->getMeta('timezone', 'UTC'),
            'notifications' => [
                'email' => $user->getMeta('notifications_email', true),
                'sms' => $user->getMeta('notifications_sms', false),
            ],
            'per_page' => $user->getMeta('per_page', 20),
        ]);
    }
}
```

### Example 5: Import/Export CSV

```php
// Model
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Litepie\Database\Traits\Importable;
use Litepie\Database\Traits\Exportable;
use Litepie\Database\Traits\Batchable;

class Contact extends Model
{
    use Importable, Exportable, Batchable;
    
    protected $fillable = ['name', 'email', 'phone', 'company'];
}

// Controller
class ContactImportController extends Controller
{
    public function preview(Request $request)
    {
        $file = $request->file('csv');
        
        // Preview first 10 rows
        $preview = Contact::previewImport($file->getPathname(), 10);
        
        return response()->json($preview);
    }
    
    public function import(Request $request)
    {
        $file = $request->file('csv');
        
        // Define column mapping
        $mapping = [
            'Name' => 'name',
            'Email Address' => 'email',
            'Phone' => 'phone',
            'Company Name' => 'company',
        ];
        
        // Import with validation
        $result = Contact::importFromCsv(
            $file->getPathname(),
            $mapping,
            skipFirstRow: true,
            validateBeforeInsert: true
        );
        
        return response()->json([
            'imported' => $result['imported'],
            'failed' => $result['failed'],
            'errors' => $result['errors'] ?? [],
        ]);
    }
    
    public function export(Request $request)
    {
        return Contact::query()
            ->where('status', 'active')
            ->exportToCsv(['name', 'email', 'phone', 'company'], 'contacts.csv');
    }
}
```

### Example 6: Large Dataset Pagination

```php
// Model
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Litepie\Database\Traits\Paginatable;
use Litepie\Database\Traits\Cacheable;

class LogEntry extends Model
{
    use Paginatable, Cacheable;
}

// Controller
class LogController extends Controller
{
    public function index(Request $request)
    {
        // For infinite scroll (no page numbers)
        if ($request->cursor) {
            return LogEntry::where('level', 'error')
                ->cursorPaginate(50);
        }
        
        // For traditional pagination with large dataset
        return LogEntry::where('level', 'error')
            ->optimizedPaginate(50);
    }
    
    public function feed(Request $request)
    {
        // Real-time feed style (Twitter/Facebook)
        return LogEntry::seekPaginate(
            limit: 50,
            lastId: $request->last_id,
            direction: $request->direction ?? 'next',
            orderColumn: 'created_at'
        );
    }
}
```

## Common Patterns

### Pattern: Multi-tenant Blog
```php
class Post extends Model
{
    use Versionable, Translatable, Sluggable, Searchable, Cacheable;
    
    // Scope for tenant
    protected static function booted()
    {
        static::addGlobalScope('tenant', function ($query) {
            if (auth()->check()) {
                $query->where('tenant_id', auth()->user()->tenant_id);
            }
        });
    }
}
```

### Pattern: Audit Trail
```php
class Document extends Model
{
    use Versionable;
    
    protected bool $autoVersioning = true;
    protected int $maxVersions = 100; // Keep all versions for compliance
    
    protected static function booted()
    {
        static::updating(function ($model) {
            $model->createVersion(
                'Document updated',
                auth()->user(),
                ['ip' => request()->ip(), 'user_agent' => request()->userAgent()]
            );
        });
    }
}
```

### Pattern: Searchable Catalog
```php
class Product extends Model
{
    use Searchable, Cacheable, Sluggable;
    
    protected array $searchable = ['name', 'description', 'sku'];
    protected array $fullTextSearchable = ['name', 'description'];
    protected array $searchWeights = ['name' => 10, 'sku' => 8, 'description' => 5];
    
    public function scopeActiveProducts($query)
    {
        return $query->where('status', 'active')->where('stock', '>', 0);
    }
}
```

These examples show real-world usage patterns that AI assistants can reference and adapt.
