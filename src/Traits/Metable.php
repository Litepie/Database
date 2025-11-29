<?php

namespace Litepie\Database\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Metable trait for flexible key-value metadata storage.
 * 
 * Similar to WordPress post_meta, this trait allows you to:
 * - Store arbitrary key-value pairs for any model
 * - Query models by metadata
 * - Type-safe metadata retrieval
 * - Bulk metadata operations
 */
trait Metable
{
    /**
     * Cached metadata for this instance.
     *
     * @var Collection|null
     */
    protected ?Collection $metaCache = null;

    /**
     * Boot the metable trait.
     *
     * @return void
     */
    public static function bootMetable(): void
    {
        static::deleted(function (Model $model) {
            if (method_exists($model, 'isForceDeleting') && $model->isForceDeleting()) {
                $model->meta()->delete();
            }
        });
    }

    /**
     * Get all metadata for this model.
     *
     * @return MorphMany
     */
    public function meta(): MorphMany
    {
        return $this->morphMany(
            config('litepie-database.metable.model', ModelMeta::class),
            'metable'
        );
    }

    /**
     * Set a meta value.
     *
     * @param string $key
     * @param mixed $value
     * @param string|null $type
     * @return Model
     */
    public function setMeta(string $key, mixed $value, ?string $type = null): Model
    {
        $type = $type ?? $this->detectMetaType($value);

        $meta = $this->meta()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $this->serializeMetaValue($value, $type),
                'type' => $type,
            ]
        );

        $this->clearMetaCache();

        return $meta;
    }

    /**
     * Get a meta value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getMeta(string $key, mixed $default = null): mixed
    {
        $meta = $this->getMetaCollection()->firstWhere('key', $key);

        if (!$meta) {
            return $default;
        }

        return $this->unserializeMetaValue($meta->value, $meta->type);
    }

    /**
     * Check if meta key exists.
     *
     * @param string $key
     * @return bool
     */
    public function hasMeta(string $key): bool
    {
        return $this->getMetaCollection()->contains('key', $key);
    }

    /**
     * Delete a meta key.
     *
     * @param string $key
     * @return bool
     */
    public function deleteMeta(string $key): bool
    {
        $result = $this->meta()->where('key', $key)->delete();
        $this->clearMetaCache();
        
        return $result > 0;
    }

    /**
     * Set multiple meta values at once.
     *
     * @param array $meta
     * @return $this
     */
    public function setMultipleMeta(array $meta): static
    {
        foreach ($meta as $key => $value) {
            $this->setMeta($key, $value);
        }

        return $this;
    }

    /**
     * Get all meta as key-value array.
     *
     * @return array
     */
    public function getAllMeta(): array
    {
        $result = [];
        
        foreach ($this->getMetaCollection() as $meta) {
            $result[$meta->key] = $this->unserializeMetaValue($meta->value, $meta->type);
        }

        return $result;
    }

    /**
     * Delete all meta.
     *
     * @return bool
     */
    public function deleteAllMeta(): bool
    {
        $result = $this->meta()->delete();
        $this->clearMetaCache();
        
        return $result > 0;
    }

    /**
     * Delete multiple meta keys.
     *
     * @param array $keys
     * @return int
     */
    public function deleteMultipleMeta(array $keys): int
    {
        $result = $this->meta()->whereIn('key', $keys)->delete();
        $this->clearMetaCache();
        
        return $result;
    }

    /**
     * Increment a numeric meta value.
     *
     * @param string $key
     * @param int|float $amount
     * @return int|float
     */
    public function incrementMeta(string $key, int|float $amount = 1): int|float
    {
        $currentValue = $this->getMeta($key, 0);
        $newValue = $currentValue + $amount;
        
        $this->setMeta($key, $newValue);
        
        return $newValue;
    }

    /**
     * Decrement a numeric meta value.
     *
     * @param string $key
     * @param int|float $amount
     * @return int|float
     */
    public function decrementMeta(string $key, int|float $amount = 1): int|float
    {
        return $this->incrementMeta($key, -$amount);
    }

    /**
     * Append value to array meta.
     *
     * @param string $key
     * @param mixed $value
     * @return array
     */
    public function appendToMeta(string $key, mixed $value): array
    {
        $currentArray = $this->getMeta($key, []);
        
        if (!is_array($currentArray)) {
            $currentArray = [$currentArray];
        }
        
        $currentArray[] = $value;
        $this->setMeta($key, $currentArray);
        
        return $currentArray;
    }

    /**
     * Remove value from array meta.
     *
     * @param string $key
     * @param mixed $value
     * @return array
     */
    public function removeFromMeta(string $key, mixed $value): array
    {
        $currentArray = $this->getMeta($key, []);
        
        if (!is_array($currentArray)) {
            return [];
        }
        
        $currentArray = array_values(array_filter($currentArray, fn($item) => $item !== $value));
        $this->setMeta($key, $currentArray);
        
        return $currentArray;
    }

    /**
     * Get meta keys.
     *
     * @return array
     */
    public function getMetaKeys(): array
    {
        return $this->getMetaCollection()->pluck('key')->toArray();
    }

    /**
     * Search meta by key pattern.
     *
     * @param string $pattern
     * @return Collection
     */
    public function searchMeta(string $pattern): Collection
    {
        return $this->getMetaCollection()->filter(function ($meta) use ($pattern) {
            return preg_match("/{$pattern}/", $meta->key);
        });
    }

    /**
     * Copy meta to another model.
     *
     * @param Model $target
     * @param array $keys
     * @return $this
     */
    public function copyMetaTo(Model $target, array $keys = []): static
    {
        $metaToCopy = empty($keys) 
            ? $this->getAllMeta() 
            : array_intersect_key($this->getAllMeta(), array_flip($keys));

        foreach ($metaToCopy as $key => $value) {
            $target->setMeta($key, $value);
        }

        return $this;
    }

    /**
     * Merge meta from another model.
     *
     * @param Model $source
     * @param bool $overwrite
     * @return $this
     */
    public function mergeMetaFrom(Model $source, bool $overwrite = false): static
    {
        $sourceMeta = $source->getAllMeta();

        foreach ($sourceMeta as $key => $value) {
            if ($overwrite || !$this->hasMeta($key)) {
                $this->setMeta($key, $value);
            }
        }

        return $this;
    }

    /**
     * Scope to filter models by meta value.
     *
     * @param Builder $query
     * @param string $key
     * @param mixed $value
     * @param string $operator
     * @return Builder
     */
    public function scopeWhereMeta(Builder $query, string $key, mixed $value = null, string $operator = '='): Builder
    {
        return $query->whereHas('meta', function ($q) use ($key, $value, $operator) {
            $q->where('key', $key);
            
            if ($value !== null) {
                $type = $this->detectMetaType($value);
                $serializedValue = $this->serializeMetaValue($value, $type);
                $q->where('value', $operator, $serializedValue);
            }
        });
    }

    /**
     * Scope to filter models that have a meta key.
     *
     * @param Builder $query
     * @param string $key
     * @return Builder
     */
    public function scopeHasMeta(Builder $query, string $key): Builder
    {
        return $query->whereHas('meta', function ($q) use ($key) {
            $q->where('key', $key);
        });
    }

    /**
     * Scope to filter models that don't have a meta key.
     *
     * @param Builder $query
     * @param string $key
     * @return Builder
     */
    public function scopeDoesntHaveMeta(Builder $query, string $key): Builder
    {
        return $query->whereDoesntHave('meta', function ($q) use ($key) {
            $q->where('key', $key);
        });
    }

    /**
     * Scope to filter models by multiple meta conditions.
     *
     * @param Builder $query
     * @param array $conditions
     * @return Builder
     */
    public function scopeWhereMetaMultiple(Builder $query, array $conditions): Builder
    {
        foreach ($conditions as $key => $value) {
            $query->whereMeta($key, $value);
        }

        return $query;
    }

    /**
     * Scope to order by meta value.
     *
     * @param Builder $query
     * @param string $key
     * @param string $direction
     * @return Builder
     */
    public function scopeOrderByMeta(Builder $query, string $key, string $direction = 'asc'): Builder
    {
        $metaTable = (new (config('litepie-database.metable.model', ModelMeta::class)))->getTable();
        
        return $query->leftJoin($metaTable, function ($join) use ($metaTable, $key) {
            $join->on($this->getTable() . '.' . $this->getKeyName(), '=', $metaTable . '.metable_id')
                ->where($metaTable . '.metable_type', '=', get_class($this))
                ->where($metaTable . '.key', '=', $key);
        })->orderBy($metaTable . '.value', $direction);
    }

    /**
     * Get meta collection with caching.
     *
     * @return Collection
     */
    protected function getMetaCollection(): Collection
    {
        if ($this->metaCache === null) {
            $this->metaCache = $this->meta()->get();
        }

        return $this->metaCache;
    }

    /**
     * Clear meta cache.
     *
     * @return void
     */
    protected function clearMetaCache(): void
    {
        $this->metaCache = null;
    }

    /**
     * Detect the type of a value.
     *
     * @param mixed $value
     * @return string
     */
    protected function detectMetaType(mixed $value): string
    {
        return match (true) {
            is_bool($value) => 'boolean',
            is_int($value) => 'integer',
            is_float($value) => 'float',
            is_array($value) => 'array',
            is_object($value) => 'object',
            default => 'string',
        };
    }

    /**
     * Serialize meta value based on type.
     *
     * @param mixed $value
     * @param string $type
     * @return string
     */
    protected function serializeMetaValue(mixed $value, string $type): string
    {
        return match ($type) {
            'boolean' => $value ? '1' : '0',
            'array', 'object' => json_encode($value),
            default => (string) $value,
        };
    }

    /**
     * Unserialize meta value based on type.
     *
     * @param string $value
     * @param string $type
     * @return mixed
     */
    protected function unserializeMetaValue(string $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => (bool) $value,
            'integer' => (int) $value,
            'float' => (float) $value,
            'array' => json_decode($value, true),
            'object' => json_decode($value),
            default => $value,
        };
    }

    /**
     * Magic getter for meta values.
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        // Check if it's a meta key (prefixed with meta_)
        if (str_starts_with($key, 'meta_')) {
            $metaKey = substr($key, 5);
            return $this->getMeta($metaKey);
        }

        return parent::__get($key);
    }

    /**
     * Magic setter for meta values.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function __set($key, $value)
    {
        // Check if it's a meta key (prefixed with meta_)
        if (str_starts_with($key, 'meta_')) {
            $metaKey = substr($key, 5);
            $this->setMeta($metaKey, $value);
            return;
        }

        parent::__set($key, $value);
    }
}

/**
 * Model Meta class for storing metadata.
 * 
 * Migration:
 * Schema::create('model_meta', function (Blueprint $table) {
 *     $table->id();
 *     $table->morphs('metable');
 *     $table->string('key');
 *     $table->text('value')->nullable();
 *     $table->string('type')->default('string');
 *     $table->timestamps();
 *     
 *     $table->unique(['metable_type', 'metable_id', 'key']);
 *     $table->index(['metable_type', 'metable_id']);
 *     $table->index('key');
 * });
 */
class ModelMeta extends Model
{
    protected $table = 'model_meta';

    protected $fillable = [
        'metable_type',
        'metable_id',
        'key',
        'value',
        'type',
    ];

    /**
     * Get the owning metable model.
     */
    public function metable()
    {
        return $this->morphTo();
    }
}
