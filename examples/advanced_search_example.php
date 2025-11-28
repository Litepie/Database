<?php

/**
 * Advanced Search Examples
 * 
 * This file demonstrates how to use the scopeAdvancedSearch method
 * from the Searchable trait with various operators and search patterns.
 */

namespace App\Examples;

use App\Models\Article;
use App\Models\Product;
use App\Models\User;

class AdvancedSearchExamples
{
    /**
     * Example 1: Basic Advanced Search with AND operator
     * 
     * Searches for articles containing both "Laravel" AND "framework"
     */
    public function basicAndSearch()
    {
        // Search for articles with both terms
        $articles = Article::advancedSearch('Laravel AND framework')->get();
        
        // Alternative: Multiple terms default to AND
        $articles = Article::advancedSearch('Laravel framework')->get();
        
        return $articles;
    }

    /**
     * Example 2: Advanced Search with OR operator
     * 
     * Searches for articles containing either "Laravel" OR "PHP"
     */
    public function orSearch()
    {
        $articles = Article::advancedSearch('Laravel OR PHP')->get();
        
        // All articles that mention either Laravel or PHP
        return $articles;
    }

    /**
     * Example 3: Combined AND/OR operators
     * 
     * Complex search combining multiple operators
     */
    public function combinedOperators()
    {
        // Find articles about (Laravel OR Symfony) AND framework
        $articles = Article::advancedSearch('Laravel OR Symfony AND framework')->get();
        
        // Another example: (PHP OR JavaScript) AND tutorial
        $tutorials = Article::advancedSearch('PHP OR JavaScript AND tutorial')->get();
        
        return $tutorials;
    }

    /**
     * Example 4: Exact Match Search
     * 
     * Use quotes for exact phrase matching
     */
    public function exactMatchSearch()
    {
        // Search for exact phrase "Laravel framework"
        $articles = Article::advancedSearch('"Laravel framework"')->get();
        
        // Combine exact match with other terms
        $articles = Article::advancedSearch('"web development" AND PHP')->get();
        
        return $articles;
    }

    /**
     * Example 5: Exclusion Search (NOT operator)
     * 
     * Exclude terms from search using minus sign (-)
     */
    public function exclusionSearch()
    {
        // Find Laravel articles but exclude WordPress
        $articles = Article::advancedSearch('Laravel -WordPress')->get();
        
        // PHP tutorials excluding beginner content
        $advanced = Article::advancedSearch('PHP tutorial -beginner')->get();
        
        // Framework articles excluding Symfony and CodeIgniter
        $articles = Article::advancedSearch('framework -Symfony -CodeIgniter')->get();
        
        return $articles;
    }

    /**
     * Example 6: Complex Search with Multiple Operators
     * 
     * Combining AND, OR, quotes, and exclusions
     */
    public function complexSearch()
    {
        // Search: (Laravel OR PHP) AND tutorial, but NOT beginner
        $articles = Article::advancedSearch('Laravel OR PHP AND tutorial -beginner')->get();
        
        // Exact phrase with exclusions
        $articles = Article::advancedSearch('"web development" AND modern -legacy')->get();
        
        // Multiple exclusions with OR
        $articles = Article::advancedSearch('framework OR library -WordPress -jQuery -old')->get();
        
        return $articles;
    }

    /**
     * Example 7: Search Specific Fields
     * 
     * Limit advanced search to specific columns
     */
    public function searchSpecificFields()
    {
        // Search only in title and excerpt
        $articles = Article::advancedSearch('Laravel AND framework', ['title', 'excerpt'])->get();
        
        // Search in relationship fields
        $articles = Article::advancedSearch('John AND Doe', ['author.name', 'author.bio'])->get();
        
        return $articles;
    }

    /**
     * Example 8: Product Search Example
     * 
     * Real-world e-commerce search scenario
     */
    public function productSearch()
    {
        // Find laptops excluding refurbished
        $products = Product::advancedSearch('laptop -refurbished')->get();
        
        // Search for gaming laptops or desktops
        $products = Product::advancedSearch('gaming AND laptop OR desktop')->get();
        
        // Exact brand with exclusions
        $products = Product::advancedSearch('"MacBook Pro" -2019 -2020')->get();
        
        // Multiple categories
        $products = Product::advancedSearch('phone OR tablet AND samsung')->get();
        
        return $products;
    }

    /**
     * Example 9: User Search with Multiple Criteria
     * 
     * Search users with complex conditions
     */
    public function userSearch()
    {
        // Find developers but exclude juniors
        $users = User::advancedSearch('developer -junior')->get();
        
        // Search for specific skills
        $users = User::advancedSearch('Laravel AND PHP OR JavaScript', ['skills', 'bio'])->get();
        
        // Exact job title with location
        $users = User::advancedSearch('"Senior Developer" AND remote')->get();
        
        return $users;
    }

    /**
     * Example 10: Advanced Search with Pagination
     * 
     * Combine advanced search with pagination
     */
    public function searchWithPagination()
    {
        $articles = Article::advancedSearch('Laravel OR PHP AND tutorial')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        return $articles;
    }

    /**
     * Example 11: Advanced Search with Additional Filters
     * 
     * Combine with other query methods
     */
    public function searchWithFilters()
    {
        // Search with date range filter
        $articles = Article::advancedSearch('Laravel AND framework')
            ->where('status', 'published')
            ->whereBetween('created_at', [now()->subMonth(), now()])
            ->orderBy('views', 'desc')
            ->get();
        
        // Search with relationship filters
        $articles = Article::advancedSearch('PHP tutorial')
            ->whereHas('category', function ($query) {
                $query->where('name', 'Programming');
            })
            ->with('author')
            ->get();
        
        return $articles;
    }

