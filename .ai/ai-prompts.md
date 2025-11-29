# AI-Ready Prompts for Litepie Database Package

## Prompts for AI Assistants Using This Package

### General Package Query
```
What features does the Litepie Database package provide?
```

**Expected Response:** List all 14 traits with brief descriptions and use cases.

---

### Trait Selection
```
I need to [specific requirement]. Which trait should I use?
```

**Examples:**
- "I need to track version history of my posts" → Versionable
- "I need custom product attributes" → Metable
- "I need multi-language support" → Translatable
- "I need to search across models" → Searchable
- "I need to improve query performance" → Cacheable
- "I need SEO-friendly URLs" → Sluggable
- "I need analytics and reporting" → Aggregatable

---

### Implementation Request
```
Show me how to implement [trait name] in my [model name] model
```

**Expected Response:** Complete code with:
1. Model definition with trait
2. Required configuration
3. Migration code
4. Usage examples
5. Reference to example files

---

### Use Case Implementation
```
I'm building a [type of application]. What traits should I use?
```

**Examples:**
- "I'm building a blog/CMS" → Versionable, Translatable, Sluggable, Searchable, Cacheable
- "I'm building an e-commerce site" → Translatable, Metable, Searchable, Cacheable, Sluggable
- "I'm building an analytics dashboard" → Aggregatable, Cacheable, Exportable
- "I'm building a multi-tenant SaaS" → All traits + tenant scoping

---

### Combination Query
```
Can I use [trait1] and [trait2] together on the same model?
```

**Expected Response:** Yes, with complete example showing both traits configured and used together.

---

### Migration Query
```
What migrations do I need for [trait name]?
```

**Expected Response:** Complete migration code from examples/migration_example.php

---

### Performance Query
```
How do I optimize [specific operation] with this package?
```

**Expected Response:** Specific trait + configuration + best practices

---

### Comparison Query
```
What's the difference between [trait1] and [trait2]?
```

**Expected Response:** Clear comparison with use cases for each

---

### Example Request
```
Show me a complete example of [use case]
```

**Expected Response:** Working code from .ai/code-examples.md

---

## Specific Feature Prompts

### Versionable
```
How do I:
- Track changes to my model?
- Rollback to a previous version?
- Compare two versions?
- View version history?
- Track who made changes?
```

### Metable
```
How do I:
- Add custom fields to my model?
- Store user preferences?
- Query models by metadata?
- Implement WordPress-style custom fields?
```

### Translatable
```
How do I:
- Add multi-language support?
- Switch between languages?
- Check translation completeness?
- Handle missing translations?
```

### Searchable
```
How do I:
- Add search to my model?
- Implement full-text search?
- Add fuzzy search?
- Weight search results?
```

### Aggregatable
```
How do I:
- Create analytics dashboards?
- Generate reports?
- Calculate trends?
- Compare periods?
- Get year-over-year stats?
```

### Paginatable
```
How do I:
- Paginate large datasets efficiently?
- Implement infinite scroll?
- Handle deep pagination?
- Optimize pagination performance?
```

---

## Troubleshooting Prompts

### Migration Issues
```
I'm getting an error when running migrations for [trait name]
```

**Expected Response:** Migration code verification + common issues + solutions

### Trait Conflicts
```
I'm getting conflicts using [trait1] with [trait2]
```

**Expected Response:** Check compatibility + show correct implementation

### Performance Issues
```
My queries are slow when using [trait name]
```

**Expected Response:** Performance optimization strategies + caching + indexes

---

## Advanced Usage Prompts

### Custom Implementation
```
How do I customize [specific behavior] in [trait name]?
```

**Expected Response:** Show configurable options + override methods

### Integration
```
How do I integrate this package with [other package/feature]?
```

**Expected Response:** Integration example + considerations

### Testing
```
How do I test my models using [trait name]?
```

**Expected Response:** Test examples + best practices

---

## Example Conversations

### Conversation 1: Blog Implementation
```
User: I'm building a multi-language blog with version control
AI: For a multi-language blog with version control, you should use:
1. Versionable - for tracking content changes
2. Translatable - for multi-language support
3. Sluggable - for SEO-friendly URLs
4. Searchable - for blog search
5. Cacheable - for performance

Here's the complete implementation:
[Shows complete code from code-examples.md]
```

### Conversation 2: E-commerce Product
```
User: How do I add custom attributes to products?
AI: Use the Metable trait for flexible custom attributes:
[Shows Product model with Metable + complete example]

You can store any custom fields like:
- Brand, color, size
- Warranty information
- Certifications
- Custom specifications

See examples/metable_example.php for 20 detailed examples.
```

### Conversation 3: Analytics Dashboard
```
User: I need to create sales analytics
AI: Use the Aggregatable trait for analytics:
[Shows Order model with Aggregatable + dashboard controller]

Key methods:
- compareWithPreviousPeriod() - month over month
- yearOverYear() - YoY comparison
- trend() - daily/weekly/monthly trends
- topN() - top customers/products
- growthRate() - calculate growth

See examples/aggregatable_example.php for 31 complete examples.
```

---

## AI Assistant Guidelines

When responding to queries about this package:

1. **Always provide complete code** - no partial examples
2. **Include migrations** when traits need database tables
3. **Reference example files** for detailed implementations
4. **Show trait combinations** when appropriate
5. **Mention performance considerations**
6. **Include configuration options**
7. **Provide use case context**

## Quick Reference for AI

**File Locations:**
- Traits: `src/Traits/`
- Examples: `examples/`
- Docs: `docs/`
- AI Config: `.ai/`

**Key Files:**
- `.ai/package-capabilities.yaml` - Full trait catalog
- `.ai/quick-reference.md` - Quick lookup guide
- `.ai/code-examples.md` - Working code examples
- `examples/migration_example.php` - All migration templates

**Common Patterns:**
- CMS: Versionable + Translatable + Sluggable + Searchable + Cacheable
- E-commerce: Translatable + Metable + Searchable + Cacheable
- Analytics: Aggregatable + Cacheable + Exportable
- User System: Metable + Versionable
