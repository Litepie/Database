<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Litepie\Database\Traits\Archivable;
use Litepie\Database\Traits\Cacheable;
use Litepie\Database\Traits\Searchable;
use Litepie\Database\Traits\Sluggable;
use Litepie\Database\Casts\JsonCast;
use Litepie\Database\Casts\MoneyCast;

/**
 * Example Product model showcasing all package features.
 * 
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $description
 * @property array $price
 * @property array $specifications
 * @property array $metadata
 * @property string $status
 * @property int $views
 * @property int $position
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 * @property \Carbon\Carbon|null $archived_at
 * @property string|null $archived_by
 * @property string|null $archived_reason
 */
class Product extends Model
{
    use SoftDeletes;
    use Archivable;
    use Cacheable;
    use Searchable;
    use Sluggable;

    protected $fillable = [
        'name',
        'description',
        'price',
        'specifications',
        'metadata',
        'status',
        'views',
        'position',
        'category_id',
        'brand_id',
    ];

    protected $casts = [
        'price' => MoneyCast::currency('USD'),
        'specifications' => JsonCast::withSchema([
            'weight' => ['type' => 'float', 'required' => true],
            'dimensions' => ['type' => 'array', 'required' => false],
            'materials' => ['type' => 'array', 'required' => false],
        ]),
        'metadata' => JsonCast::withDefault([]),
        'views' => 'integer',
        'position' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    // Sluggable configuration
    protected array $slugs = [
        'slug' => 'name',
    ];

    protected array $slugConfig = [
        'separator' => '-',
        'max_length' => 255,
        'unique' => true,
        'on_update' => false,
        'ascii_only' => true,
    ];

    // Searchable configuration
    protected array $searchable = [
        'name',
        'description',
        'category.name',
        'brand.name',
    ];

    protected array $fullTextSearchable = [
        'name',
        'description',
    ];

    protected array $searchWeights = [
        'name' => 10,
        'description' => 5,
        'category.name' => 3,
        'brand.name' => 3,
    ];

    // Cacheable configuration
    protected int $defaultCacheTtl = 120; // 2 hours
    protected array $cacheTags = ['products', 'catalog'];
    protected string $cachePrefix = 'product';

    // Archive configuration constants
    const ARCHIVED_AT = 'archived_at';
    const ARCHIVED_BY = 'archived_by';
    const ARCHIVED_REASON = 'archived_reason';

    /**
     * Relationships
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    public function scopePopular($query)
    {
        return $query->where('views', '>', 1000);
    }

    public function scopeInPriceRange($query, $min, $max)
    {
        return $query->whereRaw("JSON_EXTRACT(price, '$.amount') BETWEEN ? AND ?", [$min, $max]);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Accessors
     */
    public function getFormattedPriceAttribute()
    {
        return $this->price['formatted'] ?? '$0.00';
    }

    public function getAverageRatingAttribute()
    {
        return $this->reviews()->avg('rating') ?? 0;
    }

    public function getReviewCountAttribute()
    {
        return $this->reviews()->count();
    }

    /**
     * Mutators
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = trim($value);
    }

    /**
     * Custom methods demonstrating package features
     */

    /**
     * Get products with advanced caching.
     */
    public static function getCachedProducts($categoryId = null, $limit = 10)
    {
        $query = static::with(['category', 'brand'])
            ->active()
            ->limit($limit);

        if ($categoryId) {
            $query->byCategory($categoryId);
        }

        $cacheKey = "products_category_{$categoryId}_limit_{$limit}";
        $cacheTags = ['products', 'categories'];

        return $query->cacheWithTags($cacheTags, 60, $cacheKey);
    }

    /**
     * Search products with multiple strategies.
     */
    public static function searchProducts($term, $strategy = 'basic')
    {
        $query = static::with(['category', 'brand'])->active();

        return match($strategy) {
            'full_text' => $query->fullTextSearch($term),
            'fuzzy' => $query->fuzzySearch($term),
            'weighted' => $query->weightedSearch($term),
            'boolean' => $query->booleanSearch($term),
            default => $query->search($term),
        };
    }

    /**
     * Archive products with reason.
     */
    public static function archiveOldProducts($days = 365, $reason = 'Automatic cleanup')
    {
        $cutoffDate = now()->subDays($days);
        
        return static::where('created_at', '<', $cutoffDate)
            ->where('views', '<', 10)
            ->get()
            ->each(function ($product) use ($reason) {
                $product->archive($reason, 'system');
            });
    }

    /**
     * Bulk update with validation.
     */
    public static function bulkUpdatePrices(array $updates)
    {
        $results = [];
        
        foreach ($updates as $productId => $newPrice) {
            $product = static::find($productId);
            
            if ($product) {
                $product->price = $newPrice;
                $product->save();
                $results[] = $product;
            }
        }

        // Clear cache after bulk update
        static::clearModelCache();

        return $results;
    }

    /**
     * Get product recommendations based on specifications.
     */
    public function getRecommendations($limit = 5)
    {
        $query = static::where('id', '!=', $this->id)
            ->where('category_id', $this->category_id)
            ->active();

        // Cache recommendations
        $cacheKey = "recommendations_product_{$this->id}_limit_{$limit}";
        
        return $query->cacheFor(30, $cacheKey)->limit($limit)->get();
    }

    /**
     * Advanced filtering example.
     */
    public static function advancedFilter(array $filters)
    {
        $query = static::with(['category', 'brand'])->active();

        // Apply filters using the enhanced filter macro
        $query->filter($filters);

        // Add custom filters
        if (isset($filters['price_range'])) {
            [$min, $max] = $filters['price_range'];
            $query->inPriceRange($min, $max);
        }

        if (isset($filters['has_reviews'])) {
            $query->has('reviews');
        }

        if (isset($filters['rating_above'])) {
            $query->whereHas('reviews', function ($q) use ($filters) {
                $q->havingRaw('AVG(rating) > ?', [$filters['rating_above']]);
            });
        }

        return $query;
    }

    /**
     * Model events for demonstration.
     */
    protected static function booted()
    {
        // Increment views when accessed
        static::retrieved(function ($product) {
            // You might want to implement view tracking here
        });

        // Clear cache when model changes
        static::saved(function ($product) {
            cache()->tags(['products'])->flush();
        });

        // Archive related models
        static::archived(function ($product) {
            // Archive related reviews if needed
            $product->reviews()->update([
                'archived_at' => now(),
                'archived_reason' => 'Parent product archived'
            ]);
        });
    }
}