    /**
     * Example 12: Search from Request Parameters
     * 
     * Use in controller with user input
     */
    public function searchFromRequest()
    {
        // In a controller method
        $searchTerm = request('q'); // User input: "Laravel AND framework -beginner"
        $fields = request('fields', []); // Optional specific fields
        
        $results = Article::advancedSearch($searchTerm, $fields ?: null)
            ->when(request('category'), function ($query, $category) {
                $query->where('category_id', $category);
            })
            ->when(request('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy(request('sort_by', 'created_at'), request('sort_dir', 'desc'))
            ->paginate(request('per_page', 15));
        
        return $results;
    }

    /**
     * Example 13: Case-Insensitive Search
     * 
     * Advanced search is case-insensitive by default
     */
    public function caseInsensitiveSearch()
    {
        // These all return the same results
        $articles1 = Article::advancedSearch('LARAVEL AND FRAMEWORK')->get();
        $articles2 = Article::advancedSearch('laravel and framework')->get();
        $articles3 = Article::advancedSearch('Laravel AND Framework')->get();
        
        return $articles1; // Same as articles2 and articles3
    }

    /**
     * Example 14: Chaining Multiple Search Methods
     * 
     * Combine different search strategies
     */
    public function chainedSearch()
    {
        // Start with advanced search, then add basic search on other fields
        $articles = Article::advancedSearch('Laravel AND framework', ['title', 'content'])
            ->orWhere(function ($query) {
                $query->search('beginner', ['tags']);
            })
            ->get();
        
        return $articles;
    }

    /**
     * Example 15: Performance Optimization with Caching
     * 
     * Cache expensive search queries
     */
    public function cachedSearch()
    {
        $searchTerm = 'Laravel AND framework -beginner';
        
        // Cache search results for 30 minutes
        $articles = Article::advancedSearch($searchTerm)
            ->where('status', 'published')
            ->cacheFor(30, 'advanced_search_' . md5($searchTerm))
            ->get();
        
        return $articles;
    }

    /**
     * Example 16: Count Search Results
     * 
     * Get count without retrieving all records
     */
    public function countSearchResults()
    {
        $count = Article::advancedSearch('Laravel AND framework')->count();
        
        // With filters
        $publishedCount = Article::advancedSearch('PHP tutorial')
            ->where('status', 'published')
            ->count();
        
        return [
            'total' => $count,
            'published' => $publishedCount,
        ];
    }

    /**
     * Example 17: Search with Relationship Eager Loading
     * 
     * Optimize queries with eager loading
     */
    public function searchWithEagerLoading()
    {
        $articles = Article::advancedSearch('Laravel framework')
            ->with(['author', 'category', 'tags'])
            ->get();
        
        // Access relationships without N+1 queries
        foreach ($articles as $article) {
            echo $article->author->name;
            echo $article->category->name;
        }
        
        return $articles;
    }

    /**
     * Example 18: Search and Group Results
     * 
     * Group search results by category or other fields
     */
    public function searchAndGroup()
    {
        $articles = Article::advancedSearch('tutorial')
            ->where('status', 'published')
            ->get()
            ->groupBy('category_id');
        
        return $articles;
    }

    /**
     * Example 19: Search with Score/Relevance
     * 
     * Combine with weighted search for relevance
     */
    public function searchWithRelevance()
    {
        // First filter with advanced search, then apply relevance scoring
        $articles = Article::advancedSearch('Laravel framework')
            ->get()
            ->sortByDesc(function ($article) {
                // Custom relevance calculation
                $score = 0;
                if (str_contains(strtolower($article->title), 'laravel')) $score += 10;
                if (str_contains(strtolower($article->title), 'framework')) $score += 5;
                return $score;
            });
        
        return $articles;
    }

    /**
     * Example 20: API Response Example
     * 
     * Format advanced search results for API
     */
    public function apiSearchResponse()
    {
        $searchTerm = request('q');
        
        $results = Article::advancedSearch($searchTerm)
            ->where('status', 'published')
            ->select(['id', 'title', 'slug', 'excerpt', 'created_at'])
            ->paginate(20);
        
        return response()->json([
            'success' => true,
            'query' => $searchTerm,
            'data' => $results->items(),
            'meta' => [
                'total' => $results->total(),
                'per_page' => $results->perPage(),
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
            ],
        ]);
    }

    /**
     * Example 21: Filter with Comparison Operators (>, <, >=, <=, between)
     * 
     * Use the filter() macro for advanced field filtering with operators
     */
    public function filterWithOperators()
    {
        // Greater than - Find articles with more than 100 views
        $articles = Article::filter([
            'views:>' => 100,
        ])->get();

        // Less than - Articles with less than 50 comments
        $articles = Article::filter([
            'comments:<' => 50,
        ])->get();

        // Greater than or equal
        $articles = Article::filter([
            'rating:>=' => 4.5,
        ])->get();

        // Less than or equal
        $articles = Article::filter([
            'price:<=' => 99.99,
        ])->get();

        // Between - Articles created in date range
        $articles = Article::filter([
            'created_at:between' => ['2024-01-01', '2024-12-31'],
        ])->get();

        // Multiple operators combined
        $articles = Article::filter([
            'views:>' => 100,
            'rating:>=' => 4.0,
            'price:<=' => 199.99,
            'status' => 'published',
        ])->get();

        return $articles;
    }

    /**
     * Example 22: Filter from Query String Parameters
     * 
     * Parse query string and apply filters with operators
     */
    public function filterFromQueryString()
    {
        // URL: /products?price:>=50&price:<=200&rating:>=4&status=active
        
        $filters = [];
        
        foreach (request()->all() as $key => $value) {
            if (!empty($value)) {
                $filters[$key] = $value;
            }
        }
        
        $products = Product::filter($filters)->get();
        
        return $products;
    }

    /**
     * Example 23: Advanced Filter with Search Combined
     * 
     * Combine text search with field filters
     */
    public function combinedSearchAndFilter()
    {
        // Search for "laptop" with price and rating filters
        $products = Product::search('laptop')
            ->filter([
                'price:>=' => 500,
                'price:<=' => 2000,
                'rating:>' => 4.0,
                'in_stock' => true,
            ])
            ->orderBy('price', 'asc')
            ->get();

        return $products;
    }

    /**
     * Example 24: E-commerce Filter Example
     * 
     * Real-world product filtering scenario
     */
    public function ecommerceFiltering()
    {
        // URL: /products?category=laptops&price:>=1000&price:<=3000&rating:>=4.5&brand[]=Apple&brand[]=Dell
        
        $products = Product::filter([
            'category' => request('category'),           // Exact match
            'price:>=' => request('price_min'),          // Greater or equal
            'price:<=' => request('price_max'),          // Less or equal
            'rating:>=' => request('min_rating'),        // Minimum rating
            'brand' => request('brand'),                 // Array of brands (IN query)
            'created_at:between' => [
                request('date_from'), 
                request('date_to')
            ],
        ])->get();

        return $products;
    }

    /**
     * Example 25: Request Handler for Complex Filtering
     * 
     * Complete example with query string parsing
     */
    public function handleComplexFiltering()
    {
        // Example query string:
        // ?q=laptop&price:>=500&price:<=2000&rating:>=4&views:>1000&status=active&brand[]=Apple&brand[]=Dell
        
        $searchTerm = request('q');
        $filters = $this->parseFilterParams(request()->except(['q', 'page', 'per_page']));
        
        $query = Product::query();
        
        // Apply text search if provided
        if ($searchTerm) {
            $query->search($searchTerm);
        }
        
        // Apply filters
        if (!empty($filters)) {
            $query->filter($filters);
        }
        
        $results = $query->paginate(request('per_page', 15));
        
        return $results;
    }

    /**
     * Parse filter parameters from request
     * 
     * @param array $params
     * @return array
     */
    private function parseFilterParams(array $params): array
    {
        $filters = [];
        
        foreach ($params as $key => $value) {
            // Skip empty values
            if (is_null($value) || $value === '') {
                continue;
            }
            
            // Handle between operator specially
            if (str_contains($key, ':between') && is_array($value) && count($value) === 2) {
                $filters[$key] = $value;
            }
            // Handle array values (IN queries)
            elseif (is_array($value)) {
                $filters[$key] = $value;
            }
            // Handle other operators
            else {
                $filters[$key] = $value;
            }
        }
        
        return $filters;
    }

    /**
     * Example 26: Date Range Filtering
     * 
     * Filter records by date ranges
     */
    public function dateRangeFiltering()
    {
        // Articles from last 30 days
        $articles = Article::filter([
            'created_at:>=' => now()->subDays(30)->toDateString(),
        ])->get();

        // Articles between specific dates
        $articles = Article::filter([
            'created_at:between' => ['2024-01-01', '2024-12-31'],
        ])->get();

        // Published after a specific date
        $articles = Article::filter([
            'published_at:>' => '2024-06-01',
        ])->get();

        return $articles;
    }

    /**
     * Example 27: Numeric Range Filtering
     * 
     * Filter by numeric ranges
     */
    public function numericRangeFiltering()
    {
        // Products in price range
        $products = Product::filter([
            'price:>=' => 100,
            'price:<=' => 500,
        ])->get();

        // High-rated products
        $products = Product::filter([
            'rating:>=' => 4.5,
            'reviews_count:>' => 50,
        ])->get();

        // Articles with view count range
        $articles = Article::filter([
            'views:>=' => 1000,
            'views:<=' => 10000,
        ])->get();

        return $products;
    }

    /**
     * Example 28: Complete Controller Example with Filters (OLD WAY)
     * 
     * Full manual implementation in a controller
     */
    public function controllerFilterExample()
    {
        // This would be in a controller method
        
        $filters = [];
        
        // Text search
        if (request('q')) {
            $query = Product::search(request('q'));
        } else {
            $query = Product::query();
        }
        
        // Build filters from request
        if (request('price_min')) {
            $filters['price:>='] = request('price_min');
        }
        
        if (request('price_max')) {
            $filters['price:<='] = request('price_max');
        }
        
        if (request('rating_min')) {
            $filters['rating:>='] = request('rating_min');
        }
        
        if (request('views_min')) {
            $filters['views:>'] = request('views_min');
        }
        
        if (request('category')) {
            $filters['category_id'] = request('category');
        }
        
        if (request('brands')) {
            $filters['brand'] = request('brands'); // Array for IN query
        }
        
        if (request('date_from') && request('date_to')) {
            $filters['created_at:between'] = [
                request('date_from'),
                request('date_to'),
            ];
        }
        
        // Apply filters
        if (!empty($filters)) {
            $query->filter($filters);
        }
        
        // Sort and paginate
        $results = $query
            ->orderBy(request('sort_by', 'created_at'), request('sort_dir', 'desc'))
            ->paginate(request('per_page', 15));
        
        return $results;
    }

    /**
     * Example 29: Automatic Filters with applyFilters() - SIMPLE!
     * 
     * Let the trait handle everything automatically
     */
    public function automaticFiltering()
    {
        // URL: /products?q=laptop&price:>=500&price:<=2000&rating:>=4.5&brand[]=Apple&brand[]=Dell
        
        // ONE LINE - automatically applies all filters for searchable fields!
        $products = Product::applyFilters()->paginate(15);
        
        return $products;
    }

    /**
     * Example 30: Smart Filter with Advanced Search
     * 
     * Automatically detects search operators and applies appropriate search method
     */
    public function smartFiltering()
    {
        // URL: /products?q=gaming+laptop+AND+high+performance&price:>=1000&rating:>=4
        
        // Automatically uses advancedSearch() if operators detected, otherwise search()
        $products = Product::smartFilter()->paginate(15);
        
        return $products;
    }

    /**
     * Example 31: Filter with Custom Parameters
     * 
     * Pass custom parameter array instead of using request()
     */
    public function filterWithCustomParams()
    {
        $params = [
            'q' => 'laptop',
            'price:>=' => 500,
            'price:<=' => 2000,
            'rating:>=' => 4.5,
            'brand' => ['Apple', 'Dell'],
            'in_stock' => true,
        ];
        
        $products = Product::applyFilters($params)->get();
        
        return $products;
    }

    /**
     * Example 32: Strict Mode - Only Allow Defined Fields
     * 
     * Prevent filtering on fields not in $searchable or $fillable
     */
    public function strictFiltering()
    {
        // Only filters fields that are in the model's $searchable or $fillable arrays
        $products = Product::applyFilters(null, ['strict' => true])->paginate(15);
        
        return $products;
    }

    /**
     * Example 33: Exclude Specific Parameters
     * 
     * Exclude certain parameters from being used as filters
     */
    public function filterWithExclusions()
    {
        $products = Product::applyFilters(null, [
            'exclude' => ['page', 'per_page', 'sort_by', 'sort_dir', 'token', 'api_key']
        ])->paginate(15);
        
        return $products;
    }

    /**
     * Example 34: Filter and Paginate in One Call
     * 
     * Combine filtering, sorting, and pagination
     */
    public function filterAndPaginate()
    {
        // Automatically handles: search, filters, sorting, and pagination
        // URL: /products?q=laptop&price:>=500&rating:>=4&sort_by=price&sort_dir=asc&per_page=24
        
        $products = Product::filterAndPaginate();
        
        return $products;
    }

    /**
     * Example 35: Simple Controller with Auto-Filtering
     * 
     * The simplest possible controller implementation
     */
    public function simpleController()
    {
        // That's it! One line handles everything:
        // - Text search (q parameter)
        // - All field filters (price:>=, rating:>, etc.)
        // - Array filters (brand[]=Apple&brand[]=Dell)
        // - Date ranges (created_at:between)
        // - Sorting (sort_by, sort_dir)
        // - Pagination (per_page)
        
        return Product::filterAndPaginate();
    }

    /**
     * Example 36: Controller with Additional Constraints
     * 
     * Auto-filtering + custom query constraints
     */
    public function filterWithConstraints()
    {
        $products = Product::where('status', 'active')
            ->where('published', true)
            ->smartFilter()
            ->with(['category', 'brand'])
            ->paginate(request('per_page', 15));
        
        return $products;
    }

    /**
     * Example 37: API Response with Auto-Filtering
     * 
     * Perfect for API endpoints
     */
    public function apiFilterResponse()
    {
        $results = Product::filterAndPaginate(null, request('per_page', 20));
        
        return response()->json([
            'success' => true,
            'data' => $results->items(),
            'meta' => [
                'total' => $results->total(),
                'per_page' => $results->perPage(),
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
            ],
            'filters_applied' => request()->except(['page', 'per_page']),
        ]);
    }

    /**
     * Example 38: Combining Multiple Features
     * 
     * Auto-filtering with caching and eager loading
     */
    public function advancedAutoFiltering()
    {
        $products = Product::smartFilter()
            ->with(['category', 'brand', 'reviews'])
            ->when(request('featured'), function ($query) {
                $query->where('featured', true);
            })
            ->cacheFor(30)
            ->paginate(15);
        
        return $products;
    }

    /**
     * Example 39: Extended Operators - IN and NOT IN
     * 
     * Filter using IN and NOT IN operators
     */
    public function inOperators()
    {
        // IN operator - match any value in array
        $products = Product::filter([
            'category_id:in' => [1, 2, 3, 4],
        ])->get();

        // Alternative syntax
        $products = Product::filter([
            'brand:in' => ['Apple', 'Samsung', 'Dell'],
        ])->get();

        // NOT IN operator - exclude values
        $products = Product::filter([
            'status:not_in' => ['draft', 'archived', 'deleted'],
        ])->get();

        // Combine with other filters
        $products = Product::filter([
            'category_id:in' => [1, 2, 3],
            'price:>=' => 100,
            'rating:>=' => 4.0,
            'status:not_in' => ['draft', 'deleted'],
        ])->get();

        return $products;
    }

    /**
     * Example 40: String Pattern Operators
     * 
     * Use LIKE, starts_with, ends_with, contains
     */
    public function stringPatternOperators()
    {
        // LIKE operator
        $articles = Article::filter([
            'title:like' => '%Laravel%',
        ])->get();

        // NOT LIKE operator
        $articles = Article::filter([
            'title:not_like' => '%WordPress%',
        ])->get();

        // Starts with
        $users = User::filter([
            'name:starts_with' => 'John',
        ])->get();

        // Ends with
        $products = Product::filter([
            'sku:ends_with' => '-XL',
        ])->get();

        // Contains (shorthand for %value%)
        $articles = Article::filter([
            'content:contains' => 'Laravel',
        ])->get();

        // Does not contain
        $articles = Article::filter([
            'content:not_contains' => 'deprecated',
        ])->get();

        return $articles;
    }

    /**
     * Example 41: NULL and NOT NULL Operators
     * 
     * Filter by null values
     */
    public function nullOperators()
    {
        // IS NULL
        $users = User::filter([
            'email_verified_at:null' => true,
        ])->get();

        // Alternative syntax
        $users = User::filter([
            'deleted_at:is_null' => true,
        ])->get();

        // IS NOT NULL
        $users = User::filter([
            'email_verified_at:not_null' => true,
        ])->get();

        // Combine with other filters
        $products = Product::filter([
            'discount_price:not_null' => true,
            'in_stock' => true,
            'status' => 'active',
        ])->get();

        return $users;
    }

    /**
     * Example 42: BETWEEN and NOT BETWEEN
     * 
     * Range filtering with between operators
     */
    public function betweenOperators()
    {
        // BETWEEN for dates
        $orders = Order::filter([
            'created_at:between' => ['2024-01-01', '2024-12-31'],
        ])->get();

        // BETWEEN for numbers
        $products = Product::filter([
            'price:between' => [100, 500],
        ])->get();

        // NOT BETWEEN
        $articles = Article::filter([
            'views:not_between' => [0, 10],
        ])->get();

        // Combine multiple range filters
        $products = Product::filter([
            'price:between' => [100, 1000],
            'rating:between' => [4.0, 5.0],
            'created_at:between' => ['2024-01-01', '2024-12-31'],
        ])->get();

        return $orders;
    }

    /**
     * Example 43: Not Equal Operators
     * 
     * Filter excluding specific values
     */
    public function notEqualOperators()
    {
        // Not equal (!=)
        $products = Product::filter([
            'status:!=' => 'draft',
        ])->get();

        // Alternative syntax
        $products = Product::filter([
            'status:not' => 'deleted',
        ])->get();

        // Multiple not equal conditions
        $articles = Article::filter([
            'status:!=' => 'draft',
            'author_id:!=' => 1,
        ])->get();

        return $products;
    }

    /**
     * Example 44: Combined Advanced Filters
     * 
     * Real-world example combining all operator types
     */
    public function complexAdvancedFilters()
    {
        // E-commerce product search with all operators
        // URL: /products?q=laptop&price:between[]=500&price:between[]=2000&category_id:in[]=1&category_id:in[]=2&brand:not_in[]=Refurbished&name:contains=Pro&status:!=draft&discount:not_null=true
        
        $products = Product::filter([
            // Text patterns
            'name:contains' => 'Pro',
            'description:not_contains' => 'refurbished',
            'sku:starts_with' => 'LAP-',
            
            // Ranges
            'price:between' => [500, 2000],
            'rating:between' => [4.0, 5.0],
            
            // IN/NOT IN
            'category_id:in' => [1, 2, 3],
            'brand:not_in' => ['Unknown', 'Generic'],
            
            // NULL checks
            'discount_price:not_null' => true,
            'deleted_at:null' => true,
            
            // Comparisons
            'stock:>' => 0,
            'views:>=' => 100,
            'status:!=' => 'draft',
            
            // Exact matches
            'active' => true,
            'featured' => true,
        ])->get();

        return $products;
    }

    /**
     * Example 45: URL Query String with Extended Operators
     * 
     * How to use extended operators in URLs
     */
    public function urlQueryExamples()
    {
        // These URL patterns are automatically parsed by applyFilters() and smartFilter()
        
        // URL Examples:
        // 
        // IN operator:
        // ?category_id:in[]=1&category_id:in[]=2&category_id:in[]=3
        // 
        // NOT IN operator:
        // ?status:not_in[]=draft&status:not_in[]=deleted
        // 
        // LIKE patterns:
        // ?name:starts_with=John
        // ?email:ends_with=@gmail.com
        // ?title:contains=Laravel
        // ?content:not_contains=deprecated
        // 
        // NULL checks:
        // ?email_verified_at:null=true
        // ?deleted_at:not_null=true
        // 
        // BETWEEN:
        // ?price:between[]=100&price:between[]=500
        // ?created_at:between[]=2024-01-01&created_at:between[]=2024-12-31
        // 
        // NOT BETWEEN:
        // ?views:not_between[]=0&views:not_between[]=10
        // 
        // NOT EQUAL:
        // ?status:!=draft
        // ?author_id:not=1
        
        // All automatically handled by:
        $results = Product::filterAndPaginate();
        
        return $results;
    }

    /**
     * Example 46: One-Line Advanced Filtering
     * 
     * Complete filtering with all operators in one call
     */
    public function oneLineAdvancedFilter()
    {
        // URL: /api/products?q=gaming&category_id:in[]=1&category_id:in[]=2&price:between[]=500&price:between[]=2000&brand:not_in[]=Generic&name:contains=Pro&status:!=draft&discount:not_null=true&sort_by=price&per_page=20
        
        // ONE LINE handles everything!
        return Product::filterAndPaginate();
        
        // Automatically applies:
        // ✓ Text search (q=gaming)
        // ✓ IN filter (category_id:in)
        // ✓ BETWEEN filter (price:between)
        // ✓ NOT IN filter (brand:not_in)
        // ✓ CONTAINS filter (name:contains)
        // ✓ NOT EQUAL filter (status:!=)
        // ✓ NULL check (discount:not_null)
        // ✓ Sorting (sort_by, sort_dir)
        // ✓ Pagination (per_page)
    }
}

/**
 * MODEL SETUP EXAMPLE
 * 
 * Here's how to configure your model to use advancedSearch:
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Litepie\Database\Traits\Searchable;

class Article extends Model
{
    use Searchable;

    // Define searchable fields
    protected array $searchable = [
        'title',
        'content',
        'excerpt',
        'tags',
        'author.name',      // Search in relationships
        'category.name',
    ];

    // Optional: Define search weights for relevance
    protected array $searchWeights = [
        'title' => 10,
        'excerpt' => 8,
        'content' => 5,
        'tags' => 3,
        'author.name' => 2,
    ];

    public function author()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}


/**
 * COMPLETE OPERATOR REFERENCE
 * ============================
 * 
 * TEXT SEARCH OPERATORS (advancedSearch):
 * ----------------------------------------
 * AND       - Both terms must be present (default)
 *             Example: "Laravel AND framework"
 * 
 * OR        - Either term can be present
 *             Example: "Laravel OR PHP"
 * 
 * "..."     - Exact phrase match
 *             Example: '"Laravel framework"'
 * 
 * -term     - Exclude term from results
 *             Example: "Laravel -WordPress"
 * 
 * COMBINED TEXT SEARCH EXAMPLES:
 * - "Laravel OR PHP AND tutorial"
 * - '"web development" AND modern -legacy'
 * - "framework -WordPress -CodeIgniter"
 * - '"Laravel 11" OR "Laravel 12" AND tutorial -beginner'
 * 
 * 
 * FIELD FILTER OPERATORS (filter):
 * =================================
 * 
 * COMPARISON OPERATORS:
 * ---------------------
 * field:>        - Greater than
 *                  Example: ['price:>' => 100]
 * 
 * field:>=       - Greater than or equal
 * field:gte      - (alias)
 *                  Example: ['rating:>=' => 4.5]
 * 
 * field:<        - Less than
 *                  Example: ['views:<' => 50]
 * 
 * field:<=       - Less than or equal
 * field:lte      - (alias)
 *                  Example: ['price:<=' => 99.99]
 * 
 * field:=        - Equal to
 * field:eq       - (alias)
 *                  Example: ['status:=' => 'active']
 * 
 * field:!=       - Not equal to
 * field:<>       - (alias)
 * field:not      - (alias)
 * field:ne       - (alias)
 *                  Example: ['status:!=' => 'draft']
 * 
 * 
 * RANGE OPERATORS:
 * ----------------
 * field:between     - Between two values (inclusive)
 *                     Example: ['price:between' => [100, 500]]
 *                     Example: ['created_at:between' => ['2024-01-01', '2024-12-31']]
 * 
 * field:not_between - Not between two values
 * field:notbetween  - (alias)
 *                     Example: ['views:not_between' => [0, 10]]
 * 
 * 
 * ARRAY OPERATORS:
 * ----------------
 * field:in       - Value in array (IN query)
 *                  Example: ['category_id:in' => [1, 2, 3]]
 *                  Example: ['brand:in' => ['Apple', 'Dell', 'HP']]
 * 
 * field:not_in   - Value not in array (NOT IN query)
 * field:notin    - (alias)
 *                  Example: ['status:not_in' => ['draft', 'deleted']]
 * 
 * field          - Array without operator (defaults to IN)
 *                  Example: ['category_id' => [1, 2, 3]]
 * 
 * 
 * STRING PATTERN OPERATORS:
 * -------------------------
 * field:like         - LIKE pattern match
 *                      Example: ['title:like' => '%Laravel%']
 * 
 * field:not_like     - NOT LIKE pattern
 * field:notlike      - (alias)
 *                      Example: ['title:not_like' => '%WordPress%']
 * 
 * field:starts_with  - Starts with string
 * field:startswith   - (alias)
 * field:starts       - (alias)
 *                      Example: ['name:starts_with' => 'John']
 * 
 * field:ends_with    - Ends with string
 * field:endswith     - (alias)
 * field:ends         - (alias)
 *                      Example: ['email:ends_with' => '@gmail.com']
 * 
 * field:contains     - Contains string (shorthand for %value%)
 *                      Example: ['content:contains' => 'Laravel']
 * 
 * field:not_contains - Does not contain string
 * field:notcontains  - (alias)
 * field:doesnt_contain - (alias)
 *                      Example: ['content:not_contains' => 'deprecated']
 * 
 * 
 * NULL OPERATORS:
 * ---------------
 * field:null         - IS NULL
 * field:is_null      - (alias)
 * field:isnull       - (alias)
 *                      Example: ['deleted_at:null' => true]
 * 
 * field:not_null     - IS NOT NULL
 * field:is_not_null  - (alias)
 * field:isnotnull    - (alias)
 * field:notnull      - (alias)
 *                      Example: ['email_verified_at:not_null' => true]
 * 
 * 
 * EXACT MATCH:
 * ------------
 * field           - Exact match (no operator)
 *                   Example: ['status' => 'active']
 * 
 * 
 * QUERY STRING EXAMPLES:
 * ======================
 * 
 * Basic Filters:
 * --------------
 * ?price:>=100
 * ?rating:>4.5
 * ?views:<1000
 * ?status:!=draft
 * 
 * Range Filters:
 * --------------
 * ?price:between[]=100&price:between[]=500
 * ?created_at:between[]=2024-01-01&created_at:between[]=2024-12-31
 * ?views:not_between[]=0&views:not_between[]=10
 * 
 * Array Filters (IN/NOT IN):
 * --------------------------
 * ?category_id:in[]=1&category_id:in[]=2&category_id:in[]=3
 * ?brand:not_in[]=Unknown&brand:not_in[]=Generic
 * ?brand[]=Apple&brand[]=Dell (defaults to IN)
 * 
 * String Pattern Filters:
 * -----------------------
 * ?name:starts_with=John
 * ?email:ends_with=@gmail.com
 * ?title:contains=Laravel
 * ?content:not_contains=deprecated
 * ?title:like=%framework%
 * 
 * NULL Filters:
 * -------------
 * ?deleted_at:null=true
 * ?email_verified_at:not_null=true
 * 
 * Combined Filters:
 * -----------------
 * ?q=laptop&price:>=500&price:<=2000&rating:>=4.5&status:!=draft
 * ?category_id:in[]=1&category_id:in[]=2&brand:not_in[]=Generic&price:between[]=100&price:between[]=500
 * ?name:contains=Pro&status:!=draft&discount:not_null=true&sort_by=price&per_page=20
 * 
 * Complete Advanced Example:
 * --------------------------
 * ?q=gaming+laptop&category_id:in[]=1&category_id:in[]=2&price:between[]=1000&price:between[]=3000&rating:>=4&brand:not_in[]=Generic&name:contains=Pro&status:!=draft&stock:>0&discount:not_null=true&sort_by=price&sort_dir=asc&per_page=24
 */

/**
 * CONTROLLER USAGE EXAMPLES
 */

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Product;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    // ============================================
    // NEW SIMPLIFIED WAY - Use applyFilters()
    // ============================================
    
    // Simple search - ONE LINE!
    public function index(Request $request)
    {
        // Automatically handles: q, field filters, sorting, pagination
        // URL: /articles?q=Laravel&views:>=100&rating:>=4&sort_by=created_at&per_page=20
        
        $articles = Article::filterAndPaginate();
        
        return view('articles.index', compact('articles'));
    }
    
    // With additional constraints
    public function published(Request $request)
    {
        $articles = Article::where('status', 'published')
            ->smartFilter()
            ->with(['author', 'category'])
            ->paginate(15);
        
        return view('articles.published', compact('articles'));
    }
    
    // API endpoint
    public function apiIndex(Request $request)
    {
        $articles = Article::filterAndPaginate(null, 20);
        
        return response()->json($articles);
    }
    
    // Strict mode - only allow defined searchable fields
    public function strictSearch(Request $request)
    {
        $articles = Article::smartFilter(null, ['strict' => true])
            ->paginate(15);
        
        return view('articles.search', compact('articles'));
    }
    
    // ============================================
    // OLD WAY - Manual filter building (still works)
    // ============================================
    
    // Basic search controller (OLD WAY)
    public function searchOld(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2',
            'fields' => 'sometimes|array',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $articles = Article::advancedSearch(
            $request->input('q'),
            $request->input('fields')
        )
        ->where('status', 'published')
        ->with(['author', 'category'])
        ->orderBy('created_at', 'desc')
        ->paginate($request->input('per_page', 15));

        return view('articles.search', compact('articles'));
    }
    
    // Advanced search with filters (OLD WAY)
    public function advancedSearchOld(Request $request)
    {
        $request->validate([
            'q' => 'sometimes|string|min:2',
            'views_min' => 'sometimes|integer',
            'views_max' => 'sometimes|integer',
            'rating_min' => 'sometimes|numeric',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date',
            'category' => 'sometimes|array',
        ]);

        $query = Article::query();
        
        // Apply text search if provided
        if ($request->has('q')) {
            $query->advancedSearch($request->input('q'));
        }
        
        // Build filters
        $filters = [];
        
        if ($request->has('views_min')) {
            $filters['views:>='] = $request->input('views_min');
        }
        
        if ($request->has('views_max')) {
            $filters['views:<='] = $request->input('views_max');
        }
        
        if ($request->has('rating_min')) {
            $filters['rating:>='] = $request->input('rating_min');
        }
        
        if ($request->has('category')) {
            $filters['category_id'] = $request->input('category');
        }
        
        if ($request->has('date_from') && $request->has('date_to')) {
            $filters['created_at:between'] = [
                $request->input('date_from'),
                $request->input('date_to'),
            ];
        }
        
        // Apply filters
        if (!empty($filters)) {
            $query->filter($filters);
        }
        
        $articles = $query
            ->where('status', 'published')
            ->with(['author', 'category'])
            ->orderBy($request->input('sort_by', 'created_at'), $request->input('sort_dir', 'desc'))
            ->paginate($request->input('per_page', 15));

        return view('articles.search', compact('articles'));
    }
}

class ProductController extends Controller
{
    // ============================================
    // NEW SIMPLIFIED WAY
    // ============================================
    
