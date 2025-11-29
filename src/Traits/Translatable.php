<?php

namespace Litepie\Database\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;

/**
 * Translatable trait for multi-language content support.
 * 
 * This trait allows you to:
 * - Store translations for specific model attributes
 * - Switch between locales seamlessly
 * - Fall back to default locale when translation is missing
 * - Query models by translated content
 */
trait Translatable
{
    /**
     * Current locale for this instance.
     *
     * @var string|null
     */
    protected ?string $currentLocale = null;

    /**
     * Cached translations for this instance.
     *
     * @var Collection|null
     */
    protected ?Collection $translationsCache = null;

    /**
     * Attributes that are translatable.
     *
     * @var array
     */
    protected array $translatable = [];

    /**
     * Default locale fallback.
     *
     * @var bool
     */
    protected bool $useTranslatableFallback = true;

    /**
     * Boot the translatable trait.
     *
     * @return void
     */
    public static function bootTranslatable(): void
    {
        static::deleted(function (Model $model) {
            if (method_exists($model, 'isForceDeleting') && $model->isForceDeleting()) {
                $model->translations()->delete();
            }
        });
    }

    /**
     * Initialize the translatable trait.
     *
     * @return void
     */
    public function initializeTranslatable(): void
    {
        $this->currentLocale = $this->currentLocale ?? App::getLocale();
    }

    /**
     * Get all translations for this model.
     *
     * @return MorphMany
     */
    public function translations(): MorphMany
    {
        return $this->morphMany(
            config('litepie-database.translatable.model', ModelTranslation::class),
            'translatable'
        );
    }

    /**
     * Translate specific attributes for a locale.
     *
     * @param string $locale
     * @param array $attributes
     * @return Model
     */
    public function translate(string $locale, array $attributes): Model
    {
        foreach ($attributes as $key => $value) {
            if (!$this->isTranslatableAttribute($key)) {
                continue;
            }

            $this->translations()->updateOrCreate(
                [
                    'locale' => $locale,
                    'attribute' => $key,
                ],
                ['value' => $value]
            );
        }

        $this->clearTranslationsCache();

        return $this;
    }

    /**
     * Get translation for a specific attribute and locale.
     *
     * @param string $attribute
     * @param string|null $locale
     * @param mixed $fallback
     * @return mixed
     */
    public function getTranslation(string $attribute, ?string $locale = null, mixed $fallback = null): mixed
    {
        $locale = $locale ?? $this->getLocale();

        $translation = $this->getTranslationsCollection()
            ->where('locale', $locale)
            ->where('attribute', $attribute)
            ->first();

        if ($translation) {
            return $translation->value;
        }

        // Try fallback locale
        if ($this->useTranslatableFallback && $locale !== $this->getDefaultLocale()) {
            return $this->getTranslation($attribute, $this->getDefaultLocale(), $fallback);
        }

        // Return original attribute or fallback
        return $fallback ?? $this->getOriginalAttribute($attribute);
    }

    /**
     * Get all translations for a specific attribute.
     *
     * @param string $attribute
     * @return array
     */
    public function getTranslations(string $attribute): array
    {
        $translations = $this->getTranslationsCollection()
            ->where('attribute', $attribute);

        $result = [];
        foreach ($translations as $translation) {
            $result[$translation->locale] = $translation->value;
        }

        return $result;
    }

    /**
     * Get all translations for current locale.
     *
     * @param string|null $locale
     * @return array
     */
    public function getAllTranslations(?string $locale = null): array
    {
        $locale = $locale ?? $this->getLocale();

        $translations = $this->getTranslationsCollection()
            ->where('locale', $locale);

        $result = [];
        foreach ($translations as $translation) {
            $result[$translation->attribute] = $translation->value;
        }

        return $result;
    }

    /**
     * Check if translation exists for attribute.
     *
     * @param string $attribute
     * @param string|null $locale
     * @return bool
     */
    public function hasTranslation(string $attribute, ?string $locale = null): bool
    {
        $locale = $locale ?? $this->getLocale();

        return $this->getTranslationsCollection()
            ->where('locale', $locale)
            ->where('attribute', $attribute)
            ->isNotEmpty();
    }

