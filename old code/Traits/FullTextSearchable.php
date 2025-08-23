<?php

namespace Litepie\Database\Traits;

use Illuminate\Database\Eloquent\Builder;

trait FullTextSearchable
{
    /**
     * Scope a query for full-text search.
     *
     * @param Builder $query
     * @param string $term
     * @param array $columns
     * @return Builder
     */
    public function scopeFullTextSearch(Builder $query, string $term, array $columns)
    {
        $columnsList = implode(',', $columns);
        return $query->whereRaw(
            "MATCH ($columnsList) AGAINST (? IN BOOLEAN MODE)",
            [$term]
        );
    }
}