    // E-commerce product listing - ONE LINE!
    public function index(Request $request)
    {
        // URL: /products?q=laptop&price:>=500&price:<=2000&rating:>=4&brand[]=Apple&brand[]=Dell
        
        $products = Product::filterAndPaginate(null, 24);
        
        return view('products.index', compact('products'));
    }
    
    // With scopes and eager loading
    public function featured(Request $request)
    {
        $products = Product::where('featured', true)
            ->smartFilter()
            ->with(['category', 'brand', 'images'])
            ->paginate(request('per_page', 24));
        
        return view('products.featured', compact('products'));
    }
    
    // API with custom validation
    public function apiFilter(Request $request)
    {
        // Optional: Add validation if needed
        $request->validate([
            'price_min' => 'sometimes|numeric|min:0',
            'price_max' => 'sometimes|numeric',
            'rating_min' => 'sometimes|numeric|min:0|max:5',
        ]);
        
        $products = Product::filterAndPaginate(null, 20);
        
        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'meta' => [
                'total' => $products->total(),
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
            ],
        ]);
    }
    
    // ============================================
    // OLD WAY (still works but more code)
    // ============================================
    
    // E-commerce product search with filters (OLD WAY)
    public function indexOld(Request $request)
    {
        $request->validate([
            'q' => 'sometimes|string|min:2',
            'price_min' => 'sometimes|numeric|min:0',
            'price_max' => 'sometimes|numeric',
            'rating_min' => 'sometimes|numeric|min:0|max:5',
            'brand' => 'sometimes|array',
            'category' => 'sometimes|integer',
        ]);

        $query = Product::query();
        
        // Text search
        if ($request->filled('q')) {
            $query->search($request->input('q'));
        }
        
        // Build filters array
        $filters = array_filter([
            'price:>=' => $request->input('price_min'),
            'price:<=' => $request->input('price_max'),
            'rating:>=' => $request->input('rating_min'),
            'brand' => $request->input('brand'),
            'category_id' => $request->input('category'),
            'in_stock' => $request->boolean('in_stock') ? 1 : null,
        ], fn($value) => !is_null($value) && $value !== '');
        
        // Apply filters
        if (!empty($filters)) {
            $query->filter($filters);
        }
        
        $products = $query
            ->with(['category', 'brand'])
            ->orderBy($request->input('sort_by', 'created_at'), $request->input('sort_dir', 'desc'))
            ->paginate($request->input('per_page', 24));

        return view('products.index', compact('products'));
    }
}

