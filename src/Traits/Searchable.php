<?php

namespace Litepie\Database\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Advanced Searchable trait with multiple search strategies.
 */
trait Searchable
{
    /**
     * The searchable fields configuration.
     *
     * @var array
     */
    protected array $searchable = [];

    /**
     * The full-text searchable fields.
     *
     * @var array
     */
    protected array $fullTextSearchable = [];

    /**
     * Searchable field weights for relevance scoring.
     *
     * @var array
     */
    protected array $searchWeights = [];

    /**
     * Fields allowed for filtering via query string.
     * If empty, uses $searchable and $fillable fields.
     *
     * @var array
     */
    protected array $filterableFields = [];

    /**
     * Initialize the searchable trait.
     *
     * @return void
     */
    public function initializeSearchable(): void
    {
        if (empty($this->searchable) && property_exists($this, 'fillable')) {
            $this->searchable = $this->fillable;
        }
    }

    /**
     * Scope for basic search across specified fields.
     *
     * @param Builder $query
     * @param string $term
     * @param array|null $fields
     * @return Builder
     */
    public function scopeSearch(Builder $query, string $term, ?array $fields = null): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $searchFields = $fields ?? $this->getSearchFields();
        
        if (empty($searchFields)) {
            return $query;
        }

        return $query->where(function (Builder $subQuery) use ($term, $searchFields) {
            foreach ($searchFields as $field) {
                $this->addSearchCondition($subQuery, $field, $term);
            }
        });
    }

    /**
     * Scope for advanced search with operators and multiple terms.
     *
     * @param Builder $query
     * @param string $term
     * @param array|null $fields
     * @return Builder
     */
    public function scopeAdvancedSearch(Builder $query, string $term, ?array $fields = null): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $searchFields = $fields ?? $this->getSearchFields();
        
        if (empty($searchFields)) {
            return $query;
        }

        // Parse search terms and operators
        $searchTerms = $this->parseSearchTerms($term);
        
        return $query->where(function (Builder $subQuery) use ($searchTerms, $searchFields) {
            foreach ($searchTerms as $termData) {
                $method = $termData['operator'] === 'AND' ? 'where' : 'orWhere';
                
                $subQuery->$method(function (Builder $termQuery) use ($termData, $searchFields) {
                    foreach ($searchFields as $field) {
                        $this->addAdvancedSearchCondition($termQuery, $field, $termData);
                    }
                });
            }
        });
    }

    /**
     * Scope for full-text search (MySQL FULLTEXT).
     *
     * @param Builder $query
     * @param string $term
     * @param array|null $fields
     * @param string $mode
     * @return Builder
     */
    public function scopeFullTextSearch(Builder $query, string $term, ?array $fields = null, string $mode = 'NATURAL LANGUAGE'): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $searchFields = $fields ?? $this->getFullTextSearchFields();
        
        if (empty($searchFields)) {
            return $this->scopeSearch($query, $term, $fields);
        }

        $columns = implode(',', $searchFields);
        
        return $query->whereRaw(
            "MATCH({$columns}) AGAINST(? IN {$mode} MODE)",
            [$term]
        );
    }

    /**
     * Scope for fuzzy search using Levenshtein distance.
     *
     * @param Builder $query
     * @param string $term
     * @param array|null $fields
     * @param int $threshold
     * @return Builder
     */
    public function scopeFuzzySearch(Builder $query, string $term, ?array $fields = null, int $threshold = 2): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $searchFields = $fields ?? $this->getSearchFields();
        
        if (empty($searchFields)) {
            return $query;
        }

        return $query->where(function (Builder $subQuery) use ($term, $searchFields, $threshold) {
            foreach ($searchFields as $field) {
                $subQuery->orWhereRaw(
                    "LEVENSHTEIN({$field}, ?) <= ?",
                    [$term, $threshold]
                );
            }
        });
    }

    /**
     * Scope for weighted search with relevance scoring.
     *
     * @param Builder $query
     * @param string $term
     * @param array|null $fields
     * @return Builder
     */
    public function scopeWeightedSearch(Builder $query, string $term, ?array $fields = null): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $searchFields = $fields ?? $this->getSearchFields();
        $weights = $this->getSearchWeights();
        
        if (empty($searchFields)) {
            return $query;
        }

        $selectParts = ['*'];
        $relevanceConditions = [];

        foreach ($searchFields as $field) {
            $weight = $weights[$field] ?? 1;
            $relevanceConditions[] = "CASE WHEN {$field} LIKE '%{$term}%' THEN {$weight} ELSE 0 END";
        }

        if (!empty($relevanceConditions)) {
            $relevanceQuery = '(' . implode(' + ', $relevanceConditions) . ')';
            $selectParts[] = "{$relevanceQuery} as search_relevance";
            
            $query->selectRaw(implode(', ', $selectParts))
                  ->havingRaw('search_relevance > 0')
                  ->orderByDesc('search_relevance');
        }

        return $this->scopeSearch($query, $term, $searchFields);
    }

    /**
     * Scope for boolean search with AND/OR/NOT operators.
     *
     * @param Builder $query
     * @param string $term
     * @param array|null $fields
     * @return Builder
     */
    public function scopeBooleanSearch(Builder $query, string $term, ?array $fields = null): Builder
    {
        if (empty($term)) {
            return $query;
        }

        $searchFields = $fields ?? $this->getSearchFields();
        
        if (empty($searchFields)) {
            return $query;
        }

        // Parse boolean operators
        $booleanTerms = $this->parseBooleanSearch($term);
        
        return $query->where(function (Builder $subQuery) use ($booleanTerms, $searchFields) {
            foreach ($booleanTerms as $termData) {
                $this->applyBooleanCondition($subQuery, $termData, $searchFields);
            }
        });
    }

    /**
     * Get the searchable fields.
     *
     * @return array
     */
    public function getSearchFields(): array
    {
        return $this->searchable;
    }

    /**
     * Get the full-text searchable fields.
     *
     * @return array
     */
    public function getFullTextSearchFields(): array
    {
        return $this->fullTextSearchable;
    }

    /**
     * Get the search field weights.
     *
     * @return array
     */
    public function getSearchWeights(): array
    {
        return $this->searchWeights;
    }

    /**
     * Set searchable fields.
     *
     * @param array $fields
     * @return $this
     */
    public function setSearchFields(array $fields): static
    {
        $this->searchable = $fields;
        return $this;
    }

    /**
     * Add search condition for a field.
     *
     * @param Builder $query
     * @param string $field
     * @param string $term
     * @return void
     */
    protected function addSearchCondition(Builder $query, string $field, string $term): void
    {
        if (str_contains($field, '.')) {
            // Handle relationship fields
            $this->addRelationshipSearchCondition($query, $field, $term);
        } else {
            $query->orWhere($field, 'LIKE', "%{$term}%");
        }
    }

    /**
     * Add advanced search condition with operators.
     *
     * @param Builder $query
     * @param string $field
     * @param array $termData
     * @return void
     */
    protected function addAdvancedSearchCondition(Builder $query, string $field, array $termData): void
    {
        $term = $termData['term'];
        $exact = $termData['exact'] ?? false;
        $exclude = $termData['exclude'] ?? false;
        
        $operator = $exact ? '=' : 'LIKE';
        $value = $exact ? $term : "%{$term}%";
        $method = $exclude ? 'whereNot' : 'orWhere';
        
        $query->$method($field, $operator, $value);
    }

    /**
     * Add relationship search condition.
     *
     * @param Builder $query
     * @param string $field
     * @param string $term
     * @return void
     */
    protected function addRelationshipSearchCondition(Builder $query, string $field, string $term): void
    {
        $parts = explode('.', $field, 2);
        $relation = $parts[0];
        $column = $parts[1];
        
        $query->orWhereHas($relation, function (Builder $relationQuery) use ($column, $term) {
            $relationQuery->where($column, 'LIKE', "%{$term}%");
        });
    }

    /**
     * Parse search terms with operators.
     *
     * @param string $term
     * @return array
     */
    protected function parseSearchTerms(string $term): array
    {
        $terms = [];
        $parts = explode(' ', $term);
        
        $currentOperator = 'AND';
        
        foreach ($parts as $part) {
            $part = trim($part);
            
            if (in_array(strtoupper($part), ['AND', 'OR'])) {
                $currentOperator = strtoupper($part);
                continue;
            }
            
            $exclude = str_starts_with($part, '-');
            if ($exclude) {
                $part = substr($part, 1);
            }
            
            $exact = str_starts_with($part, '"') && str_ends_with($part, '"');
            if ($exact) {
                $part = trim($part, '"');
            }
            
            if (!empty($part)) {
                $terms[] = [
                    'term' => $part,
                    'operator' => $currentOperator,
                    'exact' => $exact,
                    'exclude' => $exclude,
                ];
            }
        }
        
        return $terms;
    }

    /**
     * Parse boolean search terms.
     *
     * @param string $term
     * @return array
     */
    protected function parseBooleanSearch(string $term): array
    {
        // Implementation for parsing complex boolean search
        // This is a simplified version
        return $this->parseSearchTerms($term);
    }

    /**
     * Apply boolean condition to query.
     *
     * @param Builder $query
     * @param array $termData
     * @param array $fields
     * @return void
     */
    protected function applyBooleanCondition(Builder $query, array $termData, array $fields): void
    {
        $method = match($termData['operator']) {
            'OR' => 'orWhere',
            'NOT' => 'whereNot',
            default => 'where'
        };
        
        $query->$method(function (Builder $subQuery) use ($termData, $fields) {
            foreach ($fields as $field) {
                $this->addAdvancedSearchCondition($subQuery, $field, $termData);
            }
        });
    }

    /**
     * Scope to automatically apply filters from request parameters.
     * 
     * This method automatically detects and applies:
     * - Text search (q parameter)
     * - Field filters with operators (field:>, field:>=, field:<, field:<=, field:between)
     * - Exact matches (field=value)
     * - Array filters (field[]=value1&field[]=value2)
     * 
     * Only applies filters for fields defined in $searchable or $fillable arrays.
     *
     * @param Builder $query
     * @param array|null $params Request parameters (defaults to request()->all())
     * @param array $options Additional options ['strict' => bool, 'exclude' => array]
     * @return Builder
     */
    public function scopeApplyFilters(Builder $query, ?array $params = null, array $options = []): Builder
    {
        $params = $params ?? request()->all();
        $strict = $options['strict'] ?? false;
        $exclude = $options['exclude'] ?? ['page', 'per_page', 'sort_by', 'sort_dir', 'sort', 'direction'];
        
        // Get allowed fields for filtering
        $allowedFields = $this->getAllowedFilterFields();
        
        // Extract search query if present
        $searchQuery = null;
        if (isset($params['q']) && !empty($params['q'])) {
            $searchQuery = $params['q'];
            unset($params['q']);
        }
        
        // Apply text search first if provided
        if ($searchQuery) {
            $query->search($searchQuery);
        }
        
        // Parse and apply filters
        $filters = $this->parseRequestFilters($params, $allowedFields, $exclude, $strict);
        
        if (!empty($filters)) {
            $query->filter($filters);
        }
        
        return $query;
    }

    /**
     * Scope to apply filters with automatic search detection.
     * 
     * Enhanced version that also handles advanced search operators.
     *
     * @param Builder $query
     * @param array|null $params
     * @param array $options
     * @return Builder
     */
    public function scopeSmartFilter(Builder $query, ?array $params = null, array $options = []): Builder
    {
        $params = $params ?? request()->all();
        $strict = $options['strict'] ?? false;
        $exclude = $options['exclude'] ?? ['page', 'per_page', 'sort_by', 'sort_dir', 'sort', 'direction'];
        
        // Get allowed fields
        $allowedFields = $this->getAllowedFilterFields();
        
        // Extract and apply search query
        $searchQuery = $params['q'] ?? null;
        $searchFields = $params['fields'] ?? null;
        
        if ($searchQuery) {
            // Use advanced search if search query contains operators
            if ($this->hasSearchOperators($searchQuery)) {
                $query->advancedSearch($searchQuery, $searchFields);
            } else {
                $query->search($searchQuery, $searchFields);
            }
            unset($params['q'], $params['fields']);
        }
        
        // Parse and apply field filters
        $filters = $this->parseRequestFilters($params, $allowedFields, $exclude, $strict);
        
        if (!empty($filters)) {
            $query->filter($filters);
        }
        
        // Apply sorting if provided
        $this->applySorting($query, $params);
        
        return $query;
    }

    /**
     * Get allowed fields for filtering.
     *
     * @return array
     */
    protected function getAllowedFilterFields(): array
    {
        // Combine searchable fields with fillable fields
        $fields = array_merge(
            $this->getSearchFields(),
            property_exists($this, 'fillable') ? $this->fillable : []
        );
        
        // Add common timestamp fields
        $fields = array_merge($fields, ['created_at', 'updated_at', 'deleted_at', 'archived_at']);
        
        return array_unique($fields);
    }

    /**
     * Parse request parameters into filter array.
     *
     * @param array $params
     * @param array $allowedFields
     * @param array $exclude
     * @param bool $strict
     * @return array
     */
    protected function parseRequestFilters(array $params, array $allowedFields, array $exclude, bool $strict): array
    {
        $filters = [];
        
        foreach ($params as $key => $value) {
            // Skip excluded parameters
            if (in_array($key, $exclude)) {
                continue;
            }
            
            // Skip empty values
            if (is_null($value) || $value === '') {
                continue;
            }
            
            // Parse field and operator
            $parsed = $this->parseFilterKey($key);
            $field = $parsed['field'];
            $operator = $parsed['operator'];
            
            // Check if field is allowed (skip relationship fields in strict mode)
            if ($strict && !str_contains($field, '.') && !in_array($field, $allowedFields)) {
                continue;
            }
            
            // Handle relationship fields (allow them if they're in searchable)
            if (str_contains($field, '.')) {
                $baseField = explode('.', $field)[0];
                if ($strict && !in_array($field, $this->getSearchFields())) {
                    continue;
                }
            }
            
            // Add to filters with operator
            if ($operator) {
                $filters[$field . ':' . $operator] = $value;
            } else {
                $filters[$field] = $value;
            }
        }
        
        return $filters;
    }

    /**
     * Parse filter key to extract field and operator.
     *
     * @param string $key
     * @return array ['field' => string, 'operator' => string|null]
     */
    protected function parseFilterKey(string $key): array
    {
        // Check for operator in key (e.g., "price:>=", "created_at:between")
        if (str_contains($key, ':')) {
            $parts = explode(':', $key, 2);
            return [
                'field' => $parts[0],
                'operator' => $parts[1],
            ];
        }
        
        return [
            'field' => $key,
            'operator' => null,
        ];
    }

    /**
     * Check if search query contains advanced search operators.
     *
     * @param string $query
     * @return bool
     */
    protected function hasSearchOperators(string $query): bool
    {
        return str_contains($query, ' AND ') ||
               str_contains($query, ' OR ') ||
               str_contains($query, '"') ||
               str_starts_with(trim($query), '-');
    }

    /**
     * Apply sorting to query from request parameters.
     *
     * @param Builder $query
     * @param array $params
     * @return void
     */
    protected function applySorting(Builder $query, array $params): void
    {
        $sortBy = $params['sort_by'] ?? $params['sort'] ?? null;
        $sortDir = $params['sort_dir'] ?? $params['direction'] ?? 'desc';
        
        if ($sortBy) {
            $query->orderBy($sortBy, $sortDir);
        }
    }

    /**
     * Scope to apply filters and return paginated results.
     *
     * @param Builder $query
     * @param array|null $params
     * @param int $perPage
     * @param array $options
     * @return mixed
     */
    public function scopeFilterAndPaginate(Builder $query, ?array $params = null, int $perPage = 15, array $options = []): mixed
    {
        $params = $params ?? request()->all();
        $perPage = $params['per_page'] ?? $perPage;
        
        return $query->smartFilter($params, $options)->paginate($perPage);
    }

    /**
     * Apply complex filter expressions from a query string.
     * 
     * Supports advanced filter expressions with multiple operators.
     * See advanced_search_example.php for detailed usage examples.
     * 
     * Available operators: IN, NOT_IN, BETWEEN, NOT_BETWEEN, EQ, NEQ, GT, GTE, LT, LTE,
     * LIKE, NOT_LIKE, STARTS_WITH, ENDS_WITH, IS_NULL, IS_NOT_NULL, DATE_EQ, DATE_GT,
     * DATE_GTE, DATE_LT, DATE_LTE, DATE_BETWEEN, YEAR, MONTH, DAY, JSON_CONTAINS,
     * JSON_LENGTH, REGEX
     *
     * @param Builder $query
     * @param string $filterString Filter expression string
     * @param array $allowedFields Optional whitelist of allowed fields for security
     * @return Builder
     */
    public function scopeFilterQueryString(Builder $query, string $filterString, array $allowedFields = []): Builder
    {
        if (empty($filterString)) {
            return $query;
        }

        $filters = $this->parseFilterString($filterString);
        $allowedFields = !empty($allowedFields) ? $allowedFields : $this->getFilterableFields();
        
        foreach ($filters as $field => $condition) {
            // Security check - only allow specified fields if provided
            if (!empty($allowedFields) && !in_array($field, $allowedFields)) {
                continue;
            }
            
            $this->applyQueryStringFilterCondition($query, $field, $condition);
        }
        
        return $query;
    }

    /**
     * Parse the filter string into structured conditions.
     *
     * @param string $filterString
     * @return array
     */
    protected function parseFilterString(string $filterString): array
    {
        $filters = [];
        
        // Split by semicolon to get individual filter conditions
        $conditions = explode(';', $filterString);
        
        foreach ($conditions as $condition) {
            $condition = trim($condition);
            if (empty($condition)) {
                continue;
            }
            
            // Parse each condition: field:OPERATOR(value1,value2)
            if (preg_match('/^([^:]+):([^(]+)\(([^)]*)\)$/', $condition, $matches)) {
                $field = trim($matches[1]);
                $operator = strtoupper(trim($matches[2]));
                $values = $this->parseFilterValues($matches[3]);
                
                $filters[$field] = [
                    'operator' => $operator,
                    'values' => $values
                ];
            }
        }
        
        return $filters;
    }

    /**
     * Parse values from the condition string.
     *
     * @param string $valueString
     * @return array
     */
    protected function parseFilterValues(string $valueString): array
    {
        if (empty($valueString)) {
            return [];
        }
        
        // Split by comma and clean up values
        $values = array_map(function($value) {
            $value = trim($value);
            
            // Remove quotes if present
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }
            
            // Convert to appropriate type
            if (is_numeric($value)) {
                return str_contains($value, '.') ? (float)$value : (int)$value;
            }
            
            if (strtolower($value) === 'true') {
                return true;
            }
            
            if (strtolower($value) === 'false') {
                return false;
            }
            
            if (strtolower($value) === 'null') {
                return null;
            }
            
            return $value;
        }, explode(',', $valueString));
        
        return $values;
    }

    /**
     * Apply a filter condition to the query builder.
     *
     * @param Builder $query
     * @param string $field
     * @param array $condition
     * @return void
     */
    protected function applyQueryStringFilterCondition(Builder $query, string $field, array $condition): void
    {
        $operator = $condition['operator'];
        $values = $condition['values'];
        
        switch ($operator) {
            case 'IN':
                if (!empty($values)) {
                    $query->whereIn($field, $values);
                }
                break;
                
            case 'NOT_IN':
            case 'NOTIN':
                if (!empty($values)) {
                    $query->whereNotIn($field, $values);
                }
                break;
                
            case 'BETWEEN':
                if (count($values) >= 2) {
                    $query->whereBetween($field, [$values[0], $values[1]]);
                }
                break;
                
            case 'NOT_BETWEEN':
            case 'NOTBETWEEN':
                if (count($values) >= 2) {
                    $query->whereNotBetween($field, [$values[0], $values[1]]);
                }
                break;
                
            case 'EQ':
            case 'EQUALS':
                if (!empty($values)) {
                    $query->where($field, '=', $values[0]);
                }
                break;
                
            case 'NEQ':
            case 'NOT_EQUALS':
            case 'NOTEQUALS':
                if (!empty($values)) {
                    $query->where($field, '!=', $values[0]);
                }
                break;
                
            case 'GT':
            case 'GREATER_THAN':
                if (!empty($values)) {
                    $query->where($field, '>', $values[0]);
                }
                break;
                
            case 'GTE':
            case 'GREATER_THAN_EQUALS':
                if (!empty($values)) {
                    $query->where($field, '>=', $values[0]);
                }
                break;
                
            case 'LT':
            case 'LESS_THAN':
                if (!empty($values)) {
                    $query->where($field, '<', $values[0]);
                }
                break;
                
            case 'LTE':
            case 'LESS_THAN_EQUALS':
                if (!empty($values)) {
                    $query->where($field, '<=', $values[0]);
                }
                break;
                
            case 'LIKE':
                if (!empty($values)) {
                    $query->where($field, 'LIKE', '%' . $values[0] . '%');
                }
                break;
                
            case 'NOT_LIKE':
            case 'NOTLIKE':
                if (!empty($values)) {
                    $query->where($field, 'NOT LIKE', '%' . $values[0] . '%');
                }
                break;
                
            case 'STARTS_WITH':
            case 'STARTSWITH':
                if (!empty($values)) {
                    $query->where($field, 'LIKE', $values[0] . '%');
                }
                break;
                
            case 'ENDS_WITH':
            case 'ENDSWITH':
                if (!empty($values)) {
                    $query->where($field, 'LIKE', '%' . $values[0]);
                }
                break;
                
            case 'IS_NULL':
            case 'ISNULL':
                $query->whereNull($field);
                break;
                
            case 'IS_NOT_NULL':
            case 'ISNOTNULL':
            case 'NOT_NULL':
            case 'NOTNULL':
                $query->whereNotNull($field);
                break;
                
            case 'DATE_EQ':
            case 'DATE_EQUALS':
                if (!empty($values)) {
                    $query->whereDate($field, '=', $values[0]);
                }
                break;
                
            case 'DATE_GT':
            case 'DATE_AFTER':
                if (!empty($values)) {
                    $query->whereDate($field, '>', $values[0]);
                }
                break;
                
            case 'DATE_GTE':
            case 'DATE_FROM':
                if (!empty($values)) {
                    $query->whereDate($field, '>=', $values[0]);
                }
                break;
                
            case 'DATE_LT':
            case 'DATE_BEFORE':
                if (!empty($values)) {
                    $query->whereDate($field, '<', $values[0]);
                }
                break;
                
            case 'DATE_LTE':
            case 'DATE_TO':
                if (!empty($values)) {
                    $query->whereDate($field, '<=', $values[0]);
                }
                break;
                
            case 'DATE_BETWEEN':
                if (count($values) >= 2) {
                    $query->whereBetween($field, [$values[0], $values[1]]);
                }
                break;
                
            case 'YEAR':
                if (!empty($values)) {
                    $query->whereYear($field, $values[0]);
                }
                break;
                
            case 'MONTH':
                if (!empty($values)) {
                    $query->whereMonth($field, $values[0]);
                }
                break;
                
            case 'DAY':
                if (!empty($values)) {
                    $query->whereDay($field, $values[0]);
                }
                break;
                
            case 'JSON_CONTAINS':
                if (!empty($values)) {
                    $query->whereJsonContains($field, $values[0]);
                }
                break;
                
            case 'JSON_LENGTH':
                if (!empty($values)) {
                    $query->whereJsonLength($field, $values[0]);
                }
                break;
                
            case 'REGEX':
            case 'REGEXP':
                if (!empty($values)) {
                    $query->where($field, 'REGEXP', $values[0]);
                }
                break;
                
            default:
                // For unknown operators, try to apply as simple equality
                if (!empty($values)) {
                    $query->where($field, '=', $values[0]);
                }
                break;
        }
    }

    /**
     * Get filterable fields.
     *
     * @return array
     */
    protected function getFilterableFields(): array
    {
        if (!empty($this->filterableFields)) {
            return $this->filterableFields;
        }
        
        return $this->getAllowedFilterFields();
    }

    /**
     * Build a filter string from an array of conditions.
     * 
     * Useful for generating URLs with filters.
     *
     * @param array $filters
     * @return string
     */
    public static function buildFilterString(array $filters): string
    {
        $conditions = [];
        
        foreach ($filters as $field => $condition) {
            if (is_array($condition) && isset($condition['operator'], $condition['values'])) {
                $operator = $condition['operator'];
                $values = is_array($condition['values']) ? $condition['values'] : [$condition['values']];
                
                // Escape and quote string values
                $escapedValues = array_map(function($value) {
                    if (is_string($value) && (str_contains($value, ',') || str_contains($value, ')'))) {
                        return '"' . addslashes($value) . '"';
                    }
                    return $value;
                }, $values);
                
                $valueString = implode(',', $escapedValues);
                $conditions[] = "{$field}:{$operator}({$valueString})";
            } elseif (is_array($condition)) {
                // Simple IN condition
                $valueString = implode(',', $condition);
                $conditions[] = "{$field}:IN({$valueString})";
            } else {
                // Simple equality condition
                $conditions[] = "{$field}:EQ({$condition})";
            }
        }
        
        return implode(';', $conditions);
    }

    /**
     * Get available filter operators.
     *
     * @return array
     */
    public static function getAvailableFilterOperators(): array
    {
        return [
            'IN' => 'Field value is in the provided list',
            'NOT_IN' => 'Field value is not in the provided list',
            'BETWEEN' => 'Field value is between two values',
            'NOT_BETWEEN' => 'Field value is not between two values',
            'EQ' => 'Field value equals the provided value',
            'NEQ' => 'Field value does not equal the provided value',
            'GT' => 'Field value is greater than the provided value',
            'GTE' => 'Field value is greater than or equal to the provided value',
            'LT' => 'Field value is less than the provided value',
            'LTE' => 'Field value is less than or equal to the provided value',
            'LIKE' => 'Field value contains the provided string',
            'NOT_LIKE' => 'Field value does not contain the provided string',
            'STARTS_WITH' => 'Field value starts with the provided string',
            'ENDS_WITH' => 'Field value ends with the provided string',
            'IS_NULL' => 'Field value is null',
            'IS_NOT_NULL' => 'Field value is not null',
            'DATE_EQ' => 'Date field equals the provided date',
            'DATE_GT' => 'Date field is after the provided date',
            'DATE_GTE' => 'Date field is on or after the provided date',
            'DATE_LT' => 'Date field is before the provided date',
            'DATE_LTE' => 'Date field is on or before the provided date',
            'DATE_BETWEEN' => 'Date field is between two dates',
            'YEAR' => 'Year of date field equals the provided year',
            'MONTH' => 'Month of date field equals the provided month',
            'DAY' => 'Day of date field equals the provided day',
            'JSON_CONTAINS' => 'JSON field contains the provided value',
            'JSON_LENGTH' => 'JSON field has the specified length',
            'REGEX' => 'Field value matches the provided regular expression',
        ];
    }

    /**
     * Validate a filter string format.
     *
     * @param string $filterString
     * @return array Array with 'valid' boolean and 'errors' array
     */
    public static function validateFilterString(string $filterString): array
    {
        $errors = [];
        $valid = true;
        
        if (empty($filterString)) {
            return ['valid' => true, 'errors' => []];
        }
        
        $conditions = explode(';', $filterString);
        $availableOperators = array_keys(static::getAvailableFilterOperators());
        
        foreach ($conditions as $index => $condition) {
            $condition = trim($condition);
            if (empty($condition)) {
                continue;
            }
            
            if (!preg_match('/^([^:]+):([^(]+)\(([^)]*)\)$/', $condition, $matches)) {
                $errors[] = "Invalid condition format at position {$index}: '{$condition}'";
                $valid = false;
                continue;
            }
            
            $field = trim($matches[1]);
            $operator = strtoupper(trim($matches[2]));
            $values = trim($matches[3]);
            
            if (empty($field)) {
                $errors[] = "Empty field name in condition: '{$condition}'";
                $valid = false;
            }
            
            if (!in_array($operator, $availableOperators)) {
                $errors[] = "Unknown operator '{$operator}' in condition: '{$condition}'";
                $valid = false;
            }
            
            // Validate operator-specific requirements
            if (in_array($operator, ['BETWEEN', 'NOT_BETWEEN', 'DATE_BETWEEN'])) {
                $valueArray = explode(',', $values);
                if (count($valueArray) < 2) {
                    $errors[] = "Operator '{$operator}' requires at least 2 values in condition: '{$condition}'";
                    $valid = false;
                }
            }
            
            if (in_array($operator, ['IS_NULL', 'IS_NOT_NULL']) && !empty($values)) {
                $errors[] = "Operator '{$operator}' should not have values in condition: '{$condition}'";
                $valid = false;
            }
        }
        
        return ['valid' => $valid, 'errors' => $errors];
    }
}