    /**
     * Delete translation for attribute.
     *
     * @param string $attribute
     * @param string|null $locale
     * @return bool
     */
    public function deleteTranslation(string $attribute, ?string $locale = null): bool
    {
        $locale = $locale ?? $this->getLocale();

        $result = $this->translations()
            ->where('locale', $locale)
            ->where('attribute', $attribute)
            ->delete();

        $this->clearTranslationsCache();

        return $result > 0;
    }

    /**
     * Delete all translations for a locale.
     *
     * @param string $locale
     * @return int
     */
    public function deleteTranslationsForLocale(string $locale): int
    {
        $result = $this->translations()
            ->where('locale', $locale)
            ->delete();

        $this->clearTranslationsCache();

        return $result;
    }

    /**
     * Delete all translations.
     *
     * @return bool
     */
    public function deleteAllTranslations(): bool
    {
        $result = $this->translations()->delete();
        $this->clearTranslationsCache();

        return $result > 0;
    }

    /**
     * Set the current locale for this instance.
     *
     * @param string $locale
     * @return $this
     */
    public function setLocale(string $locale): static
    {
        $this->currentLocale = $locale;
        return $this;
    }

    /**
     * Get the current locale.
     *
     * @return string
     */
    public function getLocale(): string
    {
        return $this->currentLocale ?? App::getLocale();
    }

    /**
     * Get the default locale.
     *
     * @return string
     */
    public function getDefaultLocale(): string
    {
        return config('app.locale', 'en');
    }

    /**
     * Get available locales for this model.
     *
     * @return array
     */
    public function getAvailableLocales(): array
    {
        return $this->translations()
            ->distinct('locale')
            ->pluck('locale')
            ->toArray();
    }

    /**
     * Check if model has translations.
     *
     * @param string|null $locale
     * @return bool
     */
    public function hasTranslations(?string $locale = null): bool
    {
        $query = $this->translations();

        if ($locale) {
            $query->where('locale', $locale);
        }

        return $query->exists();
    }

    /**
     * Copy translations to another model.
     *
     * @param Model $target
     * @param array $locales
     * @return $this
     */
    public function copyTranslationsTo(Model $target, array $locales = []): static
    {
        $query = $this->translations();

        if (!empty($locales)) {
            $query->whereIn('locale', $locales);
        }

        $translations = $query->get();

        foreach ($translations as $translation) {
            $target->translate($translation->locale, [
                $translation->attribute => $translation->value
            ]);
        }

        return $this;
    }

    /**
     * Duplicate translations from one locale to another.
     *
     * @param string $fromLocale
     * @param string $toLocale
     * @param bool $overwrite
     * @return $this
     */
    public function duplicateTranslations(string $fromLocale, string $toLocale, bool $overwrite = false): static
    {
        $translations = $this->translations()
            ->where('locale', $fromLocale)
            ->get();

        foreach ($translations as $translation) {
            if ($overwrite || !$this->hasTranslation($translation->attribute, $toLocale)) {
                $this->translate($toLocale, [
                    $translation->attribute => $translation->value
                ]);
            }
        }

        return $this;
    }

    /**
     * Get translation completion percentage.
     *
     * @param string|null $locale
     * @return float
     */
    public function getTranslationCompleteness(?string $locale = null): float
    {
        $locale = $locale ?? $this->getLocale();
        $totalAttributes = count($this->getTranslatableAttributes());

        if ($totalAttributes === 0) {
            return 100.0;
        }

        $translatedCount = $this->translations()
            ->where('locale', $locale)
            ->whereIn('attribute', $this->getTranslatableAttributes())
            ->count();

        return ($translatedCount / $totalAttributes) * 100;
    }

    /**
     * Get missing translations for a locale.
     *
     * @param string|null $locale
     * @return array
     */
    public function getMissingTranslations(?string $locale = null): array
    {
        $locale = $locale ?? $this->getLocale();
        
        $translatedAttributes = $this->translations()
            ->where('locale', $locale)
            ->pluck('attribute')
            ->toArray();

        return array_diff($this->getTranslatableAttributes(), $translatedAttributes);
    }