/*
// ============================================
// COMPARISON: Old vs New
// ============================================
*/

class ComparisonController extends Controller
{
    // OLD WAY - ~30 lines of code
    public function oldWay(Request $request)
    {
        $query = Product::query();
        
        if ($request->has('q')) {
            $query->search($request->input('q'));
        }
        
        $filters = [];
        if ($request->has('price_min')) $filters['price:>='] = $request->input('price_min');
        if ($request->has('price_max')) $filters['price:<='] = $request->input('price_max');
        if ($request->has('rating_min')) $filters['rating:>='] = $request->input('rating_min');
        if ($request->has('category')) $filters['category_id'] = $request->input('category');
        
        if (!empty($filters)) {
            $query->filter($filters);
        }
        
        return $query->orderBy('created_at', 'desc')->paginate(15);
    }

    // NEW WAY - 1 line of code!
    public function newWay(Request $request)
    {
        return Product::filterAndPaginate();
    }

    // =========================================================================
    // FILTER STRING PARSING WITH filterQueryString() METHOD
    // =========================================================================
    // The filterQueryString() method provides a powerful way to parse complex
    // filter expressions from query strings using an operator-based syntax.
    // Format: field:OPERATOR(value1,value2,...);field2:OPERATOR(value)
    // =========================================================================

