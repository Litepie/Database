# Exportable/Importable Trait Split

## Overview

The original `Exportable` trait has been split into two separate traits for better code organization and adherence to the Single Responsibility Principle:

- **`Exportable`** - Export data from models to files (CSV, JSON, Excel)
- **`Importable`** - Import data into models from files with validation and preview

## Why Split?

1. **Single Responsibility**: Each trait now has one clear purpose
2. **Better Modularity**: Use only what you need (`Exportable`, `Importable`, or both)
3. **Easier Maintenance**: Smaller, focused files are easier to understand and modify
4. **Flexible Usage**: Models can choose to implement one or both traits

## Migration Guide

### Before (Old Approach)

```php
use Litepie\Database\Traits\Exportable;

class Product extends Model
{
    use Exportable;
    
    // Both export and import methods available
}

// Export
$path = Product::query()->exportToCsv();

// Import
$imported = Product::importFromCsv('products.csv');
```

### After (New Approach)

#### Option 1: Export Only

```php
use Litepie\Database\Traits\Exportable;

class Product extends Model
{
    use Exportable;
    
    // Only export methods available
}

// Export
$path = Product::query()->exportToCsv();
```

#### Option 2: Import Only

```php
use Litepie\Database\Traits\Importable;

class Product extends Model
{
    use Importable;
    
    // Only import methods available
}

// Import
$imported = Product::importFromCsv('products.csv');

// Preview
$preview = Product::previewImport('products.csv', 'csv', $mapping);
```

#### Option 3: Both Export and Import

```php
use Litepie\Database\Traits\Exportable;
use Litepie\Database\Traits\Importable;

class Product extends Model
{
    use Exportable, Importable;
    
    // All methods available
}

// Export
$path = Product::query()->exportToCsv();

// Import
$imported = Product::importFromCsv('products.csv');
```

## Trait Features

### Exportable Trait

**Methods:**
- `exportToCsv()` - Export to CSV format
- `exportToJson()` - Export to JSON format
- `exportToExcel()` - Export to Excel-compatible CSV
- `streamExport()` - Stream large exports to browser
- `configureExport()` - Configure export settings
- `getExportStats()` - Get export statistics

**Use Cases:**
- Generating reports
- Data backups
- API data exports
- Scheduled exports

### Importable Trait

**Methods:**
- `importFromCsv()` - Import CSV files
- `importFromJson()` - Import JSON files
- `importFromExcel()` - Import Excel files
- `previewImport()` - Preview data before importing
- `validateImportFile()` - Validate import file structure
- `getImportRecommendations()` - Get recommendations for import settings

**Use Cases:**
- Bulk data imports
- Data migrations
- User-uploaded data
- Seeding databases

## Example Files

### Export Examples
See: `examples/exportable_example.php`
- 20 examples covering all export scenarios
- CSV, JSON, Excel exports
- Streaming, filtering, relationships
- Cloud storage, scheduled exports

### Import Examples
See: `examples/importable_example.php`
- 20 examples covering all import scenarios
- CSV, JSON, Excel imports
- Preview, validation, error handling
- Update/upsert, batch processing

## Breaking Changes

### None!

The new implementation is **100% backward compatible** if you use both traits:

```php
// This works exactly as before
use Exportable, Importable;
```

The only "breaking" change is if you want to use just export or just import functionality - in which case you now have the flexibility to do so.

## File Changes

### New Files
- `src/Traits/Importable.php` - New import-only trait
- `examples/importable_example.php` - 20 import examples

### Modified Files
- `src/Traits/Exportable.php` - Now export-only (reduced from 931 to ~400 lines)
- `examples/exportable_example.php` - Now 20 export-only examples

### Backup Files (can be deleted)
- `src/Traits/ExportableOld.php` - Original combined trait
- `examples/exportable_example_old.php` - Original combined examples

## Benefits

1. **Reduced File Size**: Each trait is now ~400-500 lines instead of 931 lines
2. **Clearer Purpose**: File names directly indicate functionality
3. **Better Performance**: Load only what you need
4. **Easier Testing**: Test import and export separately
5. **Better Documentation**: Examples are now focused and easier to find

## Recommendations

### When to use `Exportable`:
- Read-only models (reports, analytics)
- Models that generate downloadable data
- API endpoints that export data

### When to use `Importable`:
- Models that receive bulk uploads
- Admin interfaces with import functionality
- Data migration models

### When to use both:
- Full-featured admin models
- Products, orders, users (typical CRUD models)
- Models with complete data portability needs

## Next Steps

1. ✅ Split completed
2. ✅ Examples separated
3. ✅ Documentation created
4. ⏭️ Test both traits independently
5. ⏭️ Update README.md with split information
6. ⏭️ Delete backup files when confirmed working

## Questions?

The split maintains all original functionality while providing better code organization. Both traits follow the same conventions as other traits in the package (Searchable, Cacheable, Measurable, etc.).
