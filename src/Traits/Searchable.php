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
}