    /**
     * Example 47: Basic Comparison Operators
     * Demonstrates single filter expressions with comparison operators.
     */
    public function example47(): void
    {
        // Greater than
        $expensiveProducts = Product::filterQueryString('price:GT(1000)')->get();
        // SELECT * FROM products WHERE price > 1000

        // Greater than or equal
        $highRatedProducts = Product::filterQueryString('rating:GTE(4.5)')->get();
        // SELECT * FROM products WHERE rating >= 4.5

        // Less than
        $budgetProducts = Product::filterQueryString('price:LT(100)')->get();
        // SELECT * FROM products WHERE price < 100

        // Less than or equal
        $recentProducts = Product::filterQueryString('views:LTE(50)')->get();
        // SELECT * FROM products WHERE views <= 50

        // Equal
        $activeProducts = Product::filterQueryString('status:EQ(active)')->get();
        // SELECT * FROM products WHERE status = 'active'

        // Not equal
        $nonDraftProducts = Product::filterQueryString('status:NEQ(draft)')->get();
        // SELECT * FROM products WHERE status != 'draft'
    }

    /**
     * Example 48: IN and NOT_IN Operators
     * Filter by multiple values (array operations).
     */
    public function example48(): void
    {
        // IN operator - match any value in list
        $selectedCategories = Product::filterQueryString('category_id:IN(1,2,3,5,8)')->get();
        // SELECT * FROM products WHERE category_id IN (1, 2, 3, 5, 8)

        // Multiple brands
        $techBrands = Product::filterQueryString('brand:IN(Apple,Samsung,Google,Microsoft)')->get();
        // SELECT * FROM products WHERE brand IN ('Apple', 'Samsung', 'Google', 'Microsoft')

        // NOT_IN operator - exclude values
        $excludeStatuses = Order::filterQueryString('status:NOT_IN(cancelled,refunded,failed)')->get();
        // SELECT * FROM orders WHERE status NOT IN ('cancelled', 'refunded', 'failed')

        // Exclude specific IDs
        $excludeUsers = User::filterQueryString('id:NOT_IN(1,2,5,10)')->get();
        // SELECT * FROM users WHERE id NOT IN (1, 2, 5, 10)
    }

