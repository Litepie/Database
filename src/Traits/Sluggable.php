<?php

namespace Litepie\Database\Traits;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Advanced Sluggable trait with multiple slug strategies and configurations.
 */
trait Sluggable
{
    /**
     * Array of attributes to automatically generate unique URL names (slugs) for.
     *
     * Format: ['slug_field' => 'source_field'] or ['slug_field' => ['source1', 'source2']]
     *
     * @var array
     */
    protected array $slugs = [];

    /**
     * Slug configuration options.
     *
     * @var array
     */
    protected array $slugConfig = [
        'separator' => '-',
        'language' => 'en',
        'max_length' => 255,
        'reserved_words' => ['admin', 'api', 'www'],
        'unique' => true,
        'include_trashed' => false,
        'on_update' => false,
        'ascii_only' => false,
    ];

    /**
     * Boot the sluggable trait for a model.
     *
     * @return void
     * @throws Exception
     */
    public static function bootSluggable(): void
    {
        if (!property_exists(get_called_class(), 'slugs') || empty((new static())->getSlugs())) {
            throw new Exception(sprintf(
                'You must define a $slugs property in %s to use the Sluggable trait.',
                get_called_class()
            ));
        }

        static::creating(function (Model $model) {
            $model->slugAttributes();
        });

        static::updating(function (Model $model) {
            if ($model->shouldRegenerateSlugOnUpdate()) {
                $model->slugAttributes();
            }
        });
    }

    /**
     * Initialize the sluggable trait.
     *
     * @return void
     */
    public function initializeSluggable(): void
    {
        if (empty($this->slugs)) {
            $this->slugs = ['slug' => 'title'];
        }

        $this->slugConfig = array_merge($this->slugConfig, $this->getSlugConfig());
    }

    /**
     * Adds slug attributes to the dataset, used before saving.
     *
     * @return void
     */
    public function slugAttributes(): void
    {
        foreach ($this->getSlugs() as $slugAttribute => $sourceAttributes) {
            $this->setSluggedValue($slugAttribute, $sourceAttributes);
        }
    }

    /**
     * Sets a single slug attribute value.
     *
     * @param string $slugAttribute
     * @param mixed $sourceAttributes
     * @param int|null $maxLength
     * @return string
     */
    public function setSluggedValue(string $slugAttribute, mixed $sourceAttributes, ?int $maxLength = null): string
    {
        $maxLength = $maxLength ?? $this->getSlugConfig('max_length');

        // Skip if slug already exists and we're not forcing regeneration
        if (!empty($this->{$slugAttribute}) && !$this->shouldForceSlugRegeneration($slugAttribute)) {
            return $this->{$slugAttribute};
        }

        if (!is_array($sourceAttributes)) {
            $sourceAttributes = [$sourceAttributes];
        }

        $slugParts = [];
        foreach ($sourceAttributes as $attribute) {
            $value = $this->getSluggableSourceAttributeValue($attribute);
            if (!empty($value)) {
                $slugParts[] = $value;
            }
        }

        if (empty($slugParts)) {
            $slug = $this->generateFallbackSlug();
        } else {
            $slug = implode(' ', $slugParts);
            $slug = $this->processSlugString($slug, $maxLength);
        }

        // Check for reserved words
        if ($this->isReservedSlug($slug)) {
            $slug = $slug . $this->getSeparator() . 'item';
        }

        // Make unique if required
        if ($this->getSlugConfig('unique')) {
            $slug = $this->makeSlugUnique($slugAttribute, $slug);
        }

        return $this->{$slugAttribute} = $slug;
    }

    /**
     * Process the slug string with various transformations.
     *
     * @param string $slug
     * @param int $maxLength
     * @return string
     */
    protected function processSlugString(string $slug, int $maxLength): string
    {
        // Remove HTML tags
        $slug = strip_tags($slug);

        // Convert to ASCII if required
        if ($this->getSlugConfig('ascii_only')) {
            $slug = Str::ascii($slug);
        }

        // Create the slug
        $slug = Str::slug(
            $slug,
            $this->getSeparator(),
            $this->getSlugConfig('language')
        );

        // Truncate to max length
        if ($maxLength > 0 && strlen($slug) > $maxLength) {
            $slug = substr($slug, 0, $maxLength);
            // Remove any trailing separator
            $slug = rtrim($slug, $this->getSeparator());
        }

        return $slug;
    }

    /**
     * Ensures a unique attribute value by appending a counter if necessary.
     *
     * @param string $slugAttribute
     * @param string $slug
     * @return string
     */
    protected function makeSlugUnique(string $slugAttribute, string $slug): string
    {
        $separator = $this->getSeparator();
        $originalSlug = $slug;

        // Build query to check for existing slugs
        $query = $this->buildSlugUniqueQuery($slugAttribute, $slug);

        // Get existing similar slugs
        $existingSlugs = $query->pluck($slugAttribute)->map(function ($item) {
            return strtolower($item);
        })->toArray();

        $slug = strtolower($slug);

        // If no conflicts, return original
        if (!in_array($slug, $existingSlugs)) {
            return $originalSlug;
        }

        // Find the next available number
        $counter = 2;
        $baseSlug = preg_replace('/' . preg_quote($separator) . '[0-9]+$/', '', $slug);

        while (in_array($baseSlug . $separator . $counter, $existingSlugs)) {
            $counter++;
        }

        return $baseSlug . $separator . $counter;
    }

