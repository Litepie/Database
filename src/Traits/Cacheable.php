<?php

namespace Litepie\Database\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Advanced Cacheable trait with intelligent cache management.
 */
trait Cacheable
{
    /**
     * Default cache TTL in minutes.
     *
     * @var int
     */
    protected int $defaultCacheTtl = 60;

    /**
     * Cache tags for this model.
     *
     * @var array
     */
    protected array $cacheTags = [];

    /**
     * Cache key prefix.
     *
     * @var string
     */
    protected string $cachePrefix = '';

    /**
     * Whether to use cache for this model.
     *
     * @var bool
     */
    protected bool $useCache = true;

    /**
     * Boot the cacheable trait.
     *
     * @return void
     */
    public static function bootCacheable(): void
    {
        // Clear cache when model is created, updated, or deleted
        static::saved(function (Model $model) {
            $model->clearModelCache();
        });

        static::deleted(function (Model $model) {
            $model->clearModelCache();
        });
    }

    /**
     * Initialize the cacheable trait.
     *
     * @return void
     */
    public function initializeCacheable(): void
    {
        if (empty($this->cacheTags)) {
            $this->cacheTags = [strtolower(class_basename($this))];
        }

        if (empty($this->cachePrefix)) {
            $this->cachePrefix = strtolower(class_basename($this));
        }
    }

    /**
     * Cache query results for a specified duration.
     *
     * @param Builder $query
     * @param int $minutes
     * @param string|null $key
     * @return mixed
     */
    public function scopeCacheFor(Builder $query, int $minutes = null, ?string $key = null): mixed
    {
        if (!$this->shouldUseCache()) {
            return $query->get();
        }

        $minutes = $minutes ?? $this->defaultCacheTtl;
        $key = $key ?? $this->generateCacheKey($query);

        return Cache::remember($key, now()->addMinutes($minutes), function () use ($query) {
            return $query->get();
        });
    }

    /**
     * Cache query results with tags.
     *
     * @param Builder $query
     * @param array $tags
     * @param int $minutes
     * @param string|null $key
     * @return mixed
     */
    public function scopeCacheWithTags(Builder $query, array $tags = [], int $minutes = null, ?string $key = null): mixed
    {
        if (!$this->shouldUseCache()) {
            return $query->get();
        }

        $minutes = $minutes ?? $this->defaultCacheTtl;
        $key = $key ?? $this->generateCacheKey($query);
        $tags = array_merge($this->getCacheTags(), $tags);

        return Cache::tags($tags)->remember($key, now()->addMinutes($minutes), function () use ($query) {
            return $query->get();
        });
    }

    /**
     * Cache a single model by ID.
     *
     * @param Builder $query
     * @param mixed $id
     * @param int $minutes
     * @return Model|null
     */
    public function scopeCacheById(Builder $query, mixed $id, int $minutes = null): ?Model
    {
        if (!$this->shouldUseCache()) {
            return $query->find($id);
        }

        $minutes = $minutes ?? $this->defaultCacheTtl;
        $key = $this->generateModelCacheKey($id);

        return Cache::remember($key, now()->addMinutes($minutes), function () use ($query, $id) {
            return $query->find($id);
        });
    }

    /**
     * Cache query results until manually cleared.
     *
     * @param Builder $query
     * @param string|null $key
     * @return mixed
     */
    public function scopeCacheForever(Builder $query, ?string $key = null): mixed
    {
        if (!$this->shouldUseCache()) {
            return $query->get();
        }

        $key = $key ?? $this->generateCacheKey($query);

        return Cache::rememberForever($key, function () use ($query) {
            return $query->get();
        });
    }

    /**
     * Cache with automatic invalidation based on model updates.
     *
     * @param Builder $query
     * @param int $minutes
     * @param array $dependencies
     * @return mixed
     */
    public function scopeSmartCache(Builder $query, int $minutes = null, array $dependencies = []): mixed
    {
        if (!$this->shouldUseCache()) {
            return $query->get();
        }

        $minutes = $minutes ?? $this->defaultCacheTtl;
        $key = $this->generateSmartCacheKey($query, $dependencies);
        $tags = array_merge($this->getCacheTags(), $dependencies);

        return Cache::tags($tags)->remember($key, now()->addMinutes($minutes), function () use ($query) {
            return $query->get();
        });
    }

    /**
     * Cache paginated results.
     *
     * @param Builder $query
     * @param int $perPage
     * @param int $minutes
     * @return mixed
     */
    public function scopeCachePaginate(Builder $query, int $perPage = 15, int $minutes = null): mixed
    {
        if (!$this->shouldUseCache()) {
            return $query->paginate($perPage);
        }

        $minutes = $minutes ?? $this->defaultCacheTtl;
        $page = request('page', 1);
        $key = $this->generatePaginationCacheKey($query, $perPage, $page);

        return Cache::remember($key, now()->addMinutes($minutes), function () use ($query, $perPage) {
            return $query->paginate($perPage);
        });
    }