    /**
     * Example 49: BETWEEN and NOT_BETWEEN Operators
     * Range filtering for numeric and date values.
     */
    public function example49(): void
    {
        // Price range with BETWEEN
        $midRangeProducts = Product::filterQueryString('price:BETWEEN(100,500)')->get();
        // SELECT * FROM products WHERE price BETWEEN 100 AND 500

        // Rating range
        $goodProducts = Product::filterQueryString('rating:BETWEEN(3.5,4.5)')->get();
        // SELECT * FROM products WHERE rating BETWEEN 3.5 AND 4.5

        // NOT_BETWEEN - exclude a range
        $extremePrices = Product::filterQueryString('price:NOT_BETWEEN(50,200)')->get();
        // SELECT * FROM products WHERE price NOT BETWEEN 50 AND 200
        // Returns products < 50 OR > 200

        // Quantity ranges
        $stockLevels = Product::filterQueryString('stock:BETWEEN(10,100)')->get();
        // SELECT * FROM products WHERE stock BETWEEN 10 AND 100
    }

    /**
     * Example 50: String Pattern Matching Operators
     * LIKE, NOT_LIKE, STARTS_WITH, ENDS_WITH for text filtering.
     */
    public function example50(): void
    {
        // LIKE operator - contains pattern
        $laptopProducts = Product::filterQueryString('name:LIKE(laptop)')->get();
        // SELECT * FROM products WHERE name LIKE '%laptop%'

        // NOT_LIKE - exclude pattern
        $nonRefurbished = Product::filterQueryString('description:NOT_LIKE(refurbished)')->get();
        // SELECT * FROM products WHERE description NOT LIKE '%refurbished%'

        // STARTS_WITH - prefix matching
        $proProducts = Product::filterQueryString('name:STARTS_WITH(Pro)')->get();
        // SELECT * FROM products WHERE name LIKE 'Pro%'
        // Matches: "Pro MacBook", "Professional Camera", etc.

        // ENDS_WITH - suffix matching
        $gmailUsers = User::filterQueryString('email:ENDS_WITH(@gmail.com)')->get();
        // SELECT * FROM users WHERE email LIKE '%@gmail.com'

        // Multiple pattern matches
        $filteredUsers = User::filterQueryString('name:STARTS_WITH(John);email:ENDS_WITH(@company.com)')->get();
        // SELECT * FROM users WHERE name LIKE 'John%' AND email LIKE '%@company.com'
    }

    /**
     * Example 51: Date Filtering Operators
     * DATE_EQ, DATE_GT, DATE_LT, DATE_BETWEEN, YEAR, MONTH, DAY.
     */
    public function example51(): void
    {
        // Date equals
        $todayOrders = Order::filterQueryString('created_at:DATE_EQ(2024-06-15)')->get();
        // SELECT * FROM orders WHERE DATE(created_at) = '2024-06-15'

        // Date greater than
        $recentOrders = Order::filterQueryString('created_at:DATE_GT(2024-01-01)')->get();
        // SELECT * FROM orders WHERE DATE(created_at) > '2024-01-01'

        // Date less than or equal
        $oldOrders = Order::filterQueryString('created_at:DATE_LTE(2023-12-31)')->get();
        // SELECT * FROM orders WHERE DATE(created_at) <= '2023-12-31'

        // Date range with DATE_BETWEEN
        $yearOrders = Order::filterQueryString('created_at:DATE_BETWEEN(2024-01-01,2024-12-31)')->get();
        // SELECT * FROM orders WHERE DATE(created_at) BETWEEN '2024-01-01' AND '2024-12-31'

        // Filter by year
        $year2024Orders = Order::filterQueryString('created_at:YEAR(2024)')->get();
        // SELECT * FROM orders WHERE YEAR(created_at) = 2024

        // Filter by month (1-12)
        $januaryOrders = Order::filterQueryString('created_at:MONTH(1)')->get();
        // SELECT * FROM orders WHERE MONTH(created_at) = 1

        // Filter by day (1-31)
        $firstDayOrders = Order::filterQueryString('created_at:DAY(1)')->get();
        // SELECT * FROM orders WHERE DAY(created_at) = 1

        // Combine date filters
        $specificOrders = Order::filterQueryString('created_at:YEAR(2024);created_at:MONTH(6);status:EQ(completed)')->get();
        // SELECT * FROM orders WHERE YEAR(created_at) = 2024 AND MONTH(created_at) = 6 AND status = 'completed'
    }

    /**
     * Example 52: NULL Checking Operators
     * IS_NULL and IS_NOT_NULL for null value filtering.
     */
    public function example52(): void
    {
        // Find null values
        $unpublishedPosts = Post::filterQueryString('published_at:IS_NULL()')->get();
        // SELECT * FROM posts WHERE published_at IS NULL

        // Find non-null values
        $publishedPosts = Post::filterQueryString('published_at:IS_NOT_NULL()')->get();
        // SELECT * FROM posts WHERE published_at IS NOT NULL

        // Soft-deleted records (not deleted)
        $activeRecords = Product::filterQueryString('deleted_at:IS_NULL()')->get();
        // SELECT * FROM products WHERE deleted_at IS NULL

        // Combine null checks
        $readyPosts = Post::filterQueryString('published_at:IS_NOT_NULL();deleted_at:IS_NULL()')->get();
        // SELECT * FROM posts WHERE published_at IS NOT NULL AND deleted_at IS NULL

        // Complex combination
        $completeOrders = Order::filterQueryString('completed_at:IS_NOT_NULL();cancelled_at:IS_NULL();status:EQ(delivered)')->get();
        // SELECT * FROM orders WHERE completed_at IS NOT NULL AND cancelled_at IS NULL AND status = 'delivered'
    }

