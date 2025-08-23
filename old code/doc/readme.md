# Litepie Database

Litepie Database is a Laravel package that provides advanced Eloquent model traits, scopes, macros, and custom casts for rapid application development. It is designed for modularity, reusability, and clean code in Laravel and Litepie-based projects.

## Features

- **Traits** for archiving, searching, sorting, sluggable URLs, encryption, caching, and more
- **Bulk actions** for update, delete, archive, and restore
- **Advanced search** with full-text support
- **Attribute encryption** for sensitive data
- **Custom casts** (JSON, Money, etc.)
- **Relationship macros** (hasManyDeep, belongsToThrough)
- **Query caching** for performance
- **Service provider** for easy integration

## Installation

```bash
composer require litepie/database
```

## Usage

### Register Service Provider (if not auto-discovered)

Add to `config/app.php`:

```php
Litepie\Database\DatabaseServiceProvider::class,
```

### Traits

Add traits to your Eloquent models as needed:

```php
use Litepie\Database\Traits\Archivable;
use Litepie\Database\Traits\BulkActions;
use Litepie\Database\Traits\Encryptable;
use Litepie\Database\Traits\FullTextSearchable;
use Litepie\Database\Traits\Cacheable;

class Post extends Model
{
    use Archivable, BulkActions, Encryptable, FullTextSearchable, Cacheable;

    protected $encryptable = ['secret_field'];
}
```

### Bulk Actions

```php
Post::bulkUpdate([1,2,3], ['status' => 'published']);
Post::bulkDelete([4,5,6]);
```

### Full-Text Search

```php
Post::fullTextSearch('search term', ['title', 'body'])->get();
```

### Attribute Encryption

```php
$post = new Post;
$post->secret_field = 'Sensitive Data'; // Will be encrypted automatically
$post->save();
```

### Custom Casts

```php
use Litepie\Database\Casts\JsonCast;

class Post extends Model
{
    protected $casts = [
        'settings' => JsonCast::class,
    ];
}
```

### Query Caching

```php
Post::where('status', 'published')->cacheFor(60)->get();
```

### Relationship Macros

```php
// Example usage in a model or query
Post::hasManyDeep(Comment::class, User::class, 'user_id', 'post_id');
```

## Contributing

Pull requests are welcome! For major changes, please open an issue first to discuss what you would like to change.

## License

MIT
