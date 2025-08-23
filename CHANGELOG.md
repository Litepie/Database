# Changelog

All notable changes to `litepie/database` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.1.0] - 2025-08-23

### Added
- Laravel 12.x compatibility
- PHP 8.4 support
- Enhanced GitHub Actions workflows with matrix testing
- Extended testing matrix to include all supported Laravel and PHP versions

### Changed
- Updated dependency constraints to support Laravel 12.x
- Updated PHPUnit to support v11.x
- Updated Orchestra Testbench to support v10.x for Laravel 12

## [2.0.0] - 2024-01-XX

### Added
- Complete rewrite with enhanced functionality
- **Enhanced Archivable Trait**
  - Archive with reason and user tracking
  - Advanced query methods (recentlyArchived, archivedBetween, etc.)
  - Bulk archive operations
  - Archive events and observers
  - Support for archived_by and archived_reason columns

- **Advanced Searchable Trait**
  - Multiple search strategies (basic, advanced, full-text, fuzzy, weighted, boolean)
  - Relationship field searching with dot notation
  - Search term parsing with operators (AND, OR, NOT)
  - Fuzzy search with Levenshtein distance
  - Weighted search with relevance scoring
  - Full-text search with MySQL FULLTEXT indexes

- **Intelligent Caching Trait**
  - Smart cache invalidation
  - Cache with tags for better organization
  - Cache warm-up strategies
  - Multiple cache stores support
  - Automatic cache clearing on model changes
  - Cache statistics and monitoring

- **Enhanced Sluggable Trait**
  - Multiple slug fields support
  - Advanced slug configuration options
  - Reserved words protection
  - Slug history tracking
  - Route model binding integration
  - Multi-language slug support
  - ASCII-only option for international content

- **Advanced JSON Cast**
  - Schema validation for JSON fields
  - Default value support
  - Associative array vs object casting options
  - Custom encoding options
  - Error handling with detailed messages

- **Money Cast**
  - Multi-currency support
  - Configurable precision
  - Smallest unit storage (cents)
  - Automatic formatting
  - Cryptocurrency support
  - Exchange rate integration ready

- **Enhanced Model Macro System**
  - Dynamic macro registration
  - Model-specific and global macros
  - Macro usage statistics
  - Performance monitoring
  - Reflection-based parameter inspection

- **Advanced Query Scopes**
  - Enhanced ArchivableScope with new methods
  - SearchScope for complex search queries
  - CacheScope for query caching
  - FilterScope for advanced filtering

- **Migration Macros**
  - `archivedAt()` for archive timestamp columns
  - `auditColumns()` for complete audit trail
  - `status()` for enum status columns
  - `slug()` for indexed slug columns
  - `seoColumns()` for SEO metadata
  - `position()` for ordering columns
  - `uuidPrimary()` for UUID primary keys
  - `jsonWithIndex()` for JSON with virtual indexes

- **Bulk Operations**
  - Enhanced bulk update/delete operations
  - Batch processing with callbacks
  - Memory-efficient chunk processing
  - Transaction support for bulk operations

- **Advanced Filtering**
  - Filter macro with operator support
  - Range filtering
  - Relationship filtering
  - Array value filtering

### Enhanced
- **Performance Optimizations**
  - Query optimization
  - Connection pooling support
  - Slow query logging
  - Memory usage tracking

- **Developer Experience**
  - Comprehensive documentation with examples
  - Type hints throughout
  - Better error messages
  - PHPDoc improvements
  - IDE auto-completion support

- **Configuration System**
  - Environment variable support
  - Granular configuration options
  - Debug and performance settings
  - Cache configuration options

### Breaking Changes
- Minimum PHP version raised to 8.2
- Laravel 10.0+ required
- Some method signatures changed for type safety
- Configuration structure updated

### Deprecated
- Old macro registration methods (use ModelMacroManager instead)
- Basic caching methods (use enhanced Cacheable trait)

### Security
- Input validation for all casts
- SQL injection protection in search methods
- Schema validation for JSON fields

## [1.0.0] - 2023-XX-XX

### Added
- Basic Archivable trait
- Simple search functionality
- Basic caching support
- Slug generation
- JSON and Money casts
- Model macro system

### Initial Features
- Archive/unarchive models
- Basic search across model fields
- Simple query result caching
- Automatic slug generation from title
- JSON field casting
- Money field handling
- Blueprint macros for migrations

[Unreleased]: https://github.com/litepie/database/compare/v2.0.0...HEAD
[2.0.0]: https://github.com/litepie/database/compare/v1.0.0...v2.0.0
[1.0.0]: https://github.com/litepie/database/releases/tag/v1.0.0