    /**
     * Example 53: JSON Operations
     * JSON_CONTAINS and JSON_LENGTH for JSON column filtering.
     */
    public function example53(): void
    {
        // JSON contains value
        $featuredProducts = Product::filterQueryString('metadata:JSON_CONTAINS(featured)')->get();
        // SELECT * FROM products WHERE JSON_CONTAINS(metadata, '"featured"')

        // JSON array length
        $threeTagProducts = Product::filterQueryString('tags:JSON_LENGTH(3)')->get();
        // SELECT * FROM products WHERE JSON_LENGTH(tags) = 3

        // Multiple JSON conditions
        $complexProducts = Product::filterQueryString('metadata:JSON_CONTAINS(new);tags:JSON_LENGTH(5)')->get();
        // SELECT * FROM products WHERE JSON_CONTAINS(metadata, '"new"') AND JSON_LENGTH(tags) = 5

        // Combine with other operators
        $advancedFilter = Product::filterQueryString('price:GT(100);metadata:JSON_CONTAINS(premium);tags:JSON_LENGTH(3)')->get();
        // SELECT * FROM products WHERE price > 100 AND JSON_CONTAINS(metadata, '"premium"') AND JSON_LENGTH(tags) = 3
    }

    /**
     * Example 54: Combined Multiple Operators
     * Real-world complex filtering scenarios.
     */
    public function example54(): void
    {
        // E-commerce product filtering
        $products = Product::filterQueryString(
            'category_id:IN(1,2,3);price:BETWEEN(100,500);rating:GTE(4);status:EQ(active);deleted_at:IS_NULL()'
        )->get();
        // SELECT * FROM products 
        // WHERE category_id IN (1, 2, 3) 
        // AND price BETWEEN 100 AND 500 
        // AND rating >= 4 
        // AND status = 'active' 
        // AND deleted_at IS NULL

        // Order filtering
        $orders = Order::filterQueryString(
            'status:IN(processing,shipped);created_at:DATE_BETWEEN(2024-01-01,2024-12-31);total:GT(100)'
        )->get();
        // SELECT * FROM orders 
        // WHERE status IN ('processing', 'shipped') 
        // AND DATE(created_at) BETWEEN '2024-01-01' AND '2024-12-31' 
        // AND total > 100

        // User filtering
        $users = User::filterQueryString(
            'email:ENDS_WITH(@company.com);created_at:YEAR(2024);status:NEQ(suspended);email_verified_at:IS_NOT_NULL()'
        )->get();
        // SELECT * FROM users 
        // WHERE email LIKE '%@company.com' 
        // AND YEAR(created_at) = 2024 
        // AND status != 'suspended' 
        // AND email_verified_at IS NOT NULL

        // Content filtering
        $posts = Post::filterQueryString(
            'title:LIKE(Laravel);published_at:DATE_GT(2024-01-01);views:GTE(100);status:EQ(published);category_id:IN(1,2,5)'
        )->get();
        // Complex WHERE clause with multiple conditions
    }

    /**
     * Example 55: Filter String Validation
     * Using the validateFilterString() static method.
     */
    public function example55(): void
    {
        // Validate filter syntax
        $validation1 = Product::validateFilterString('price:GT(1000);status:EQ(active)');
        /*
        Returns:
        [
            'valid' => true,
            'errors' => [],
            'filters' => [
                ['field' => 'price', 'operator' => 'GT', 'values' => ['1000']],
                ['field' => 'status', 'operator' => 'EQ', 'values' => ['active']]
            ]
        ]
        */

        // Invalid operator
        $validation2 = Product::validateFilterString('price:INVALID(100)');
        /*
        Returns:
        [
            'valid' => false,
            'errors' => ['Unsupported operator: INVALID'],
            'filters' => []
        ]
        */

        // Invalid syntax (missing parentheses)
        $validation3 = Product::validateFilterString('price:GT100');
        /*
        Returns:
        [
            'valid' => false,
            'errors' => ['Invalid filter format: price:GT100'],
            'filters' => []
        ]
        */

        // Use in API validation
        $filterString = request('filter');
        $validation = Product::validateFilterString($filterString);
        
        if (!$validation['valid']) {
            return response()->json([
                'error' => 'Invalid filter syntax',
                'details' => $validation['errors']
            ], 400);
        }

        $results = Product::filterQueryString($filterString)->get();
    }

    /**
     * Example 56: Building Filter Strings Programmatically
     * Using the buildFilterString() static method.
     */
    public function example56(): void
    {
        // Build from array
        $filters = [
            ['field' => 'price', 'operator' => 'GT', 'values' => [1000]],
            ['field' => 'status', 'operator' => 'EQ', 'values' => ['active']],
            ['field' => 'category_id', 'operator' => 'IN', 'values' => [1, 2, 3]]
        ];

        $filterString = Product::buildFilterString($filters);
        // Returns: "price:GT(1000);status:EQ(active);category_id:IN(1,2,3)"

        $products = Product::filterQueryString($filterString)->get();

        // Dynamic filter building
        $filterArray = [];

        if ($minPrice = request('min_price')) {
            $filterArray[] = ['field' => 'price', 'operator' => 'GTE', 'values' => [$minPrice]];
        }

        if ($maxPrice = request('max_price')) {
            $filterArray[] = ['field' => 'price', 'operator' => 'LTE', 'values' => [$maxPrice]];
        }

        if ($categories = request('categories')) {
            $filterArray[] = ['field' => 'category_id', 'operator' => 'IN', 'values' => $categories];
        }

        if (!empty($filterArray)) {
            $filterString = Product::buildFilterString($filterArray);
            $products = Product::filterQueryString($filterString)->get();
        }

        // Complex example
        $advancedFilters = [
            ['field' => 'created_at', 'operator' => 'DATE_BETWEEN', 'values' => ['2024-01-01', '2024-12-31']],
            ['field' => 'rating', 'operator' => 'GTE', 'values' => [4.5]],
            ['field' => 'brand', 'operator' => 'IN', 'values' => ['Apple', 'Samsung', 'Google']],
            ['field' => 'deleted_at', 'operator' => 'IS_NULL', 'values' => []]
        ];

        $filterString = Product::buildFilterString($advancedFilters);
        $results = Product::filterQueryString($filterString)->get();
    }

    /**
     * Example 57: Real-World API Endpoint with filterQueryString()
     * Complete API controller example.
     */
    public function example57(): void
    {
        // In your API Controller:
        /*
        public function index(Request $request)
        {
            $query = Product::query();

            // Apply filter string if provided
            // GET /api/products?filter=price:BETWEEN(100,500);category_id:IN(1,2,3);rating:GTE(4)
            if ($filterString = $request->query('filter')) {
                // Validate first
                $validation = Product::validateFilterString($filterString);
                
                if (!$validation['valid']) {
                    return response()->json([
                        'error' => 'Invalid filter syntax',
                        'details' => $validation['errors']
                    ], 400);
                }

                // Define allowed fields for security
                $allowedFields = ['price', 'category_id', 'rating', 'status', 'brand', 'created_at'];
                
                $query->filterQueryString($filterString, $allowedFields);
            }

            // Apply text search if provided
            if ($search = $request->query('q')) {
                $query->search($search, ['name', 'description']);
            }

            // Apply sorting
            if ($sortBy = $request->query('sort_by')) {
                $sortDir = $request->query('sort_dir', 'asc');
                $query->orderBy($sortBy, $sortDir);
            }

            // Paginate results
            return response()->json(
                $query->paginate($request->query('per_page', 15))
            );
        }
        */

        // Example API calls:
        
        // Basic filter
        // GET /api/products?filter=price:GT(1000)
        
        // Multiple filters
        // GET /api/products?filter=price:BETWEEN(100,500);rating:GTE(4);status:EQ(active)
        
        // With search
        // GET /api/products?q=laptop&filter=price:LT(2000);brand:IN(Dell,HP)
        
        // With sorting
        // GET /api/products?filter=category_id:IN(1,2);rating:GTE(4)&sort_by=price&sort_dir=asc
        
        // Date filtering
        // GET /api/orders?filter=created_at:DATE_BETWEEN(2024-01-01,2024-12-31);status:EQ(completed)
        
        // Complex combination
        // GET /api/products?q=gaming&filter=price:BETWEEN(500,2000);rating:GTE(4.5);brand:IN(MSI,ASUS);metadata:JSON_CONTAINS(featured)&sort_by=rating&sort_dir=desc&per_page=20
    }

