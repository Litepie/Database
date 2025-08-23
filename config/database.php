<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for the intelligent caching system.
    |
    */
    'cache' => [
        // Default cache TTL in minutes
        'default_ttl' => env('LITEPIE_DB_CACHE_TTL', 60),
        
        // Enable cache tags for better invalidation
        'tags_enabled' => env('LITEPIE_DB_CACHE_TAGS', true),
        
        // Warm up cache on application boot
        'warm_up_on_boot' => env('LITEPIE_DB_CACHE_WARMUP', false),
        
        // Default cache store to use
        'store' => env('LITEPIE_DB_CACHE_STORE', null),
        
        // Cache key prefix
        'prefix' => env('LITEPIE_DB_CACHE_PREFIX', 'litepie_db'),
        
        // Enable query result caching
        'query_cache_enabled' => env('LITEPIE_DB_QUERY_CACHE', true),
        
        // Maximum cache key length
        'max_key_length' => 250,
    ],

    /*
    |--------------------------------------------------------------------------
    | Archivable Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the enhanced archivable functionality.
    |
    */
    'archivable' => [
        // Default reason when archiving without specifying one
        'default_reason' => 'Archived by system',
        
        // Track which user performed the archive operation
        'track_user' => env('LITEPIE_DB_ARCHIVE_TRACK_USER', true),
        
        // Include archived records in search by default
        'include_in_search' => env('LITEPIE_DB_ARCHIVE_IN_SEARCH', false),
        
        // Automatically archive related models
        'cascade_archive' => env('LITEPIE_DB_ARCHIVE_CASCADE', false),
        
        // Column names for archive tracking
        'columns' => [
            'archived_at' => 'archived_at',
            'archived_by' => 'archived_by',
            'archived_reason' => 'archived_reason',
        ],
        
        // Enable archive events
        'events_enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Sluggable Configuration
    |--------------------------------------------------------------------------
    |
    | Global configuration for slug generation.
    |
    */
    'sluggable' => [
        // Default separator for slugs
        'separator' => env('LITEPIE_DB_SLUG_SEPARATOR', '-'),
        
        // Maximum slug length
        'max_length' => env('LITEPIE_DB_SLUG_MAX_LENGTH', 255),
        
        // Language for slug generation
        'language' => env('LITEPIE_DB_SLUG_LANGUAGE', 'en'),
        
        // Reserved words that cannot be used as slugs
        'reserved_words' => [
            'admin', 'api', 'www', 'mail', 'ftp', 'localhost',
            'root', 'index', 'about', 'contact', 'home', 'search',
            'login', 'register', 'dashboard', 'profile', 'settings',
            'help', 'support', 'terms', 'privacy', 'blog', 'news',
        ],
        
        // Make slugs unique by default
        'unique' => env('LITEPIE_DB_SLUG_UNIQUE', true),
        
        // Include soft-deleted records when checking uniqueness
        'include_trashed' => env('LITEPIE_DB_SLUG_INCLUDE_TRASHED', false),
        
        // Regenerate slug on update if source attributes change
        'on_update' => env('LITEPIE_DB_SLUG_ON_UPDATE', false),
        
        // Convert to ASCII only (removes accents, etc.)
        'ascii_only' => env('LITEPIE_DB_SLUG_ASCII_ONLY', false),
        
        // Enable slug history tracking
        'track_history' => env('LITEPIE_DB_SLUG_HISTORY', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Searchable Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for advanced search functionality.
    |
    */
    'searchable' => [
        // Default search strategy: basic, advanced, full_text, fuzzy, weighted, boolean
        'default_strategy' => env('LITEPIE_DB_SEARCH_STRATEGY', 'basic'),
        
        // Enable full-text search (requires MySQL FULLTEXT indexes)
        'enable_full_text' => env('LITEPIE_DB_SEARCH_FULLTEXT', true),
        
        // Fuzzy search threshold (Levenshtein distance)
        'fuzzy_threshold' => env('LITEPIE_DB_SEARCH_FUZZY_THRESHOLD', 2),
        
        // Default search operator for multiple terms
        'default_operator' => env('LITEPIE_DB_SEARCH_OPERATOR', 'AND'),
        
        // Enable search result highlighting
        'highlight_results' => env('LITEPIE_DB_SEARCH_HIGHLIGHT', false),
        
        // Search result cache TTL in minutes
        'cache_ttl' => env('LITEPIE_DB_SEARCH_CACHE_TTL', 30),
        
        // Maximum search terms to process
        'max_terms' => env('LITEPIE_DB_SEARCH_MAX_TERMS', 10),
        
        // Minimum character length for search terms
        'min_term_length' => env('LITEPIE_DB_SEARCH_MIN_LENGTH', 2),
        
        // Enable search analytics
        'enable_analytics' => env('LITEPIE_DB_SEARCH_ANALYTICS', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Macros Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the dynamic model macro system.
    |
    */
    'macros' => [
        // Enable automatic macro registration
        'auto_register' => env('LITEPIE_DB_MACROS_AUTO', true),
        
        // Cache macro definitions
        'cache_enabled' => env('LITEPIE_DB_MACROS_CACHE', true),
        
        // Macro cache TTL in minutes
        'cache_ttl' => env('LITEPIE_DB_MACROS_CACHE_TTL', 1440), // 24 hours
        
        // Enable macro performance monitoring
        'monitor_performance' => env('LITEPIE_DB_MACROS_MONITOR', false),
        
        // Maximum number of macros per model
        'max_per_model' => env('LITEPIE_DB_MACROS_MAX_PER_MODEL', 50),
        
        // Enable macro usage statistics
        'track_usage' => env('LITEPIE_DB_MACROS_TRACK_USAGE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | JSON Cast Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for enhanced JSON casting.
    |
    */
    'json_cast' => [
        // Enable schema validation for JSON fields
        'validate_schema' => env('LITEPIE_DB_JSON_VALIDATE', true),
        
        // Default JSON encoding options
        'encode_options' => JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION,
        
        // Maximum JSON field size in bytes
        'max_size' => env('LITEPIE_DB_JSON_MAX_SIZE', 65535), // 64KB
        
        // Enable JSON field compression
        'compression_enabled' => env('LITEPIE_DB_JSON_COMPRESSION', false),
        
        // Compression threshold in bytes
        'compression_threshold' => env('LITEPIE_DB_JSON_COMPRESSION_THRESHOLD', 1024),
    ],

    /*
    |--------------------------------------------------------------------------
    | Money Cast Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for money field handling.
    |
    */
    'money_cast' => [
        // Default currency
        'default_currency' => env('LITEPIE_DB_MONEY_CURRENCY', 'USD'),
        
        // Default precision (decimal places)
        'default_precision' => env('LITEPIE_DB_MONEY_PRECISION', 2),
        
        // Store as smallest unit (cents) by default
        'store_as_cents' => env('LITEPIE_DB_MONEY_AS_CENTS', true),
        
        // Enable multi-currency support
        'multi_currency' => env('LITEPIE_DB_MONEY_MULTI_CURRENCY', true),
        
        // Currency exchange rate provider
        'exchange_provider' => env('LITEPIE_DB_MONEY_EXCHANGE_PROVIDER', null),
        
        // Cache exchange rates TTL in minutes
        'exchange_cache_ttl' => env('LITEPIE_DB_MONEY_EXCHANGE_CACHE', 60),
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration options for performance optimization.
    |
    */
    'performance' => [
        // Enable query optimization
        'optimize_queries' => env('LITEPIE_DB_OPTIMIZE_QUERIES', true),
        
        // Maximum batch size for bulk operations
        'max_batch_size' => env('LITEPIE_DB_MAX_BATCH_SIZE', 1000),
        
        // Enable connection pooling
        'connection_pooling' => env('LITEPIE_DB_CONNECTION_POOLING', false),
        
        // Query execution timeout in seconds
        'query_timeout' => env('LITEPIE_DB_QUERY_TIMEOUT', 30),
        
        // Enable slow query logging
        'log_slow_queries' => env('LITEPIE_DB_LOG_SLOW_QUERIES', true),
        
        // Slow query threshold in milliseconds
        'slow_query_threshold' => env('LITEPIE_DB_SLOW_QUERY_THRESHOLD', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Debug Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for debugging and development.
    |
    */
    'debug' => [
        // Enable debug mode
        'enabled' => env('LITEPIE_DB_DEBUG', env('APP_DEBUG', false)),
        
        // Log all database queries
        'log_queries' => env('LITEPIE_DB_LOG_QUERIES', false),
        
        // Enable query profiling
        'profile_queries' => env('LITEPIE_DB_PROFILE_QUERIES', false),
        
        // Enable memory usage tracking
        'track_memory' => env('LITEPIE_DB_TRACK_MEMORY', false),
        
        // Maximum number of queries to log
        'max_logged_queries' => env('LITEPIE_DB_MAX_LOGGED_QUERIES', 100),
    ],
];
