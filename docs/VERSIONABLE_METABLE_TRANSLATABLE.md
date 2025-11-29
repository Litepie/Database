# Versionable, Metable, and Translatable Traits

This document provides an overview of the three new traits added to the Litepie Database package.

## Overview

Three powerful traits have been added to enhance Eloquent models:

1. **Versionable** - Track model version history and rollback changes
2. **Metable** - Flexible key-value metadata storage (WordPress-style)
3. **Translatable** - Multi-language content support

## 1. Versionable Trait

### Purpose
Track complete version history of your models, allowing you to view past states, compare versions, and rollback changes.

### Key Features
- ✅ Automatic versioning on model updates
- ✅ Manual version creation with reasons
- ✅ Rollback to any previous version
- ✅ Compare any two versions
- ✅ User tracking (who made changes)
- ✅ Metadata support for additional context
- ✅ Data integrity verification with SHA-256 hashes
- ✅ Auto-pruning old versions (configurable limit)

### Quick Start

```php
use Litepie\Database\Traits\Versionable;

class Post extends Model
{
    use Versionable;
    
    protected int $maxVersions = 20; // Keep last 20 versions
    protected array $versionableExclude = ['views', 'updated_at'];
}

// Usage
$post->createVersion('Major content update', auth()->user());
$post->rollbackToVersion(5);
$differences = $post->compareVersions(3, 5);
$history = $post->getVersionHistory();
```

### Use Cases
- Content management systems (editorial workflow)
- Audit trails for compliance
- Price change tracking for e-commerce
- Document versioning
- Undo/redo functionality

---

## 2. Metable Trait

### Purpose
Store arbitrary key-value metadata for any model, similar to WordPress post_meta functionality.

### Key Features
- ✅ Type-safe storage (string, int, float, bool, array, object)
- ✅ Automatic type detection and preservation
- ✅ Increment/decrement numeric values
- ✅ Append/remove from array values
- ✅ Query models by meta values
- ✅ Order by meta values
- ✅ Copy/merge meta between models
- ✅ Magic accessors (`$model->meta_key`)

### Quick Start

```php
use Litepie\Database\Traits\Metable;

class Post extends Model
{
    use Metable;
}

// Usage
$post->setMeta('featured', true);
$post->setMeta('view_count', 100);
$post->incrementMeta('view_count');

$featured = $post->getMeta('featured');
$allMeta = $post->getAllMeta();

// Query by meta
$posts = Post::whereMeta('featured', true)->get();
```

### Use Cases
- User preferences and settings
- Custom product attributes
- Feature flags per model
- Analytics tracking
- Page builder configurations
- A/B testing data
- SEO metadata

---

## 3. Translatable Trait

### Purpose
Support multi-language content for your models with automatic locale switching and fallback support.

### Key Features
- ✅ Translate specific attributes to multiple languages
- ✅ Automatic locale detection from App::getLocale()
- ✅ Fallback to default locale when translation missing
- ✅ Query models by translated content
- ✅ Translation completeness tracking
- ✅ Copy translations between models
- ✅ Duplicate translations across locales
- ✅ API-friendly responses

### Quick Start

```php
use Litepie\Database\Traits\Translatable;

class Post extends Model
{
    use Translatable;
    
    protected array $translatable = ['title', 'content', 'excerpt'];
}

// Usage
$post->translate('es', [
    'title' => 'Título en Español',
    'content' => 'Contenido en español',
]);

$post->setLocale('es');
echo $post->title; // Returns Spanish translation

$allSpanish = $post->getAllTranslations('es');
$completion = $post->getTranslationCompleteness('es');
```

### Use Cases
- Multi-language blogs and websites
- E-commerce product translations
- International documentation
- Localized marketing content
- Multi-region applications

---

## Installation

### 1. Run Migrations

Create and run migration for the three support tables:

```bash
php artisan make:migration create_versionable_metable_translatable_tables
```

Use the migration code from `examples/migration_example.php`.

### 2. Add Traits to Models

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Litepie\Database\Traits\Versionable;
use Litepie\Database\Traits\Metable;
use Litepie\Database\Traits\Translatable;

class Post extends Model
{
    use Versionable, Metable, Translatable;
    
    // Versionable config
    protected int $maxVersions = 50;
    protected array $versionableExclude = ['views', 'updated_at'];
    
    // Translatable config
    protected array $translatable = ['title', 'content', 'excerpt'];
}
```

### 3. Use in Your Application

All three traits can be used together on the same model:

```php
$post = Post::find(1);

// Version tracking
$post->title = 'Updated Title';
$post->save(); // Auto-versioned
$post->createVersion('Editorial changes', auth()->user());

// Metadata
$post->setMeta('featured', true);
$post->setMeta('reading_time', 5);

// Translations
$post->translate('es', [
    'title' => 'Título Actualizado',
    'content' => 'Contenido en español',
]);

// Query with all traits
$posts = Post::whereMeta('featured', true)
    ->translatedIn('es')
    ->withTranslations()
    ->get();
```

---

## Database Tables

### model_versions
```sql
- id
- versionable_type
- versionable_id
- version_number
- data (JSON)
- reason
- user_id
- user_type
- metadata (JSON)
- hash (SHA-256)
- created_at, updated_at
```

### model_meta
```sql
- id
- metable_type
- metable_id
- key
- value
- type
- created_at, updated_at
```

### model_translations
```sql
- id
- translatable_type
- translatable_id
- locale
- attribute
- value
- created_at, updated_at
```

---

## Examples

Comprehensive examples are available in:

- `examples/versionable_example.php` - 20 examples covering all Versionable features
- `examples/metable_example.php` - 20 examples covering all Metable features
- `examples/translatable_example.php` - 20 examples covering all Translatable features

---

## Performance Considerations

### Versionable
- Set `$maxVersions` to prevent unlimited growth
- Exclude frequently updated columns from versioning
- Consider async version creation for high-traffic models

### Metable
- Meta values are cached per instance
- Use indexes on `metable_type`, `metable_id`, and `key`
- For frequently queried meta, consider promoting to regular columns

### Translatable
- Translations are cached per instance
- Eager load translations: `->withTranslations('es')`
- Consider separate translation tables for very large text fields

---

## Compatibility

- **Laravel**: 10.x, 11.x, 12.x
- **PHP**: 8.2, 8.3, 8.4
- **Database**: MySQL 5.7+, PostgreSQL 10+, SQLite 3.8+

---

## Combined Usage Example

```php
class Article extends Model
{
    use Versionable, Metable, Translatable, Searchable, Cacheable;
    
    protected array $translatable = ['title', 'content', 'summary'];
    protected int $maxVersions = 30;
}

// Create article
$article = Article::create([
    'title' => 'Getting Started with Laravel',
    'content' => '...',
    'status' => 'draft',
]);

// Add translations
$article->translate('es', [
    'title' => 'Comenzando con Laravel',
    'content' => '...',
]);

// Add metadata
$article->setMultipleMeta([
    'reading_time' => 5,
    'difficulty' => 'beginner',
    'category' => 'tutorial',
]);

// Publish with version
$article->status = 'published';
$article->createVersion('Published to production', auth()->user());
$article->save();

// Query
$articles = Article::whereMeta('difficulty', 'beginner')
    ->translatedIn('es')
    ->search('Laravel')
    ->cacheFor(60)
    ->get();
```

---

## Credits

These traits were developed as part of the Litepie Database package by Renfos Technologies.

For more information, see the main package documentation.