    /**
     * Example 58: Available Filter Operators Reference
     * Get all available operators and their descriptions.
     */
    public function example58(): void
    {
        // Get all available operators
        $operators = Product::getAvailableFilterOperators();
        /*
        Returns:
        [
            'IN' => 'Match any value in a list',
            'NOT_IN' => 'Exclude values in a list',
            'BETWEEN' => 'Value between two numbers/dates',
            'NOT_BETWEEN' => 'Value not between two numbers/dates',
            'EQ' => 'Equal to',
            'NEQ' => 'Not equal to',
            'GT' => 'Greater than',
            'GTE' => 'Greater than or equal to',
            'LT' => 'Less than',
            'LTE' => 'Less than or equal to',
            'LIKE' => 'Contains text (case-insensitive)',
            'NOT_LIKE' => 'Does not contain text',
            'STARTS_WITH' => 'Starts with text',
            'ENDS_WITH' => 'Ends with text',
            'IS_NULL' => 'Field is null',
            'IS_NOT_NULL' => 'Field is not null',
            'DATE_EQ' => 'Date equals',
            'DATE_GT' => 'Date greater than',
            'DATE_GTE' => 'Date greater than or equal',
            'DATE_LT' => 'Date less than',
            'DATE_LTE' => 'Date less than or equal',
            'DATE_BETWEEN' => 'Date between range',
            'YEAR' => 'Year equals',
            'MONTH' => 'Month equals (1-12)',
            'DAY' => 'Day equals (1-31)',
            'JSON_CONTAINS' => 'JSON column contains value',
            'JSON_LENGTH' => 'JSON array length equals',
            'REGEX' => 'Matches regular expression',
            'REGEXP' => 'Matches regular expression (alias)'
        ]
        */

        // Use for API documentation
        // Display in help endpoint: GET /api/help/filters
    }

    /**
     * Example 59: Field Whitelisting for Security
     * Protect against arbitrary field filtering.
     */
    public function example59(): void
    {
        // Define allowed fields in model
        /*
        class Product extends Model
        {
            use Searchable;

            protected array $filterableFields = [
                'price',
                'category_id',
                'rating',
                'status',
                'brand',
                'created_at'
            ];
        }
        */

        // In controller - fields are automatically restricted
        $products = Product::filterQueryString(request('filter'))->get();
        // Only fields in $filterableFields can be filtered

        // Override at query time
        $products = Product::filterQueryString(
            request('filter'),
            ['price', 'category_id', 'rating'] // More restrictive
        )->get();

        // Attempting to filter disallowed field will be ignored silently
        // GET /api/products?filter=password:EQ(secret);price:GT(100)
        // Only price filter will be applied, password filter is ignored

        // Best practice: Always define $filterableFields in your models
    }

    /**
     * Example 60: Comparison - Old vs New Approach
     * See how filterQueryString() simplifies complex filtering.
     */
    public function example60(): void
    {
        // OLD APPROACH - Manual query building
        /*
        public function index(Request $request)
        {
            $query = Product::query();

            if ($minPrice = $request->query('min_price')) {
                $query->where('price', '>=', $minPrice);
            }

            if ($maxPrice = $request->query('max_price')) {
                $query->where('price', '<=', $maxPrice);
            }

            if ($categories = $request->query('categories')) {
                $query->whereIn('category_id', $categories);
            }

            if ($minRating = $request->query('min_rating')) {
                $query->where('rating', '>=', $minRating);
            }

            if ($status = $request->query('status')) {
                $query->where('status', $status);
            }

            if ($brands = $request->query('brands')) {
                $query->whereIn('brand', $brands);
            }

            if ($dateFrom = $request->query('date_from')) {
                $query->whereDate('created_at', '>=', $dateFrom);
            }

            if ($dateTo = $request->query('date_to')) {
                $query->whereDate('created_at', '<=', $dateTo);
            }

            return response()->json($query->paginate());
        }
        */

        // NEW APPROACH - Using filterQueryString()
        /*
        public function index(Request $request)
        {
            $query = Product::query();

            // Single filter parameter handles everything
            // GET /api/products?filter=price:BETWEEN(100,500);category_id:IN(1,2,3);rating:GTE(4);status:EQ(active);brand:IN(Apple,Samsung);created_at:DATE_BETWEEN(2024-01-01,2024-12-31)
            
            if ($filterString = $request->query('filter')) {
                $query->filterQueryString($filterString);
            }

            return response()->json($query->paginate());
        }
        */

        // Benefits:
        // 1. Much less code in controller
        // 2. Consistent filter syntax across all endpoints
        // 3. Self-documenting API (filter syntax is standardized)
        // 4. Easier to test and maintain
        // 5. Built-in validation
        // 6. Automatic type casting
        // 7. Support for complex operators (BETWEEN, IN, JSON, etc.)
        // 8. Field whitelisting for security
    }
}

/**
 * ROUTE EXAMPLES
 */

/*
// routes/web.php

// Basic search route
Route::get('/search', [ArticleController::class, 'search'])->name('articles.search');

// Advanced search with filters
Route::get('/articles/search', [ArticleController::class, 'advancedSearch'])->name('articles.advanced.search');

// Product filtering
Route::get('/products', [ProductController::class, 'index'])->name('products.index');

// API routes
Route::prefix('api')->group(function () {
    Route::get('/articles/search', [ArticleController::class, 'apiSearch']);
    Route::get('/products/filter', [ProductController::class, 'apiFilter']);
});

USAGE IN BROWSER:
=================

Basic Text Search:
------------------
/search?q=Laravel+AND+framework+-beginner
/search?q="web+development"+AND+PHP&fields[]=title&fields[]=content
/search?q=tutorial+OR+guide&per_page=20

Advanced Search with Filters:
-----------------------------
// Search with view count filter
/articles/search?q=Laravel&views:>=100&views:<=1000

// Search with rating filter
/articles/search?q=tutorial&rating:>=4.5

// Search with date range
/articles/search?q=PHP&date_from=2024-01-01&date_to=2024-12-31

// Multiple categories (IN query)
/articles/search?q=framework&category[]=1&category[]=2&category[]=3

// Complete example
/articles/search?q=Laravel+AND+framework&views:>=100&rating:>=4&date_from=2024-01-01&category[]=1&sort_by=views&sort_dir=desc

Product Filtering:
------------------
// Price range filter
/products?price_min=100&price_max=500

// Price and rating
/products?price_min=50&price_max=200&rating_min=4.0

// Search with filters
/products?q=laptop&price_min=500&price_max=2000&rating_min=4.5

// Brand filter (multiple)
/products?brand[]=Apple&brand[]=Dell&brand[]=HP

// Complete product filter
/products?q=gaming+laptop&price_min=1000&price_max=3000&rating_min=4.5&brand[]=MSI&brand[]=ASUS&in_stock=1&sort_by=price&sort_dir=asc

Direct Field Operators in URL:
-------------------------------
// Using colon operators directly
/products?price:>=500&price:<=2000&rating:>=4.5&views:>100

// Between operator
/articles?created_at:between[]=2024-01-01&created_at:between[]=2024-12-31

// Combined
/products?q=laptop&price:>=1000&price:<=3000&rating:>=4&brand[]=Dell&brand[]=HP
*/