    /**
     * Cache query results with custom cache store.
     *
     * @param Builder $query
     * @param string $store
     * @param int $minutes
     * @param string|null $key
     * @return mixed
     */
    public function scopeCacheOnStore(Builder $query, string $store, int $minutes = null, ?string $key = null): mixed
    {
        if (!$this->shouldUseCache()) {
            return $query->get();
        }

        $minutes = $minutes ?? $this->defaultCacheTtl;
        $key = $key ?? $this->generateCacheKey($query);

        return Cache::store($store)->remember($key, now()->addMinutes($minutes), function () use ($query) {
            return $query->get();
        });
    }

    /**
     * Clear all cache for this model.
     *
     * @return bool
     */
    public function clearModelCache(): bool
    {
        return Cache::tags($this->getCacheTags())->flush();
    }

    /**
     * Clear specific cache entry.
     *
     * @param string $key
     * @return bool
     */
    public function clearCacheKey(string $key): bool
    {
        return Cache::forget($key);
    }

    /**
     * Clear cache for specific model instance.
     *
     * @return bool
     */
    public function clearInstanceCache(): bool
    {
        $key = $this->generateModelCacheKey($this->getKey());
        return Cache::forget($key);
    }

    /**
     * Warm up cache with commonly accessed data.
     *
     * @param array $queries
     * @param int $minutes
     * @return void
     */
    public static function warmUpCache(array $queries = [], int $minutes = 60): void
    {
        $model = new static();
        
        if (empty($queries)) {
            $queries = $model->getDefaultWarmUpQueries();
        }

        foreach ($queries as $queryData) {
            $query = $queryData['query'] ?? static::query();
            $key = $queryData['key'] ?? $model->generateCacheKey($query);
            $ttl = $queryData['ttl'] ?? $minutes;

            Cache::put($key, $query->get(), now()->addMinutes($ttl));
        }
    }

    /**
     * Get cache statistics for this model.
     *
     * @return array
     */
    public function getCacheStats(): array
    {
        // This would require cache driver support for stats
        return [
            'model' => get_class($this),
            'cache_tags' => $this->getCacheTags(),
            'cache_prefix' => $this->getCachePrefix(),
            'default_ttl' => $this->defaultCacheTtl,
            'cache_enabled' => $this->shouldUseCache(),
        ];
    }

    /**
     * Generate cache key for query.
     *
     * @param Builder $query
     * @return string
     */
    protected function generateCacheKey(Builder $query): string
    {
        $sql = $query->toSql();
        $bindings = $query->getBindings();
        
        $key = $this->getCachePrefix() . ':' . md5($sql . serialize($bindings));
        
        return $key;
    }

    /**
     * Generate cache key for a specific model.
     *
     * @param mixed $id
     * @return string
     */
    protected function generateModelCacheKey(mixed $id): string
    {
        return $this->getCachePrefix() . ':model:' . $id;
    }

    /**
     * Generate smart cache key with dependencies.
     *
     * @param Builder $query
     * @param array $dependencies
     * @return string
     */
    protected function generateSmartCacheKey(Builder $query, array $dependencies): string
    {
        $baseKey = $this->generateCacheKey($query);
        $depHash = md5(serialize($dependencies));
        
        return $baseKey . ':deps:' . $depHash;
    }

    /**
     * Generate cache key for pagination.
     *
     * @param Builder $query
     * @param int $perPage
     * @param int $page
     * @return string
     */
    protected function generatePaginationCacheKey(Builder $query, int $perPage, int $page): string
    {
        $baseKey = $this->generateCacheKey($query);
        
        return $baseKey . ':paginate:' . $perPage . ':' . $page;
    }

    /**
     * Get cache tags for this model.
     *
     * @return array
     */
    protected function getCacheTags(): array
    {
        return $this->cacheTags;
    }

    /**
     * Get cache prefix for this model.
     *
     * @return string
     */
    protected function getCachePrefix(): string
    {
        return $this->cachePrefix;
    }

    /**
     * Check if cache should be used.
     *
     * @return bool
     */
    protected function shouldUseCache(): bool
    {
        return $this->useCache && config('cache.default') !== null;
    }

    /**
     * Get default warm-up queries for this model.
     *
     * @return array
     */
    protected function getDefaultWarmUpQueries(): array
    {
        return [
            [
                'query' => static::query()->limit(10),
                'key' => $this->getCachePrefix() . ':recent',
                'ttl' => 30,
            ],
            [
                'query' => static::query()->count(),
                'key' => $this->getCachePrefix() . ':count',
                'ttl' => 60,
            ],
        ];
    }

    /**
     * Set cache TTL for this instance.
     *
     * @param int $minutes
     * @return $this
     */
    public function setCacheTtl(int $minutes): static
    {
        $this->defaultCacheTtl = $minutes;
        return $this;
    }

    /**
     * Set cache tags for this instance.
     *
     * @param array $tags
     * @return $this
     */
    public function setCacheTags(array $tags): static
    {
        $this->cacheTags = $tags;
        return $this;
    }

    /**
     * Disable cache for this instance.
     *
     * @return $this
     */
    public function disableCache(): static
    {
        $this->useCache = false;
        return $this;
    }

    /**
     * Enable cache for this instance.
     *
     * @return $this
     */
    public function enableCache(): static
    {
        $this->useCache = true;
        return $this;
    }
}