    /**
     * Scope to filter by translated attribute.
     *
     * @param Builder $query
     * @param string $attribute
     * @param mixed $value
     * @param string|null $locale
     * @return Builder
     */
    public function scopeWhereTranslation(Builder $query, string $attribute, mixed $value, ?string $locale = null): Builder
    {
        $locale = $locale ?? App::getLocale();

        return $query->whereHas('translations', function ($q) use ($attribute, $value, $locale) {
            $q->where('locale', $locale)
              ->where('attribute', $attribute)
              ->where('value', $value);
        });
    }

    /**
     * Scope to filter by locale.
     *
     * @param Builder $query
     * @param string $locale
     * @return Builder
     */
    public function scopeWhereLocale(Builder $query, string $locale): Builder
    {
        return $query->whereHas('translations', function ($q) use ($locale) {
            $q->where('locale', $locale);
        });
    }

    /**
     * Scope to get models with translations.
     *
     * @param Builder $query
     * @param string|null $locale
     * @return Builder
     */
    public function scopeWithTranslations(Builder $query, ?string $locale = null): Builder
    {
        $query->with(['translations' => function ($q) use ($locale) {
            if ($locale) {
                $q->where('locale', $locale);
            }
        }]);

        return $query;
    }

    /**
     * Scope to get models translated for specific locale.
     *
     * @param Builder $query
     * @param string|null $locale
     * @return Builder
     */
    public function scopeTranslatedIn(Builder $query, ?string $locale = null): Builder
    {
        $locale = $locale ?? App::getLocale();

        return $query->whereHas('translations', function ($q) use ($locale) {
            $q->where('locale', $locale);
        });
    }

    /**
     * Get translatable attributes.
     *
     * @return array
     */
    public function getTranslatableAttributes(): array
    {
        return $this->translatable;
    }

    /**
     * Check if attribute is translatable.
     *
     * @param string $attribute
     * @return bool
     */
    public function isTranslatableAttribute(string $attribute): bool
    {
        return in_array($attribute, $this->getTranslatableAttributes());
    }

    /**
     * Get original attribute value (non-translated).
     *
     * @param string $attribute
     * @return mixed
     */
    protected function getOriginalAttribute(string $attribute): mixed
    {
        return $this->getAttributes()[$attribute] ?? null;
    }

    /**
     * Get translations collection with caching.
     *
     * @return Collection
     */
    protected function getTranslationsCollection(): Collection
    {
        if ($this->translationsCache === null) {
            $this->translationsCache = $this->translations()->get();
        }

        return $this->translationsCache;
    }

    /**
     * Clear translations cache.
     *
     * @return void
     */
    protected function clearTranslationsCache(): void
    {
        $this->translationsCache = null;
    }

    /**
     * Override attribute accessor to return translated value.
     *
     * @param string $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        if ($this->isTranslatableAttribute($key)) {
            return $this->getTranslation($key);
        }

        return parent::getAttribute($key);
    }

    /**
     * Override attribute mutator to set translation.
     *
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    public function setAttribute($key, $value)
    {
        // If setting a translatable attribute, store it as translation
        if ($this->isTranslatableAttribute($key) && $this->exists) {
            $this->translate($this->getLocale(), [$key => $value]);
            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * Convert model to array with translations.
     *
     * @return array
     */
    public function toArray()
    {
        $array = parent::toArray();

        // Replace translatable attributes with translated values
        foreach ($this->getTranslatableAttributes() as $attribute) {
            if ($this->hasTranslation($attribute)) {
                $array[$attribute] = $this->getTranslation($attribute);
            }
        }

        return $array;
    }
}

/**
 * Model Translation class for storing translations.
 * 
 * Migration:
 * Schema::create('model_translations', function (Blueprint $table) {
 *     $table->id();
 *     $table->morphs('translatable');
 *     $table->string('locale', 10);
 *     $table->string('attribute');
 *     $table->text('value')->nullable();
 *     $table->timestamps();
 *     
 *     $table->unique(['translatable_type', 'translatable_id', 'locale', 'attribute'], 'translations_unique');
 *     $table->index(['translatable_type', 'translatable_id']);
 *     $table->index('locale');
 * });
 */
class ModelTranslation extends Model
{
    protected $table = 'model_translations';

    protected $fillable = [
        'translatable_type',
        'translatable_id',
        'locale',
        'attribute',
        'value',
    ];

    /**
     * Get the owning translatable model.
     */
    public function translatable()
    {
        return $this->morphTo();
    }
}
