# AI Integration Guide - Litepie Database Package

This package is fully AI-ready and can be used through AI assistants like GitHub Copilot, Cursor AI, ChatGPT, Claude, and other AI coding tools.

## For AI Assistants

This package provides 14 powerful Eloquent traits for Laravel applications. All capabilities are documented in machine-readable formats in the `.ai/` directory.

### Key Resources for AI Tools

1. **`.ai/package-capabilities.yaml`** - Structured capability catalog
2. **`.ai/quick-reference.md`** - Fast lookup guide
3. **`.ai/code-examples.md`** - Complete working examples
4. **`.ai/ai-prompts.md`** - Recommended prompts and responses
5. **`.cursorrules`** - Cursor AI specific rules

## Quick Capability Matrix

| Need | Trait | Example Use |
|------|-------|-------------|
| Track changes | Versionable | `$model->rollbackToVersion(5)` |
| Custom fields | Metable | `$model->setMeta('key', 'value')` |
| Multi-language | Translatable | `$model->translate('es', ['title' => '...'])` |
| Search | Searchable | `Model::search('query')->get()` |
| Performance | Cacheable | `Model::query()->cacheFor(60)->get()` |
| SEO URLs | Sluggable | `Model::findBySlug('post-title')` |
| Analytics | Aggregatable | `Model::trend('created_at', 'month')` |
| Large datasets | Paginatable | `Model::cursorPaginate(50)` |
| Export data | Exportable | `Model::query()->exportToCsv([...])` |
| Import data | Importable | `Model::importFromCsv('file.csv')` |
| Manual ordering | Sortable | `$model->moveUp()` |
| Bulk operations | Batchable | `Model::where(...)->batch(100, ...)` |
| Soft archiving | Archivable | `$model->archive('reason')` |
| Performance monitoring | Measurable | `Model::measure(fn() => ...)` |

## Common AI Prompts

### Getting Started
```
"Show me how to add version control to my Post model"
"I need custom product attributes in Laravel"
"How do I add multi-language support to my blog?"
```

### Implementation
```
"Implement a CMS with version control and translations"
"Create an e-commerce product model with custom fields"
"Build an analytics dashboard for order data"
```

### Troubleshooting
```
"Why is my search slow?"
"How do I paginate 1 million records efficiently?"
"What's the best way to cache these queries?"
```

## AI-Ready Features

### 1. Structured Documentation
All traits have:
- Purpose description
- Method signatures
- Use cases
- Working examples
- Performance considerations
- Migration templates

### 2. Complete Examples
Every trait includes:
- 20+ working examples in `examples/` directory
- Real-world use cases
- Best practices
- Common patterns

### 3. Type Safety
Full PHPDoc annotations for:
- IDE autocomplete
- Static analysis
- AI code understanding

### 4. Composability
Traits can be combined:
```php
class Post extends Model
{
    use Versionable, Translatable, Sluggable, Searchable, Cacheable;
}
```

## For Developers Using AI Tools

### Cursor AI
The package includes `.cursorrules` that help Cursor:
- Suggest appropriate traits for your use case
- Provide complete implementations
- Show migration requirements
- Reference example files

### GitHub Copilot
When typing trait names, Copilot will:
- Autocomplete method calls
- Suggest configuration options
- Generate example usage

### ChatGPT / Claude
Ask natural questions like:
- "How do I track changes to my models?"
- "I need custom fields like WordPress"
- "Show me how to implement multi-language content"

The AI will reference the package documentation to provide accurate, working code.

## Installation for AI-Assisted Development

1. Install the package:
```bash
composer require litepie/database
```

2. AI tools will automatically detect:
   - Available traits in `src/Traits/`
   - Example files in `examples/`
   - Documentation in `docs/`
   - AI-specific resources in `.ai/`

3. Start asking your AI assistant for help!

## Common Use Cases

### CMS / Blog
**Prompt:** "Create a blog post model with versioning and translations"

**AI Response:** Complete model with Versionable, Translatable, Sluggable, Searchable, Cacheable traits + migrations + controller examples.

### E-commerce
**Prompt:** "Add custom attributes to my product model"

**AI Response:** Implementation using Metable trait + query examples + real-world patterns.

### Analytics
**Prompt:** "Build a sales dashboard with trends and comparisons"

**AI Response:** Order model with Aggregatable trait + dashboard controller + caching strategy.

### User Management
**Prompt:** "Store user preferences and settings"

**AI Response:** User model with Metable trait + preference management + examples.

## Example AI Conversation

```
You: I need to track changes to my Article model and rollback if needed

AI: I'll help you implement version control using the Versionable trait from 
the Litepie Database package.

1. Add the trait to your model:

use Litepie\Database\Traits\Versionable;

class Article extends Model
{
    use Versionable;
    
    protected int $maxVersions = 20;
    protected array $versionableExclude = ['views', 'updated_at'];
}

2. Run this migration:

Schema::create('model_versions', function (Blueprint $table) {
    $table->id();
    $table->morphs('versionable');
    $table->integer('version_number');
    $table->json('data');
    $table->string('reason')->nullable();
    $table->timestamps();
});

3. Usage:

// Auto-versioned on updates
$article->update(['title' => 'New Title']);

// Manual version
$article->createVersion('Major update', auth()->user());

// Rollback
$article->rollbackToVersion(5);

// View history
$history = $article->getVersionHistory();

See examples/versionable_example.php for 20 more examples!
```

## AI Training Data

This package includes:
- ✅ Machine-readable capability catalog (YAML)
- ✅ Quick reference guides (Markdown)
- ✅ 100+ working code examples
- ✅ Complete API documentation
- ✅ Common patterns and best practices
- ✅ Performance guidelines
- ✅ Migration templates
- ✅ Use case scenarios

## Contributing AI Documentation

When adding new features, please update:
1. `.ai/package-capabilities.yaml` - Add trait entry
2. `.ai/quick-reference.md` - Add quick reference
3. `.ai/code-examples.md` - Add working example
4. `.ai/ai-prompts.md` - Add recommended prompts
5. Example files in `examples/`

## Support

AI tools should reference:
- Latest documentation at `.ai/` directory
- Working examples in `examples/` directory
- PHPDoc annotations in trait files
- README.md for overview

## License

MIT License - AI tools are free to learn from and reference this package.