    /**
     * Build query for checking slug uniqueness.
     *
     * @param string $slugAttribute
     * @param string $slug
     * @return Builder
     */
    protected function buildSlugUniqueQuery(string $slugAttribute, string $slug): Builder
    {
        $query = $this->newQuery()
            ->where($slugAttribute, 'LIKE', $slug . '%');

        // Exclude current model if updating
        if ($this->exists) {
            $query->where($this->getKeyName(), '!=', $this->getKey());
        }

        // Include trashed records if specified
        if ($this->getSlugConfig('include_trashed') && method_exists($this, 'withTrashed')) {
            $query->withTrashed();
        }

        return $query;
    }

    /**
     * Get an attribute relation value using dotted notation.
     *
     * @param string $key
     * @return mixed
     */
    protected function getSluggableSourceAttributeValue(string $key): mixed
    {
        if (!str_contains($key, '.')) {
            return $this->getAttribute($key);
        }

        $keyParts = explode('.', $key);
        $value = $this;

        foreach ($keyParts as $part) {
            if (!isset($value[$part])) {
                return null;
            }
            $value = $value[$part];
        }

        return $value;
    }

    /**
     * Generate a fallback slug when source attributes are empty.
     *
     * @return string
     */
    protected function generateFallbackSlug(): string
    {
        return 'item' . $this->getSeparator() . uniqid();
    }

    /**
     * Check if a slug is in the reserved words list.
     *
     * @param string $slug
     * @return bool
     */
    protected function isReservedSlug(string $slug): bool
    {
        $reservedWords = $this->getSlugConfig('reserved_words');
        return in_array(strtolower($slug), array_map('strtolower', $reservedWords));
    }

    /**
     * Determine if slug should be regenerated on update.
     *
     * @return bool
     */
    protected function shouldRegenerateSlugOnUpdate(): bool
    {
        return $this->getSlugConfig('on_update');
    }

    /**
     * Determine if slug regeneration should be forced.
     *
     * @param string $slugAttribute
     * @return bool
     */
    protected function shouldForceSlugRegeneration(string $slugAttribute): bool
    {
        return $this->isDirty($this->getSlugSourceAttributes($slugAttribute)) ||
               empty($this->getOriginal($slugAttribute));
    }

    /**
     * Get source attributes for a specific slug field.
     *
     * @param string $slugAttribute
     * @return array
     */
    protected function getSlugSourceAttributes(string $slugAttribute): array
    {
        $slugs = $this->getSlugs();
        $sourceAttributes = $slugs[$slugAttribute] ?? [];

        return is_array($sourceAttributes) ? $sourceAttributes : [$sourceAttributes];
    }

    /**
     * Find a model by slug.
     *
     * @param string $slug
     * @param array $columns
     * @param string $slugField
     * @return Model|null
     */
    public static function findBySlug(string $slug, array $columns = ['*'], string $slugField = 'slug'): ?Model
    {
        return static::where($slugField, $slug)->first($columns);
    }

    /**
     * Find a model by slug or fail.
     *
     * @param string $slug
     * @param array $columns
     * @param string $slugField
     * @return Model
     */
    public static function findBySlugOrFail(string $slug, array $columns = ['*'], string $slugField = 'slug'): Model
    {
        return static::where($slugField, $slug)->firstOrFail($columns);
    }

    /**
     * Scope to find by slug.
     *
     * @param Builder $query
     * @param string $slug
     * @param string $slugField
     * @return Builder
     */
    public function scopeWhereSlug(Builder $query, string $slug, string $slugField = 'slug'): Builder
    {
        return $query->where($slugField, $slug);
    }

    /**
     * Get the slug separator.
     *
     * @return string
     */
    public function getSeparator(): string
    {
        return $this->getSlugConfig('separator');
    }

    /**
     * Get slug configuration.
     *
     * @param string|null $key
     * @return mixed
     */
    public function getSlugConfig(?string $key = null): mixed
    {
        if ($key) {
            return $this->slugConfig[$key] ?? null;
        }

        return $this->slugConfig;
    }

    /**
     * Get slugs configuration.
     *
     * @return array
     */
    public function getSlugs(): array
    {
        return $this->slugs;
    }

    /**
     * Get the public key value (slug).
     *
     * @param string $slugField
     * @return mixed
     */
    public function getPublicKey(string $slugField = 'slug'): mixed
    {
        return $this->getAttribute($slugField);
    }

    /**
     * Get route key name (useful for route model binding).
     *
     * @return string
     */
    public function getRouteKeyName(): string
    {
        $slugs = $this->getSlugs();
        return array_key_first($slugs) ?? 'slug';
    }

    /**
     * Set slug configuration.
     *
     * @param array $config
     * @return $this
     */
    public function setSlugConfig(array $config): static
    {
        $this->slugConfig = array_merge($this->slugConfig, $config);
        return $this;
    }

    /**
     * Regenerate all slugs for this model.
     *
     * @return $this
     */
    public function regenerateSlugs(): static
    {
        $this->slugAttributes();
        return $this;
    }

    /**
     * Check if the model has a specific slug.
     *
     * @param string $slug
     * @param string $slugField
     * @return bool
     */
    public function hasSlug(string $slug, string $slugField = 'slug'): bool
    {
        return $this->getAttribute($slugField) === $slug;
    }

    /**
     * Get all possible slug variations for debugging.
     *
     * @param string $slugAttribute
     * @param string $baseSlug
     * @param int $limit
     * @return array
     */
    public function getSlugVariations(string $slugAttribute, string $baseSlug, int $limit = 10): array
    {
        $variations = [$baseSlug];
        $separator = $this->getSeparator();

        for ($i = 2; $i <= $limit; $i++) {
            $variations[] = $baseSlug . $separator . $i;
        }

        return $variations;
    }
}
