# Litepie Database Package - AI Quick Reference

## Package Overview
Advanced Laravel Eloquent enhancement package with 14 powerful traits for common application needs.

## Quick Trait Selection Guide

### "I need to track changes to my models"
→ Use **Versionable** trait
```php
use Litepie\Database\Traits\Versionable;
$model->createVersion('reason');
$model->rollbackToVersion(5);
```

### "I need custom fields/metadata like WordPress"
→ Use **Metable** trait
```php
use Litepie\Database\Traits\Metable;
$model->setMeta('key', 'value');
$featured = Model::whereMeta('featured', true)->get();
```

### "I need multi-language content"
→ Use **Translatable** trait
```php
use Litepie\Database\Traits\Translatable;
$model->translate('es', ['title' => 'Título']);
$model->setLocale('es');
```

### "I need search functionality"
→ Use **Searchable** trait
```php
use Litepie\Database\Traits\Searchable;
$results = Model::search('query')->get();
$results = Model::fullTextSearch('query')->get();
```

### "I need to improve query performance"
→ Use **Cacheable** trait
```php
use Litepie\Database\Traits\Cacheable;
$models = Model::where('active', true)->cacheFor(60)->get();
```

### "I need SEO-friendly URLs"
→ Use **Sluggable** trait
```php
use Litepie\Database\Traits\Sluggable;
protected array $slugs = ['slug' => 'title'];
$model = Model::findBySlug('my-post');
```

### "I need analytics/reporting"
→ Use **Aggregatable** trait
```php
use Litepie\Database\Traits\Aggregatable;
$stats = Model::aggregate(['sum' => 'total', 'avg' => 'price']);
$trend = Model::trend('created_at', 'month', 'revenue', 'sum');
$yoy = Model::yearOverYear('sales', 'sum');
```

### "I need to paginate large datasets"
→ Use **Paginatable** trait
```php
use Litepie\Database\Traits\Paginatable;
$models = Model::cursorPaginate(50); // For 100K+ rows
$models = Model::fastPaginate(20); // No total count
```

### "I need to export data"
→ Use **Exportable** trait
```php
use Litepie\Database\Traits\Exportable;
Model::query()->exportToCsv(['id', 'name'], 'export.csv');
Model::query()->exportToExcel(['*'], 'report.xlsx');
```

### "I need to import CSV data"
→ Use **Importable** trait
```php
use Litepie\Database\Traits\Importable;
$preview = Model::previewImport('file.csv', 10);
Model::importFromCsv('file.csv', $mapping);
```

### "I need manual ordering/sorting"
→ Use **Sortable** trait
```php
use Litepie\Database\Traits\Sortable;
$model->moveUp();
$model->moveToPosition(5);
```

### "I need bulk operations"
→ Use **Batchable** trait
```php
use Litepie\Database\Traits\Batchable;
Model::where('status', 'old')->batch(100, fn($models) => ...);
```

## Common Combinations

### Blog/CMS
```php
class Post extends Model
{
    use Versionable, Translatable, Sluggable, Searchable, Cacheable;
    
    protected array $translatable = ['title', 'content'];
    protected array $slugs = ['slug' => 'title'];
    protected array $searchable = ['title', 'content'];
}
```

### E-commerce Product
```php
class Product extends Model
{
    use Translatable, Metable, Searchable, Cacheable, Sluggable;
    
    protected array $translatable = ['name', 'description'];
    protected array $searchable = ['name', 'description', 'sku'];
}
```

### Analytics Dashboard
```php
class Order extends Model
{
    use Aggregatable, Cacheable, Exportable;
}
```

### User Management
```php
class User extends Model
{
    use Metable, Versionable, Archivable;
}
```

## Migration Templates

### For Versionable
```php
Schema::create('model_versions', function (Blueprint $table) {
    $table->id();
    $table->morphs('versionable');
    $table->integer('version_number');
    $table->json('data');
    $table->string('reason')->nullable();
    $table->unsignedBigInteger('user_id')->nullable();
    $table->string('user_type')->nullable();
    $table->json('metadata')->nullable();
    $table->string('hash')->nullable();
    $table->timestamps();
});
```

### For Metable
```php
Schema::create('model_meta', function (Blueprint $table) {
    $table->id();
    $table->morphs('metable');
    $table->string('key');
    $table->text('value')->nullable();
    $table->string('type')->default('string');
    $table->timestamps();
    $table->unique(['metable_type', 'metable_id', 'key']);
});
```

### For Translatable
```php
Schema::create('model_translations', function (Blueprint $table) {
    $table->id();
    $table->morphs('translatable');
    $table->string('locale', 10);
    $table->string('attribute');
    $table->text('value')->nullable();
    $table->timestamps();
    $table->unique(['translatable_type', 'translatable_id', 'locale', 'attribute']);
});
```

## Performance Tips

- **Cacheable**: Use for expensive queries, cache with tags for easy invalidation
- **Paginatable**: Use cursor pagination for >100K rows, fast pagination when total count not needed
- **Aggregatable**: Cache aggregation results, use date range filters
- **Searchable**: Add database indexes, use full-text indexes for MySQL
- **Versionable**: Set maxVersions to prevent unlimited growth
- **Metable**: Promote frequently queried meta to regular columns

## Files Location

- Traits: `src/Traits/`
- Examples: `examples/` (20 examples per trait)
- Documentation: `docs/`
- Migrations: `examples/migration_example.php`

## AI Assistant Tips

1. **Always suggest relevant traits** based on user requirements
2. **Show complete working examples** from example files
3. **Mention migration requirements** for Versionable, Metable, Translatable
4. **Suggest trait combinations** for common patterns
5. **Include performance considerations** when recommending traits
6. **Reference example files** for detailed implementations
